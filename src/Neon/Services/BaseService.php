<?php
namespace Neon\Services;
use Neon\Exceptions\NeonException;
use Neon\Util\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
/**
 * Super class for all services
 *
 * @package Services
 * @author Constant Contact
 */
abstract class BaseService
{
    /**
     * Helper function to return required headers for making an http request with NeonCRM
     * @return array
     */
    private static function getHeaders()
    {
        return array(
            'User-Agent' => 'NeonCRM PHP Library v' . Config::get('settings.version'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-ctct-request-source' => 'sdk.php' . Config::get('settings.version')
        );
    }
    /**
     * GuzzleHTTP Client Implementation to use for HTTP requests
     * @var Client
     */
    private $client;
    /**
     * ApiKey for the application
     * @var string
     */
    private $apiKey;
    /**
     * Constructor with the option to to supply an alternative rest client to be used
     * @param string $apiKey - NeonCRM api key - user based ~accessToken~
     * @param string $orgId  - organizational identifier
     * still need to build semi-automated pipeline for system account user. No oauth routes route currently
     */
    public function __construct($apiKey,$orgId)
    {
        $this->apiKey = $apiKey;
	$this->orgId = $orgId;
        $this->client = new Client();
    }

    public function connect(){
	//todo
	$this->login();
    }

    /**
    *  Log in to NeonCRM server and retrieve userSessionId
    **/
    private function login()
    {
	$response = $this->client->createRequest('GET',Config::get('endpoints.baseUrl').Config::get('endpoints.login'));
	$request->getQuery()->set("login.apiKey",$this->apiKey);
	$request->getQuery()->set("login.orgid",$this->orgId);
	$request->setHeaders($this->getHeaders());
	try{
	    $response = self::getClient()->send($request);
	    $json = json_decode($response->getBody());
	    if(!$json->loginResponse) throw new EmptyResponseException();
	    if(!$json->loginResponse->operationResult === 'SUCCESS'){
	        throw new BadResponseException($json->loginResponse->responseMessage);
	    }
	    $this->userSessionId = $json->loginResponse->userSessionId;

	}catch (ClientException $e){
	    throw self::convertException($e)
	}
    }

    /**
     * Get the rest client being used by the service
     * @return Client - GuzzleHTTP Client implementation being used
     */
    protected function getClient()
    {
        return $this->client;
    }

    protected function createBaseRequest($userSessionId, $method, $baseUrl) {
	if(!$this->isLoggedIn()) $this->login();
        $request = $this->client->createRequest($method, $baseUrl);
        $request->getQuery()->set("userSessionId", $this->userSessionId);
        $request->setHeaders($this->getHeaders($accessToken));
        return $request;
    }
    /**
     * @param ClientException $exception - Guzzle ClientException
     * @return NeonException
     */
    protected function convertException($exception)
    {
        $neonException = new NeonException($exception->getResponse()->getReasonPhrase(), $exception->getCode());
        $neonException->setUrl($exception->getResponse()->getEffectiveUrl());
        $neonException->setErrors(json_decode($exception->getResponse()->getBody()->getContents()));
        return $neonException;
    }
}
