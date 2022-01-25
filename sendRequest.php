<?php

require_once('Api.php');
require_once('Message.php');

$options = getopt("", array("host:", "request:"));

$host = 'localhost:8000'; //endpoint local (padrão)
$request_count = 10; // quantidade de requests a fazer (padrão)

if (!empty($options)) {
    foreach ($options as $key => $opt) {
        if ($key == 'host') {
            $host = $opt;
            continue;
        }
        $request_count = $opt;
    }
}

$api = new Api($host, $request_count);

Message::displayMessage(sprintf('Iniciando o disparo de %s requisições...', $request_count));
Message::displayMessage(sprintf('Utilizando o host: %s', $host));

$api->generateRequests();

Message::displayMessage('Acabei!');
exit();