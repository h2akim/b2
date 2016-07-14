<?php

namespace H2akim\B2;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class B2API {

    protected $errorCode = [ 400, 401, 403, 429, 500, 503 ];
    protected $authorizationUrl = "https://api.backblaze.com/b2api/v1/";
    protected $apiUrl;

    protected $accountId;
    protected $applicationKey;
    protected $client;

    /* */
    protected $authToken;
    protected $downloadUrl;

    public function __construct($credentials) {
        $this->accountId = $credentials['accountId'];
        $this->applicationKey = $credentials['applicationKey'];
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
            $result = json_decode($response);
            $this->authToken = $result->authorizationToken;
            $this->apiUrl = $result->apiUrl.'/b2api/v1/';
            $this->downloadUrl = $result->downloadUrl.'/b2api/v1/';
        } catch (Exception $e) {
            throw new Exception('Not Authorized');
        }

    }

    public function b2_cancel_large_file() {

    }

    public function b2_create_bucket($bucketName, $bucketType = 'allPrivate') {

        $curl_opts = $this->preparePostField([
            'accountId' => $this->accountId,
            'bucketName' => $bucketName,
            'bucketType' => $bucketType
        ]);

        $response = $this->postRequest(__FUNCTION__, $curl_opts);
        return $this->returnResponse($response);

    }

    public function b2_delete_bucket($bucketId) {

        $curl_opts = $this->preparePostField([
            'accountId' => $this->accountId,
            'bucketId' => $bucketId,
        ]);

        $response = $this->postRequest(__FUNCTION__, $curl_opts);
        return $this->returnResponse($response);

    }

    public function b2_delete_bucket_by_name($bucketName) {

        $bucketList = json_decode(json_encode($this->b2_list_buckets()), true);
        $key = array_search($bucketName, array_column($bucketList['buckets'], 'bucketName'));

        if (is_null($key)) {
            return json_encode([ 'message' => 'Bucket name not found' ]);
        }

        return $this->b2_delete_bucket($bucketList['buckets'][$key]['bucketId']);

    }

    public function b2_delete_file_version() {
        $curl_opts = $this->preparePostField([
            "fileId" => $file_id,
            "fileName" => $file_name
        ]);
        $response = $this->postRequest(__FUNCTION__, $curl_opts);
        return $this->returnResponse($response);
    }

    public function b2_download_file_by_id($fileId, $options = array()) {

        $curl_opts = [
            'curl' => [
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->authToken
                ],
                CURLOPT_RETURNTRANSFER => true
            ],
            'sink' => isset($options['saveTo']) ? $options['saveTo'] : null
        ];

        try {
            $response = $this->client->request(
                'GET',
                $this->downloadUrl.__FUNCTION__.'?fileId='.$fileId,
                $curl_opts
            );
            return isset($options['saveTo']) ? 'OK' : $response;
        } catch (ClientException $e) {
            $response = $e->getResponse();
            return $this->toJson($response->getBody()->getContents());
        }

    }

    public function b2_download_file_by_name() {

    }

    public function b2_finish_large_file() {

    }

    public function b2_get_file_info() {

    }

    public function b2_get_upload_part_url() {

    }

    public function b2_get_upload_url($options = []) {

        if (isset($options['bucketName'])) {
            $bucketList = json_decode($this->b2_list_buckets(), true);
            $key = array_search($options['bucketName'], array_column($bucketList['buckets'], 'bucketName'));

            if (is_null($key)) {
                return json_encode([ 'message' => 'Bucket name not found' ]);
            }
            $options['bucketId'] = $bucketList['buckets'][$key]['bucketId'];
        }

        if (isset($options['bucketId'])) {
            $curl_opts = $this->preparePostField([
                'bucketId' => $options['bucketId']
            ]);
            $response = $this->postRequest(__FUNCTION__, $curl_opts);
            return $this->toJson($response);
        } else {
            return 'Please provide bucketId';
        }

    }

    public function b2_hide_file() {

    }

    public function b2_list_buckets() {

        $curl_opts = $this->preparePostField([
            'accountId' => $this->accountId
        ]);

        $response = $this->postRequest(__FUNCTION__, $curl_opts);
        return $this->returnResponse($response);

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

    public function b2_upload_file($options = []) {

        if ((isset($options['bucketName']) || isset($options['bucketId']))
            && isset($options['filePath'])) {

                if (isset($options['bucketName'])) {
                    $bucketOption[] = [ 'bucketName' => $options['bucketName'] ];
                } else {
                    $bucketOption[] = [ 'bucketId' => $options['bucketId'] ];
                }

                $uploadUrl = json_decode($this->b2_get_upload_url($bucketOption));

                $file = fread($options['filePath'], filesize($options['filePath']));

                if (!isset($uploadUrl->status)) {
                $curl_opts = $this->preparePostField([ $file ],
                [
                    "Authorization: " . $uploadUrl->authorizationToken,
                    "X-Bz-File-Name: " . $options['filePath'],
                    "Content-Type: " . 'b2/x-auto',
                    "X-Bz-Content-Sha1: " . $sha1_file
                ]);

                }

        } else {
            return 'Please provide bucketName & filePath';
        }

    }

    public function b2_upload_part() {

    }

    private function toJson($json) {
        return json_decode($json, true);
    }

    private function returnResponse($response, $class = false) {
        if (isset($response->status)
            && in_array($response->status, $this->errorCode))
                return json_encode($response);

        return $this->toJson($response);
    }

    private function getRequest($functionName, $curl_opts, $apiUrl = false) {
        $url = ($apiUrl) ? $this->apiUrl : $this->authorizationUrl;
        try {
            return $this->client->request(
                'GET', $url.$functionName, $curl_opts
            )->getBody();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            return $response->getBody()->getContents();
        }
    }

    private function postRequest($functionName, $curl_opts, $apiUrl = true) {
        $url = ($apiUrl) ? $this->apiUrl : $this->authorizationUrl;
        try {
            return $this->client->request(
                'POST', $url.$functionName, $curl_opts
            )->getBody();
        } catch (ClientException $e) {
            $response = $e->getResponse();
            return $response->getBody()->getContents();
        }
    }

    private function preparePostField($userField = [], $userHeader = [], $merge = false) {
        /* Set Authorization */

        $headers = [
            'Authorization: ' . $this->authToken
        ];

        if (!empty($userHeader) && !$merge) {
            $headers = $userHeader;
        }

        if ($merge) {
            $headers = array_merge([
                'Authorization: ' . $this->authToken
            ], $userHeader);
        }

        /* Set POST fields */
        $fields = json_encode($userField);

        /* Set CURL options */
        $curl_opts = [
            'curl' => [
                CURLOPT_POSTFIELDS => $fields,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true
            ]
        ];

        return $curl_opts;
    }

}
