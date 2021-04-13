<?php

class Merit_Aktiva_Client
{

    private $logger;
    private $context;

    private const INVOICE_ENDPOINT = 'sendinvoice';

    private $apiId;
    private $apiKey;
    private $apiUrl;

    public function __construct($apiUrl, $apiId, $apiKey)
    {
        $this->apiUrl  = $apiUrl;
        $this->apiId   = $apiId;
        $this->apiKey  = $apiKey;
        $this->logger  = wc_get_logger();
        $this->context = array('source' => get_class());
    }

    public static function create($apiUrl, $apiId, $apiKey)
    {
        if ($apiUrl && $apiId && $apiKey) {
            return new Merit_Aktiva_Client($apiUrl, $apiId, $apiKey);
        }

        return false;
    }

    public function send_invoice($invoice)
    {
        $response = $this->post($invoice, self::INVOICE_ENDPOINT);

        $data = json_decode($response, true);

        if (!$data || !is_array($data) || !isset($data['InvoiceId'])) {
            return false;
        }

        $guid    = strtoupper($data['InvoiceId']);
        $success = preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid) == 1;

        return [
            'success' => $success,
            'data'    => $data,
        ];
    }

    private function post($data, $endpoint)
    {
        $args = array(
            'body'        => $data,
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking'    => true,
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ),
        );

        $timestamp = (new DateTime('now', wp_timezone()))->format('YmdHis');

        $signature = $this->signURL($this->apiId, $this->apiKey, $timestamp, json_encode($data));
        $url       = $this->apiUrl . $endpoint . '?ApiId=' . $this->apiId . '&timestamp=' . $timestamp . '&signature=' . $signature;
        $this->logger->info("Url $url", $this->context);

        $response = wp_remote_post($url, $args);
        $this->logger->info('Client response: ' . json_encode($response), $this->context);

        return $response['body'];
    }

    private function signURL($id, $key, $timestamp, $json)
    {
        $signable  = $id . $timestamp . $json;
        $rawSig    = hash_hmac('sha256', $signable, $key, true);
        $base64Sig = base64_encode($rawSig);
        return $base64Sig;
    }
}
