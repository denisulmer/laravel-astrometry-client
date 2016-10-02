<?php

namespace Schnubertus\Astrometry;

use Exception;
use GuzzleHttp\Client as HttpClient;
use HttpUrlException;

class AstrometryClient
{
    /**
     * AstrometryClient constructor.
     * @param HttpClient $httpClient
     * @param $loginUrl
     * @param $fileUploadUrl
     * @param $urlUploadUrl
     * @param $jobStatusUrl
     * @param $apiKey
     */
    public function __construct(HttpClient $httpClient, $loginUrl, $fileUploadUrl, $urlUploadUrl, $jobStatusUrl, $apiKey)
    {
        // Initialisation of client
        $this->httpClient = $httpClient;
        $this->loginUrl = $loginUrl;
        $this->fileUploadUrl = $fileUploadUrl;
        $this->urlUploadUrl = $urlUploadUrl;
        $this->jobStatusUrl = $jobStatusUrl;
        $this->apiKey = $apiKey;
        $this->login = false;
        $this->session = null;

        // Test availability of astrometry.net
        $this->reachable = $this->checkAvailability();

        // Authenticate
        if ($this->reachable) {
            $this->session = $this->checkAuthentication($this->sendAuthentication());
        }
    }

    /**
     * Test if the astrometry.net service is available
     * @return bool
     */
    private function checkAvailability()
    {
        // Test if the service is available by checking the HTTP_STATUS
        return $this->httpClient->get($this->loginUrl)->getStatusCode() === 200;
    }

    /**
     * Test if the provided API key is valid and request a session ID
     * @param $response
     * @return mixed
     * @throws Exception
     */
    private function checkAuthentication($response)
    {
        // Test the response status
        if ($response->status === 'success') {
            return $response->session;
        } else if ($response->status === 'error') {
            throw new Exception('Failed to login: ' . $response->errormessage);
        } else {
            throw new Exception('There was an unknown problem during the login attempt');
        }
    }

    /**
     * Submit a GET request to the astrometry.net service
     * @param $url
     * @return mixed
     */
    private function get($url)
    {
        return $this->submit($url, array(), 'GET');
    }

    /**
     * Submit a POST request to the astrometry.net service
     * @param $url
     * @param $data
     * @return mixed
     */
    private function post($url, $data)
    {
        return $this->submit($url, $data, 'POST');
    }

    /**
     * Submit a request to the astrometry.net service
     * @param $url
     * @param $data
     * @param $type
     * @return mixed
     */
    private function submit($url, $data, $type)
    {
        if (isset($type) === false) throw new \InvalidArgumentException('The submit function must have a type specified');

        $request = $this->httpClient->request($type, $url, array(
                'form_params' => array('request-json' => json_encode($data, JSON_UNESCAPED_SLASHES)))
        );

        // Return the response
        return json_decode($request->getBody()->getContents());
    }

    /**
     * Send the provided API key
     * @return mixed
     * @throws Exception
     */
    private function sendAuthentication()
    {
        // Test if an API key is specified
        if (strlen(trim($this->apiKey)) === 0) throw new Exception('Please specify an API-Key in the config/astrometry.php file');

        // Make the request
        return  $this->post($this->loginUrl, array('apikey' => $this->apiKey));
    }

    /**
     * Upload an image from a specified URL
     * @param $url
     * @param array $options
     * @return mixed
     * @throws HttpUrlException
     */
    public function uploadFromUrl($url, $options = array())
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
            // This data is the minimum needed for this request
            $data = array('session' => $this->session, 'url' => $url);

            // Make the request
            return $this->post($this->urlUploadUrl, array_merge($options, $data));
        } else {
            throw new HttpUrlException('The specified URL is invalid');
        }
    }

    /**
     * Fetch the current processing status of a job
     * @param $jobId
     * @return mixed
     */
    public function getJobStatus($jobId)
    {
        $jobStatusUrl = $this->jobStatusUrl . '/' . $jobId;
        return $this->get($jobStatusUrl);
    }

    /**
     * @param $jobId
     * @return mixed
     */
    public function getKnowObjects($jobId)
    {
        $knowObjectsUrl = $this->jobStatusUrl . '/' . $jobId . '/objects_in_field/';
        return $this->get($knowObjectsUrl);
    }
}
