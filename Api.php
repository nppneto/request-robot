<?php

require_once('Message.php');

class Api
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    const SUCCESS = 200;
    const UNAUTHORIZED = 401;
    const NOT_FOUND = 404;
    const INTERNAL_ERROR = 500;

    protected $curl = null;
    protected $token = null;
    protected $http_header = null;
    protected $host = null;
    protected $request_count = null;
    protected $success_count = null;
    protected $errors_count = null;

    protected $credentials = array(
        'email' => 'fabio.198@gmail.com',
        'password' => '123456',
    );    

    public function __construct($host, $request_count) 
    {
        $this->host = $host;
        $this->request_count = $request_count;

        $this->success_count = 0;
        $this->errors_count = 0;

        $this->http_header = array(
            'accept:application/json',
            'content-type:application/json',
            'cache-control:no-cache',
        );
    }

    public function generateRequests()
    {
        if (is_null($this->token)) {
            $this->makeLogin();
        }

        Message::displayMessage(sprintf('Fazendo requisições com o usuário: %s', $this->credentials['email']));

        $endpoint = $this->getCreateProductEndpoint();

        $this->setAuthorizationHeader();

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

            if ($status == self::SUCCESS) {
                $display = 'info';
                $this->success_count++;
            } else {
                $display = 'error';
                $this->errors_count++;
            }

            Message::displayMessage(sprintf('Status: %d', $status), $display);
            sleep(5);
        }

        Message::displayMessage(sprintf('Total de requisições enviadas com sucesso: %d', $this->success_count));
        Message::displayMessage(sprintf('Total de requisições não enviadas: %d', $this->errors_count));

        return true;
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
        return sprintf('http://%s', $this->host);
    }

    protected function getCreateProductEndpoint()
    {
        $host = $this->getHost();
        return sprintf('%s/api/product', $host);
    }

    protected function getUserLoginEndpoint()
    {
        $host = $this->getHost();
        return sprintf('%s/api/login', $host);
    }

    protected function getHttpHeader()
    {
        return $this->http_header;
    }

    protected function setAuthorizationHeader()
    {
        $this->http_header[] = 'Authorization: Bearer ' . $this->token;
    }

    protected function setToken($token)
    {
        $this->token = $token;
    }

    protected function makeLogin()
    {
        $this->initCurl();
        
        $endpoint = $this->getUserLoginEndpoint();
        $this->setCurlOpt(self::METHOD_POST, $endpoint, $this->credentials);

        Message::displayMessage(sprintf('Tentativa de login com o e-mail %s', $this->credentials['email']));

        $response = $this->curlExec();
        $status = $this->getStatus();

        $this->curlClose();

        if ($status != self::SUCCESS) {
            Message::displayMessage('Não foi possível realizar a autenticação...', 'warning');
            Message::displayMessage('Verifique as informações de login', 'warning');
            Message::displayMessage(sprintf('Status: %d', $this->getStatus()), 'error');
            Message::displayMessage('Finalizando execução do robô.');

            exit();   
        }

        $obj = json_decode($response);
        $this->setToken($obj->data->token);

        Message::displayMessage('Autenticação realizada com sucesso...');

        return true;
    }
}