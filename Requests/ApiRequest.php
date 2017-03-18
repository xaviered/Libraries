<?php
namespace ixavier\Libraries\Requests;

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
	public function __construct( $urlBase, $path = '/' ) {
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
		$url = $this->getUrlBase() . $this->getPath() . '/' . ( $postPath ? ltrim( $postPath, '/' ) : '' );
		if ( !is_null( $queryParams ) && is_array( $queryParams ) ) {
			$url = rtrim( $url, '?' ) . '?' . http_build_query( $queryParams );
		}

		Log::info( 'Request: ' . $url );

		return $url;
	}

	/**
	 * A lis of attached entries
	 *
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function indexRequest( $queryParams = null ) {
		return $this->getApiResponse(
			$this->httpClient->get(
				$this->prepareUrl( null, $queryParams ),
				static::$defaultRequestOptions
			)
		);
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

		return $this->getApiResponse(
			$this->httpClient->post(
				$this->prepareUrl( null, $queryParams ),
				$options
			)
		);
	}

	/**
	 * Shows current entry
	 *
	 * @param string $slug
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function showRequest( $slug, $queryParams = null ) {
		return $this->getApiResponse(
			$this->httpClient->get(
				$this->prepareUrl( $slug, $queryParams ),
				static::$defaultRequestOptions
			)
		);
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

		return $this->getApiResponse(
			$this->httpClient->patch(
				$this->prepareUrl( $slug, $queryParams ),
				$options
			)
		);
	}

	/**
	 * Deletes current entry
	 *
	 * @param string $slug
	 * @param array $queryParams
	 * @return ApiResponse If 'error' is true, can retrieve last response with $this->getLastResponse()
	 */
	public function destroyRequest( $slug, $queryParams = null ) {
		return $this->getApiResponse(
			$this->httpClient->delete(
				$this->prepareUrl( $slug, $queryParams ),
				static::$defaultRequestOptions
			)
		);
	}

	/**
	 * @return mixed
	 */
	public function getLastResponse() {
		return $this->lastResponse;
	}

	/**
	 * @param Response|ResponseInterface $response
	 * @return ApiResponse
	 */
	protected function getApiResponse( Response $response ) {
		$this->lastResponse = $response;

		$apiResponse = new ApiResponse();
		$apiResponse->error = true;
		$apiResponse->message = 'Server Timeout';
		$apiResponse->statusCode = 500;

		if ( $response ) {
			$jsonResponse = json_decode( $response->getBody() );
			if ( empty( $jsonResponse ) || $jsonResponse === FALSE ) {
				$apiResponse->message = 'Internal Server Error';
			}
			else {
				foreach ( $jsonResponse as $field => $value ) {
					$apiResponse->{$field} = $value;
				}
				$apiResponse->error = false;
				$apiResponse->message = null;
				$apiResponse->statusCode = $response->getStatusCode();
			}
		}

		return $apiResponse;
	}
}
