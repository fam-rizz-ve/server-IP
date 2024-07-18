<?php

session_start();

function getCountryFromIP($ip) {
    $url = "http://ip-api.com/json/{$ip}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    return $data['status'] === 'success' ? $data['country'] : 'Sconosciuto';
}

function sendDiscordMessage($username, $userRole) {
    // Controllo per invii multipli
    if (isset($_SESSION['message_sent']) && $_SESSION['message_sent'] === true) {
        echo "Messaggio già inviato. Non è possibile inviare più messaggi.";
        return;
    }

    // Controllo della frequenza (cooldown)
    $cooldown = 300; // 5 minuti in secondi
    if (isset($_SESSION['last_message_time']) && (time() - $_SESSION['last_message_time']) < $cooldown) {
        $waitTime = $cooldown - (time() - $_SESSION['last_message_time']);
        echo "Per favore, attendi ancora {$waitTime} secondi prima di inviare un altro messaggio.";
        return;
    }

    // URL del webhook di Discord (sostituisci con il tuo URL)
    $webhookurl = "https://discord.com/api/webhooks/your_webhook_url_here";

    // Ottieni il paese dell'utente in modo sicuro
    $userCountry = getCountryFromIP($_SERVER['REMOTE_ADDR']);

    // Parte fissa del messaggio
    $fixedContent = "Benvenuto nel nostro server Discord!";

    // Parte variabile del messaggio
    $variableContent = "Ciao {$username}! Ti sei unito come {$userRole}. Benvenuto dal {$userCountry}!";

    // Componi il messaggio
    $message = array(
        "content" => $fixedContent,
        "username" => "Bot di Benvenuto",
        "avatar_url" => "https://example.com/avatar.png",
        "tts" => false,
        "embeds" => [
            [
                "title" => "Grazie per esserti unito a noi!",
                "type" => "rich",
                "description" => $variableContent,
                "color" => hexdec("3366ff"),
                "fields" => [
                    [
                        "name" => "Regole del server",
                        "value" => "Per favore, leggi le regole nel canale #regole",
                        "inline" => false
                    ],
                    [
                        "name" => "Presentati",
                        "value" => "Puoi presentarti nel canale #presentazioni",
                        "inline" => false
                    ],
                    [
                        "name" => "Aiuto",
                        "value" => "Se hai domande, chiedi pure nel canale #supporto",
                        "inline" => false
                    ]
                ]
            ]
        ]
    );

    $curl = curl_init($webhookurl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($curl);
    curl_close($curl);

    // Imposta il flag di invio del messaggio e il timestamp
    $_SESSION['message_sent'] = true;
    $_SESSION['last_message_time'] = time();

    echo "Messaggio inviato con successo!";
}

// Esempio di utilizzo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = isset($_POST['username']) ? $_POST['username'] : "Utente Anonimo";
    $newUserRole = isset($_POST['role']) ? $_POST['role'] : "Membro";
    sendDiscordMessage($newUsername, $newUserRole);
} else {
    // Mostra un form HTML se la pagina viene caricata normalmente
    echo '
    <form method="POST">
        <label for="username">Nome utente:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="role">Ruolo:</label>
        <input type="text" id="role" name="role" required><br><br>
        <input type="submit" value="Invia messaggio di benvenuto">
    </form>
    ';
}

?>
