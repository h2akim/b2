<?php

namespace H2akim\B2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class B2API {

    protected $authorizationUrl = "https://api.backblaze.com/b2api/v1/";
    protected $apiUrl;

    protected $accountId;
    protected $applicationKey;
    protected $client;

    /* */
    protected $authToken;
    protected $downloadUrl;

    public function __construct($accountId, $applicationKey) {
        $this->accountId = $accountId;
        $this->applicationKey = $applicationKey;
        $this->client = new Client();

        $this->b2_authorize_account();

    }

    public function b2_authorize_account() {

        $credentials = base64_encode($this->accountId . ":" . $this->applicationKey);
        $headers = [
            "Accept: application/json",
            "Authorization: Basic " . $credentials
        ];

        $curl_opts = [
            'curl' => [
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true
            ]
        ];

        $response = $this->getRequest(__FUNCTION__, $curl_opts);

        /* Set Auth Token & Download Url */
        try {
            $result = json_decode($response->getBody());
            $this->authToken = $result->authorizationToken;
            $this->apiUrl = $result->apiUrl.'/b2api/v1/';
            $this->downloadUrl = $result->downloadUrl;
        } catch (Exception $e) {
            throw new Exception('Not Authorized');
        }

    }

    public function b2_cancel_large_file() {

    }

    public function b2_create_bucket($bucketName, $bucketType = 'allPrivate') {

        /* Set Authorization */
        $headers = [
            'Authorization: ' . $this->authToken
        ];

        /* Set POST fields */
        $fields = json_encode([
            'accountId' => $this->accountId,
            'bucketName' => $bucketName,
            'bucketType' => $bucketType
        ]);

        /* Set CURL options */
        $curl_opts = [
            'curl' => [
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true
            ]
        ];

        $response = $this->postRequest(__FUNCTION__, $curl_opts);

        return json_decode($response->getBody());

    }

    public function b2_delete_bucket($bucketId) {

        /* Set Authorization */
        $headers = [
            'Authorization: ' . $this->authToken
        ];

        /* Set POST fields */
        $fields = json_encode([
            'accountId' => $this->accountId,
            'bucketId' => $bucketId,
        ]);

        /* Set CURL options */
        $curl_opts = [
            'curl' => [
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true
            ]
        ];

        $response = $this->postRequest(__FUNCTION__, $curl_opts);

        return json_decode($response->getBody());

    }

    public function b2_delete_bucket_by_name($bucketName) {

        $bucketList = json_decode(json_encode($this->b2_list_buckets()), true);
        $key = array_search($bucketName, array_column($bucketList['buckets'], 'bucketName'));

        if (is_null($key) || empty($key)) {
            return 'Error: Bucket not found!';
        }

        return $this->b2_delete_bucket($bucketList['buckets'][$key]['bucketId']);

    }

    public function b2_delete_file_version() {

    }

    public function b2_download_file_by_id() {

    }

    public function b2_download_file_by_name() {

    }

    public function b2_finish_large_file() {

    }

    public function b2_get_file_info() {

    }

    public function b2_get_upload_part_url() {

    }

    public function b2_get_upload_url() {

    }

    public function b2_hide_file() {

    }

    public function b2_list_buckets() {

        /* Set Authorization */
        $headers = [
            'Authorization: ' . $this->authToken
        ];

        $fields = json_encode([
            'accountId' => $this->accountId
        ]);

        /* Set CURL options */
        $curl_opts = [
            'curl' => [
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true
            ]
        ];

        $response = $this->postRequest(__FUNCTION__, $curl_opts);

        return json_decode($response->getBody());

    }

    public function b2_list_file_names() {

    }

    public function b2_list_file_versions() {

    }

    public function b2_list_parts() {

    }

    public function b2_list_unfinished_large_files() {

    }

    public function b2_start_large_file() {

    }

    public function b2_update_bucket() {

    }

    public function b2_upload_file() {

    }

    public function b2_upload_part() {

    }

    private function getRequest($functionName, $curl_opts, $apiUrl = false) {
        $url = ($apiUrl) ? $this->apiUrl : $this->authorizationUrl;
        try {
            return $this->client->request(
                'GET', $url.$functionName, $curl_opts
            );
        } catch (ClientException $e) {
            $test = $e->getMessage();
            die($test);
        }
    }

    private function postRequest($functionName, $curl_opts, $apiUrl = true) {
        $url = ($apiUrl) ? $this->apiUrl : $this->authorizationUrl;
        try {
            return $this->client->request(
                'POST', $url.$functionName, $curl_opts
            );
        } catch (ClientException $e) {
            $test = $e->getMessage();
            die($test);
        }
    }

}
