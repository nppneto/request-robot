<?php

require_once('Message.php');

class Api
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const SUCCESS = 200;
    const CREATED = 201;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    const INTERNAL_ERROR = 500;

    const DEFAULT_REQUEST_COUNT = 10;
    const DEFAULT_HOST = 'localhost:8000';

    protected $curl = null;
    protected $token = null;
    protected $http_header = null;
    protected $credentials = null;
    protected $host = null;
    protected $request_count = null;
    protected $success_count = null;
    protected $errors_count = null;

    protected static $status_list = [
        self::SUCCESS => 'Sucesso',
        self::CREATED => 'Criado',
        self::UNAUTHORIZED => 'Não autorizado',
        self::NOT_FOUND => 'Não encontrado',
        self::INTERNAL_ERROR => 'Erro interno do servidor',
    ];

    public function __construct($credentials, $host, $request_count) 
    {
        $this->credentials = $credentials;
        $this->host = $host;
        $this->request_count = $request_count;

        $this->success_count = 0;
        $this->errors_count = 0;

        $this->http_header = [
            'Accept:application/json',
            'Content-Type:application/json',
            'Cache-Control:no-cache',
        ];
    }

    protected function initCurl()
    {
        $this->curl = curl_init();
    }

    protected function curlExec()
    {
        return curl_exec($this->curl);
    }

    protected function getStatus()
    {
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    protected function curlClose()
    {
        return curl_close($this->curl);
    }

    protected function setCurlOpt($method = 'GET', $endpoint, $body = array())
    {
        curl_setopt_array($this->curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $this->getHttpHeader(),
        ]);

        if (!empty($body) && $method = 'POST') {
            curl_setopt_array($this->curl, [
                CURLOPT_POSTFIELDS => json_encode($body),
            ]);
        }
    }

    public function getHost()
    {
        if (!$this->host) {
            $this->host = self::DEFAULT_HOST;
        }

        return sprintf('http://%s', $this->host);
    }

    public function getRequestCount()
    {
        if (!$this->request_count) {
            $this->request_count = self::DEFAULT_REQUEST_COUNT;
        }

        return $this->request_count;
    }

    public function getCreateProductEndpoint()
    {
        $host = $this->getHost();
        return sprintf('%s/api/product', $host);
    }

    public function getUserLoginEndpoint()
    {
        $host = $this->getHost();
        return sprintf('%s/api/login', $host);
    }

    public function getHttpHeader()
    {
        return $this->http_header;
    }

    public function getStatusMessage($status)
    {
        $errorMessage = '';

        if (array_key_exists($status, self::$status_list)) {
            $errorMessage = self::$status_list[$status];
        }

        return $errorMessage;
    }

    protected function setAuthorizationHeader()
    {
        $this->http_header[] = 'Authorization: Bearer ' . $this->token;
    }

    protected function setToken($token)
    {
        $this->token = $token;
    }

    public function makeLogin()
    {
        $this->initCurl();
        
        $endpoint = $this->getUserLoginEndpoint();
        $this->setCurlOpt(self::METHOD_POST, $endpoint, $this->credentials);

        $response = $this->curlExec();
        $status = $this->getStatus();

        $this->curlClose();

        $data = [
            'token' => '', 
            'status' => $status,
            'message' => $this->getStatusMessage($status),
        ];

        if ($status != self::SUCCESS) {
            return $data;
        }

        $obj = json_decode($response);
        $data['token'] = $obj->data->token;

        return $data;
    }

    public function generateRequests($token)
    {
        $this->setToken($token);
        $this->setAuthorizationHeader();

        Message::displayMessage(sprintf('Iniciando o disparo de %s requisições...', $this->getRequestCount()));
        Message::displayMessage(sprintf('Utilizando o host: %s', $this->getHost()));

        $endpoint = $this->getCreateProductEndpoint();

        for ($i = 1; $i <= $this->request_count; $i++) { 
            $body = [
                'name' => sprintf('Produto %s', date('ymdhis')),
                'description' => sprintf('Descrição do produto %s', date('ymdhis')),
                'price' => rand(10, 1000) / 100,
            ];

            $this->initCurl();
            $this->setCurlOpt(self::METHOD_POST, $endpoint, $body);

            Message::displayMessage(sprintf('Tentativa de requisição: %d de %d', $i, $this->request_count));

            $response = $this->curlExec();
            $status = $this->getStatus();
            $this->curlClose();            

            if ($status == self::CREATED) {
                $display = 'info';
                $this->success_count++;
            } else {
                $display = 'error';
                $this->errors_count++;
            }

            $errorMessage = $this->getStatusMessage($status);

            Message::displayMessage(sprintf('Status: %d - %s', $status, $errorMessage), $display);
            sleep(5);
        }

        Message::displayMessage(sprintf('Total de requisições enviadas com sucesso: %d', $this->success_count));
        Message::displayMessage(sprintf('Total de requisições não enviadas: %d', $this->errors_count));

        return true;
    }
}