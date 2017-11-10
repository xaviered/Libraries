<?php

namespace ixavier\Libraries\Server\Requests;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

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
	public function prepareUrl( $postPath = null, $queryParams = null ) {
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
	 */
	public function indexRequest( $queryParams = null ) {
		$url = $this->prepareUrl( null, $queryParams );
		Log::info( "INDEX: $url" );

		return $this->getApiResponse( 'get', $url, static::$defaultRequestOptions );
	}

	/**
	 * Creates new entry
	 *
	 * @param array $postData
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function storeRequest( $postData = null, $queryParams = null ) {
		$options = static::$defaultRequestOptions;
		$options[ 'headers' ][ 'Content-Type' ] = 'application/json';
		$options[ 'body' ] = json_encode( $postData );

		$url = $this->prepareUrl( null, $queryParams );
		Log::info( "STORE: $url \n" . $options[ 'body' ] );

		return $this->getApiResponse( 'post', $url, $options );
	}

	/**
	 * Shows current entry
	 *
	 * @param string $slug
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function showRequest( $slug, $queryParams = null ) {
		$url = $this->prepareUrl( $slug, $queryParams );
		Log::info( "SHOW: $url" );

		return $this->getApiResponse( 'get', $url, static::$defaultRequestOptions );
	}

	/**
	 * Updates current entry
	 *
	 * @param string $slug
	 * @param array $postData
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function updateRequest( $slug, $postData, $queryParams = null ) {
		$options = static::$defaultRequestOptions;
		$options[ 'headers' ][ 'Content-Type' ] = 'application/json';
		$options[ 'body' ] = json_encode( $postData );

		$url = $this->prepareUrl( $slug, $queryParams );
		Log::info( "UPDATE: $url \n" . $options[ 'body' ] );

		return $this->getApiResponse( 'patch', $url, $options );
	}

	/**
	 * Deletes current entry
	 *
	 * @param string $slug
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function destroyRequest( $slug, $queryParams = null ) {
		$url = $this->prepareUrl( $slug, $queryParams );
		Log::info( "DELETE: $url" );

		return $this->getApiResponse( 'delete', $url, static::$defaultRequestOptions );
	}

	/**
	 * @return Response
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

	/**
	 * Makes the actual request
	 *
	 * @param string $method
	 * @param string $url
	 * @param array $options
	 * @return ApiResponse
	 */
	protected function getApiResponse( $method, $url, $options ) {
		$response = $this->httpClient->{$method}( $url, $options );

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
			$apiResponse->headers = $response->getHeaders();
			$apiResponse->data = $response->getBody()->getContents();
			$jsonResponse = json_decode( $apiResponse->data );

			if ( empty( $jsonResponse ) || $jsonResponse === FALSE ) {
				$apiResponse->message = 'Internal Server Error';
			}
			else {
				$apiResponse->data = $jsonResponse;
				$apiResponse->error = false;
				$apiResponse->message = null;
				$apiResponse->statusCode = $response->getStatusCode();
			}
		}

		$this->lastResponse = $response;

		return $apiResponse;
	}
}
