<?php

namespace ixavier\Libraries\Server\Requests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

/**
 * Class ApiRequest
 *
 * @package ixavier\Libraries\Requests
 */
abstract class ApiRequest
{
	/** @var array Options that will be sent for each request */
	protected static $defaultRequestOptions = [
		'http_errors' => false,
		'synchronous' => false,
		'timeout' => 60,
		'headers' => [
			'Connection' => 'close'
		]
	];

	/** @var HttpClient */
	protected $httpClient;

	/** @var Response */
	protected $lastResponse;

	/** @var string */
	protected $path;

	/** @var string */
	protected $urlBase;

	/**
	 * ApiRequest constructor.
	 *
	 * @param string $urlBase
	 * @param string $path
	 */
	public function __construct( $urlBase = null, $path = '/' ) {
		$this->httpClient = new HttpClient();
		$this->setUrlBase( $urlBase );
		$this->setPath( $path );
	}

	/**
	 * @return mixed
	 */
	public function getUrlBase() {
		return $this->urlBase;
	}

	/**
	 * @param mixed $urlBase
	 * @return $this Chainnable method
	 */
	public function setUrlBase( $urlBase ) {
		$this->urlBase = rtrim( $urlBase, '/' );

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPath() {
		return $this->path;
	}

	/**
	 * @param mixed $path
	 * @return $this Chainnable method
	 */
	public function setPath( $path ) {
		$this->path = '/' . ltrim( $path, '/' );

		return $this;
	}

	/**
	 * Prepares the URL, adds in the $postPath to the end of the URL, and adds in the $queryParams
	 *
	 * @param string $postPath
	 * @param array $queryParams
	 * @return string
	 */
	protected function prepareUrl( $postPath = null, $queryParams = null ) {
		$url = rtrim( $this->getUrlBase() . rtrim( $this->getPath(), '/' ) . '/' . ( $postPath ? ltrim( $postPath, '/' ) : '' ), '/' );
		if ( !is_null( $queryParams ) && is_array( $queryParams ) && count( $queryParams ) ) {
			$url = rtrim( $url, '?' ) . '?' . http_build_query( $queryParams );
		}

		return $url;
	}

    /**
     * A lis of attached entries
     *
     * @param array $queryParams
     * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
     * @throws \Exception
     */
	public function indexRequest( $queryParams = null ) {
		$url = $this->prepareUrl( null, $queryParams );
		$response = $this->getApiResponse( 'get', $url, static::$defaultRequestOptions );
        Log::info( "INDEX: $url" );

        return $response;
	}

    /**
     * Creates new entry
     *
     * @param array $postData
     * @param array $queryParams
     * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
     * @throws \Exception
     */
	public function storeRequest( $postData = null, $queryParams = null ) {
		$options = static::$defaultRequestOptions;
		$options[ 'headers' ][ 'Content-Type' ] = 'application/json';
		$options[ 'body' ] = json_encode( $postData );

		$url = $this->prepareUrl( null, $queryParams );
		$response = $this->getApiResponse( 'post', $url, $options );
        Log::info( "STORE: $url \n" . $options[ 'body' ] );

        return $response;
	}

    /**
     * Shows current entry
     *
     * @param string $slug
     * @param array $queryParams
     * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
     * @throws \Exception
     */
	public function showRequest( $slug, $queryParams = null ) {
		$url = $this->prepareUrl( $slug, $queryParams );

        $response = $this->getApiResponse( 'get', $url, static::$defaultRequestOptions );
        Log::info( "SHOW: $url" );

        return $response;
	}

    /**
     * Updates current entry
     *
     * @param string $slug
     * @param array $postData
     * @param array $queryParams
     * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
     * @throws \Exception
     */
	public function updateRequest( $slug, $postData, $queryParams = null ) {
		$options = static::$defaultRequestOptions;
		$options[ 'headers' ][ 'Content-Type' ] = 'application/json';
		$options[ 'body' ] = json_encode( $postData );

		$url = $this->prepareUrl( $slug, $queryParams );
		$response = $this->getApiResponse( 'patch', $url, $options );
        Log::info( "UPDATE: $url \n" . $options[ 'body' ] );

        return $response;
	}

    /**
     * Deletes current entry
     *
     * @param string $slug
     * @param array $queryParams
     * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
     * @throws \Exception
     */
	public function destroyRequest( $slug, $queryParams = null ) {
		$url = $this->prepareUrl( $slug, $queryParams );

        $response = $this->getApiResponse( 'delete', $url, static::$defaultRequestOptions );
        Log::info( "DELETE: $url" );

        return $response;
	}

    /**
     * Logs in with given credentials
     * @param null $credentials
     * @return array|\stdClass
     * @throws \Exception
     */
	public function loginRequest($credentials=null) {
        $url = $this->prepareUrl('login', $credentials);

        // this is alright because info logs are not turned on production
        Log::info("LOGIN: $url");

        return $this->_getApiResponse('post', $url, static::$defaultRequestOptions)->data ?? new \stdClass();
    }

	/**
	 * @return Response
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

    /**
     * Wrapper to make a request with proper token
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ApiResponse
     * @throws \Exception
     */
    protected function getApiResponse($method, $url, $options)
    {
        if (!$this->getToken()) {
            $this->login();
        }

        $options['headers']['Authorization'] = $this->getToken();

        $response = $this->_getApiResponse($method, $url, $options);

        // token expired, get another one and try again
        if ($response->error && $response->statusCode == 401) {
            $this->login();
            $response = $this->_getApiResponse($method, $url, $options);
        }

        return $response;
    }

    /**
     * Makes the actual request
     *
     * @param string $method
     * @param string $url
     * @param array $options
     * @return ApiResponse
     * @throws \Exception
     */
	private function _getApiResponse( $method, $url, $options ) {
        $response = $this->httpClient->{$method}($url, $options);

		$apiResponse = new ApiResponse();
		$apiResponse->error = true;
		$apiResponse->message = $response->getReasonPhrase();
		$apiResponse->statusCode = 500;
		$apiResponse->request = [
			'method' => $method,
			'url' => $url,
			'options' => $options
		];

		if ( $response ) {
            $apiResponse->data = $response->getBody()->getContents();
			$apiResponse->headers = $response->getHeaders();
            $apiResponse->statusCode = $response->getStatusCode();
			$jsonResponse = json_decode( $apiResponse->data );

			// handle special response codes
            //

            // @todo: handle this better
            // 429: too many requests, try again later
            if($apiResponse->statusCode == 429) {
                $retryAfter = $apiResponse->headers['Retry-After'] ?? 1;
                Log::info("retrying after seconds: " . print_r($retryAfter, 1));
//                sleep(max($retryAfter, 3));
            }

			if ( empty( $jsonResponse ) || $jsonResponse === FALSE ) {
				$apiResponse->message = 'Internal Server Error';
			}
			else {
                $apiResponse->data = $jsonResponse;
                if(!empty($jsonResponse->error)) {
                    $apiResponse->message = $jsonResponse->error;
                }
                else {
                    $apiResponse->error = false;
                    $apiResponse->message = null;
                }
			}
		}

		$this->lastResponse = $response;

		return $apiResponse;
	}

    /**
     * Authenticates to API with current app credentials
     * @throws \Exception If not able to authenticate.
     */
    protected function login()
    {
        $response = $this->loginRequest(config('services.content.credentials'));

        if (!empty($response->success) && isset($response->data->token)) {
            $this->setToken($response->data->token);

            return $this;
        }

        throw new \Exception('Could not login to api. '.($response->error ?? ''));
    }

    /**
     * @return string
     */
    private function getToken()
    {
        $token = session('token');
        if (!empty($token)) {
            $token = 'BEARER '.$token;
        }

        return $token;
    }

    /**
     * @param string $token
     * @return $this
     */
    private function setToken($token)
    {
        session(['token' => $token]);

        return $this;
    }
}
