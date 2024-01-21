<?php

class OneSignalApiClient {
    private $apiUrl = config('client.one_signal.base_url') +'/v1/notification';

    public function __construct($restAuthKey) {
        $this->restAuthKey = $restAuthKey;
    }

    public function send($fields, $job_id, $logger) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', config('client.one_signal.auth_key')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        $logger->addInfo('Push send for job ' . $job_id . ' curl answer', [$response]);

        curl_close($ch);
        return $response;
    }
}

