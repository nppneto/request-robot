<?php

require_once('Api.php');
require_once('Message.php');

$credentials = [
    'email' => 'fabio.198@gmail.com',
    'password' => '123456',
];

$options = getopt("", array("host:", "request:"));

$host = null;
$request_count = null;

if (!empty($options)) {
    foreach ($options as $key => $opt) {
        if ($key == 'host') {
            $host = $opt;
            continue;
        }
        $request_count = $opt;
    }
}

$api = new Api($credentials, $host, $request_count);

Message::displayMessage(sprintf('Realizando tentativa de login com o e-mail %s', $credentials['email']));

$response = $api->makeLogin();

if (isset($response['token']) && empty($response['token'])) {
    Message::displayMessage('Não foi possível realizar a autenticação.');
    Message::displayMessage('Verifique as informações de login.');
    Message::displayMessage(sprintf('Status: %d - %s', $response['status'], $response['message']));
    Message::displayMessage('Finalizando execução do robô.');
    exit();
}

Message::displayMessage(sprintf('Usuário %s autenticado!', $credentials['email']));
$api->generateRequests($response['token']);

Message::displayMessage('Acabei!');
exit();