<?php

class Merit_Aktiva_Client
{

    private const INVOICE_ENDPOINT = 'sendinvoice';

    private $apiId;
    private $apiKey;
    private $apiUrl;

    public function __construct($apiUrl, $apiId, $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiId  = $apiId;
        $this->apiKey = $apiKey;
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
        $logger = wc_get_logger();
        $context = array('source' => get_class());
        
        $response = $this->post($invoice, self::INVOICE_ENDPOINT);
        $logger->debug('Client response: ' . $response, $context);

        $data     = json_decode($response, true);
        

        if (!$data || !is_array($data) || !isset($data['InvoiceId'])) {
            return false;
        }

        $guid = strtoupper($data['InvoiceId']);
        $success = preg_match('/^\{?[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}\}?$/', $guid) == 1;

        return [
            'success' => $success,
            'data' => $data
        ];
    }

    private function signURL($id, $key, $timestamp, $json)
    {
        $signable  = $id . $timestamp . $json;
        $rawSig    = hash_hmac('sha256', $signable, $key, true);
        $base64Sig = base64_encode($rawSig);
        return $base64Sig;

    }

    private function post($data, $endpoint)
    {
        $responseString = "";

        $ch = curl_init();

        $TIMESTAMP = date("YmdHis");

        $signature = $this->signURL($this->apiId, $this->apiKey, $TIMESTAMP, json_encode($data));
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $endpoint . "?ApiId=" . $this->apiId . "&timestamp=" . $TIMESTAMP . "&signature=" . $signature);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body        = substr($response, $header_size);

        if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) != 200) {

            return false;

        } else {

            $woSlashes      = stripslashes($body);
            $strLen         = strlen($woSlashes);
            $responseString = substr(substr($woSlashes, 1, $strLen), 0, $strLen - 2);

        }
        curl_close($ch);
        return $responseString;
    }
}
