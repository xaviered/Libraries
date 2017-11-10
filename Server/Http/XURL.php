<?php
namespace ixavier\Libraries\Server\Http;

use Illuminate\Support\Str;
use ixavier\Libraries\Server\Core\Common;
use ixavier\Libraries\Server\Core\RestfulRecord;
use Symfony\Component\HttpFoundation\ParameterBag;

// @todo: Add a serialize method to spit out full URL
/**
 * Class XURL
 *
 * @package ixavier\Libraries\Http
 */
class XURL
{
	/** @var string $service Name of the service or site */
	public $service;

	/** @var int $version Version of service determined by URL */
	public $version;

	/** @var string Slug of requested resource */
	public $requestedResource;

	/** @var string $url Reconstructed URL */
	public $url;

	/** @var string $serviceUrl Reconstructed internal entry URL */
	public $serviceUrl;

	/** @var string $apiUrl The URL to make requests to the service */
	public $apiUrl;

	/** @var string $originalUrl Original URL given */
	public $originalUrl;

	/** @var string $request Path + query + hash */
	public $request;

	public $scheme;
	public $host;
	public $port;
	public $path;
	public $query;
	public $fragment;

	/** @var array Pre-loaded info about all supported services by this app */
	protected static $servicesInfo;

	/**
	 * XURL constructor.
	 *
	 * @param string $url
	 */
	public function __construct( $url = null ) {
		$this->originalUrl = $url;

		$this->prepareServicesInfo()
			->parse()
		;
	}

	/**
	 * Clones to a new object
	 *
	 * @param string $castClass Create new instace of this class instead.
	 *  New class should be a subclass of XURL class
	 * @return XURL
	 */
	public function clone( $castClass = null ) {
		$cloneClass = get_class( $this );
		if ( !empty( $castClass ) && is_subclass_of( $castClass, self::class, true ) ) {
			$cloneClass = $castClass;
		}
		/** @var XURL $clone */
		$clone = new $cloneClass;
		$clone->setProperties( $this->getProperties() );

		return $clone;
	}

	/**
	 * Must have a proper name
	 * @return bool
	 */
	public function isValid() {
		return !empty( $this->service );
	}

	/**
	 * Gets a new ParameterBag from current query
	 * @return ParameterBag
	 */
	public function getQueryParameterBag() {
		$params = [];
		if ( !empty( $this->query ) ) {
			$query = explode( '&', $this->query );

			foreach ( $query as $param ) {
				list( $name, $value ) = explode( '=', $param );
				// @todo: what about ?field[]=one&field[]=two
				$params[ urldecode( $name ) ] = urldecode( $value );
			}
		}

		return new ParameterBag( $params );
	}

	/**
	 * Parses given $url and stores values into this instance
	 *
	 * @param string $url Will use URL given at constructor if empty
	 * @return $this Chainnable method
	 */
	protected function parse( $url = null ) {
		if ( empty( $url ) ) {
			$url = $this->originalUrl;
		}
		if ( is_string( $url ) && !empty( $url ) ) {
			if ( Str::startsWith( $url, 'http' ) ) {
				$purl = $this->parseUrl( $url );
			}
			else if ( preg_match( '|^(' . RestfulRecord::SLUG_REGEX . ')?\://|', $url ) ) {
				$purl = $this->parseXUrl( $url );
			}

			if ( isset( $purl ) ) {
				$purl[ 'path' ] = rtrim( $purl[ 'path' ] ?? '', '/' );
				$this->setProperties( $purl )
					// now build url
					->buildNewUrl()
				;
			}
		}

		return $this;
	}

	/**
	 * Gets the requested resource from an API request.
	 * Overwrite this method if resource is in an endpoint other than the API
	 * i.e.
	 * /api/{v1}/{resource}
	 *
	 * @param string $path
	 * @return mixed
	 */
	protected function parseRequestedResource( $path ) {
		preg_match( "|/api(/v\d+)?(/.*)?|", $path, $matches );

		return $matches[ 2 ] ?? ( isset($matches[ 1 ]) ? '/' : $path );
	}

	/**
	 * Gets version from xurl, config, url
	 * @param string $service
	 * @param string $path
	 * @return int
	 */
	protected function getVersion( $service, $path ) {
		$version = config( 'services.' . $service . '.version' );
		if ( empty( $version ) ) {
			preg_match( "|/api/v(\d+)|", $path, $matches );
			$version = $matches[ 1 ] ?? null;
		}

		$version = intval( $version );

		return $version ? $version : null;
	}

	/**
	 * @param string $host
	 * @param string $path
	 * @param string $url
	 * @return string
	 */
	protected function getRequestPart( $host, $path, $url ) {
		$pos = strpos( $url, $path, strpos( $url, '://' . $host ) );
		$requestPart = rtrim( substr( $url, $pos ), '/' );
		if ( empty( $requestPart ) ) {
			$requestPart = '/';
		}

		return $requestPart;
	}

	/**
	 * Sets object's properties
	 *
	 * @param array $properties New properties; only class properties will be set
	 * @return $this
	 */
	public function setProperties( $properties ) {
		foreach ( get_class_vars( static::class ) as $varName => $varValue ) {
			if ( isset( $properties[ $varName ] ) ) {
				$this->{$varName} = $properties[ $varName ];
			}
		}

		if ( !isset( $this->port ) ) {
			$this->port = ( $this->scheme == 'https' ? 443 : 80 );
		}

		return $this;
	}

	/**
	 * Get object's properties
	 * @return array
	 */
	public function getProperties() {
		$properties = [];
		foreach ( ( new \ReflectionObject( $this ) )->getProperties( \ReflectionProperty::IS_PUBLIC ) as $property ) {
			$properties[ $property->getName() ] = $this->{$property->getName()};
		}

		return $properties;
	}

	/**
	 * @return array
	 */
	public function getRestfulRecordAttributes() {
		return [
			'__url' => $this->url,
			'__path' => dirname( $this->requestedResource ),
			'slug' => basename( $this->requestedResource )
		];
	}

	/**
	 * Builds new URL from parts already assembled by parse()
	 * @return $this Chainnable method
	 */
	protected function buildNewUrl() {
		// @todo: Add in login info
		$firstPart = $this->scheme . '://'
			. $this->host
			. ( $this->port != 80 && $this->port != 443 ? ':' . $this->port : '' );

		// new URL
		$this->url = $firstPart
			. $this->path
			. ( isset( $this->query ) ? '?' . $this->query : '' )
			. ( isset( $this->fragment ) ? '#' . $this->fragment : '' );

		// service URL
		if ( $this->requestedResource == '/' ) {
			$this->apiUrl = $this->url;
		}
		else {
//			preg_match( "|(/api(?=/v?\d+)?)(?=/.*)?|", $this->path, $matches );
			$path = substr( $this->path, 0, strpos( $this->path, $this->requestedResource ) );
			$this->apiUrl = rtrim( $firstPart . $path, '/' );
		}

		return $this;
	}

	/**
	 * Parses a regular URL
	 *
	 * @param string $url
	 * @return array
	 */
	protected function parseUrl( $url ) {
		$purl = parse_url( $url ) ?? [];
		if ( !empty( $purl[ 'host' ] ) ) {
			$serviceInfo = static::getServiceInfoByUrl( $url );
			if ( $serviceInfo ) {
				$purl[ 'service' ] = $serviceInfo[ 'name' ];
			}
			else {
				$purl[ 'service' ] = '';
			}

			// fix request
			$purl[ 'request' ] = $this->getRequestPart( $purl[ 'host' ], $purl[ 'path' ], $url );
		}
		// its most likely a local service
		else {
			$purl[ 'service' ] = config( 'app.serviceName' );
		}

		// fix requested resource from API request
		if ( !empty( $purl[ 'path' ] ) ) {
			$purl[ 'requestedResource' ] = $this->parseRequestedResource( $purl[ 'path' ] );
		}

		// fix version
		if ( empty( $purl[ 'version' ] ) ) {
			$purl[ 'version' ] = $this->getVersion( $purl[ 'service' ], $purl[ 'path' ] );
		}

		if ( empty( $purl[ 'serviceUrl' ] ) ) {
			$purl[ 'serviceUrl' ] = $this->getServiceUrl( $purl );
		}

		return $purl;

	}

	/**
	 * Parses an iXavier URL
	 *
	 * @param string $url
	 * @return array
	 */
	protected function parseXUrl( $url ) {
		// RegExs ...
		// name = service = site = /([a-z][a-z0-9\_\-]+)?/i
		// version = /(\d+)?/
		// domain = /([a-z][a-z0-9\_\-]{2}[a-z0-9\_\-\.]*)?/i
		// apiRequest = |/.*|
		//

		// DB entry XURL translates from...
		// internal global = {service|site}:{version?}//{domain}{apiRequest}
		// internal local = {service|site}:{version?}//{apiRequest}
		// internal same service = :{version?}//{apiRequest}

		// translates to..
		// external global = //{service|site}.{domain}/api/{version?}/{apiRequest}
		// external cdn = //{serviceDomain|siteDomain}/api/{version?}/{apiRequest}

		// i.e.
		// from...
		// content:{version}//ixavier.com/{app}/{slug}
		// content:{version}//ixavier.com/{app}/{type}/{app}
		// content:{version}//ixavier.com/{app}/{type}/{app}/{slug}

		// to...
		// external global = //content.ixavier.com/api/{version}/app/{slug}
		// external global = //content.ixavier.com/api/{version}/{type}/{app}
		// external global = //content.ixavier.com/api/{version}/{type}/{app}/{slug}

		preg_match( '|([a-z][a-z0-9\_\-]+)?\:(?=v?(\d+))?//([a-z][a-z0-9\_\-]{2}[a-z0-9\_\-\.]*)?(/.*)|', $url, $matches );

		// use default service if no service provided
		$pxurl[ 'service' ] = !empty( $matches[ 1 ] ) ? $matches[ 1 ] : config( 'app.serviceName' );
		$pxurl[ 'version' ] = intval( $matches[ 2 ] ?? null );
		$pxurl[ 'domain' ] = $matches[ 3 ] ?? null;
		$pxurl[ 'request' ] = !empty( $matches[ 4 ] ) ? $matches[ 4 ] : '/';

		// now merge with service
		$serviceInfo = static::getServiceInfo( $pxurl[ 'service' ] );
		if ( $serviceInfo ) {
			$surl = $serviceInfo[ 'url' ];
			$surl = rtrim( $surl, '/' ) . $pxurl[ 'request' ];
			$psurl = parse_url( $surl );
			if ( $psurl[ 'path' ] != '/' ) {
				$psurl[ 'path' ] = rtrim( $psurl[ 'path' ], '/' );
			}

			// fix host
			if ( !empty( $pxurl[ 'domain' ] ) ) {
				$psurl[ 'host' ] = $pxurl[ 'service' ] . '.' . $pxurl[ 'domain' ];
			}

			// get rid of extra info
			unset( $pxurl[ 'domain' ] );

			$pxurl = array_merge( $psurl, $pxurl );

			// fix request
			$pxurl[ 'request' ] = $this->getRequestPart( $pxurl[ 'host' ], $pxurl[ 'path' ], $surl );
		}

		// fix requested resource from API request
		if ( !empty( $pxurl[ 'path' ] ) ) {
			$pxurl[ 'requestedResource' ] = $this->parseRequestedResource( $pxurl[ 'path' ] );
		}

		// fix version
		if ( empty( $pxurl[ 'version' ] ) ) {
			$pxurl[ 'version' ] = $this->getVersion( $pxurl[ 'service' ], $pxurl[ 'path' ] ?? '/' );
		}

		if ( empty( $pxurl[ 'serviceUrl' ] ) ) {
			$pxurl[ 'serviceUrl' ] = $url;
		}

		return $pxurl;
	}

	/**
	 * Helper method to create new instance of static
	 * Based on $uri, will crete new instaces of subclasses of this
	 *
	 * @param string $uri
	 * @return XURL
	 */
	public static function create( $uri ) {
		$tmp = new static( $uri );

		if ( $tmp->isValid() ) {
			if ( !empty( $tmp->service ) ) {
				$serviceInfo = static::getServiceInfo( $tmp->service );
				$className = $serviceInfo[ 'url_scheme' ] ?? Common::getClassNamespace( self::class ) . '\\' . ucfirst( $tmp->service ) . 'XURL';
				if ( class_exists( $className ) ) {
					$tmp = $tmp->clone( $className );
				}
			}
		}

		return $tmp;
	}

	/**
	 * @param RestfulRecord $record
	 * @return XURL
	 */
	public static function createFromRecord( $record ) {
		$url = $record->getUrlBase()
			. ( $record->getPath() ? rtrim( $record->getPath(), '/' ) . '/' : '' )
			. ( $record->slug ?? '' );

		return static::create( $url );
	}

	/**
	 * Given an array from self::parseUrl(), will get record string to save in data-store.
	 * @param array $purl
	 * @return null|string
	 */
	protected function getServiceUrl( $purl ) {
		$serviceInfo = static::getServiceInfo( $purl[ 'service' ] );

		if ( $serviceInfo ) {
			return $serviceInfo[ 'name' ] . ':' . $purl[ 'version' ] . '//'
				. $purl[ 'requestedResource' ];
		}

		return null;
	}

	/**
	 * Pre-loads all information about supported services by this app
	 * @return $this Chainnable method
	 */
	private function prepareServicesInfo() {
		if ( !isset( static::$servicesInfo ) ) {
			static::$servicesInfo = [];
			$servicesInfo = config( 'services' ) ?? [];
			foreach ( $servicesInfo as $serviceName => $serviceInfo ) {
				if ( isset( $serviceInfo[ 'url' ] ) ) {
					static::$servicesInfo[ $serviceName ] = strtolower( parse_url( $serviceInfo[ 'url' ], PHP_URL_HOST ) );
				}
			}
		}

		return $this;
	}

	/**
	 * @param string $serviceName Name of the service
	 * @return array|null Returns null if not a supported service by this app
	 */
	protected static function getServiceInfo( $serviceName ) {
		if ( array_key_exists( $serviceName, static::$servicesInfo ) ) {
			return array_merge( [ 'name' => $serviceName ], config( 'services.' . $serviceName ) ?? [] );
		}

		return null;
	}

	/**
	 * @param string $url URL of the service
	 * @return array|null Returns null if not a supported service by this app
	 */
	protected static function getServiceInfoByUrl( $url ) {
		$serviceName = array_search( strtolower( parse_url( $url, PHP_URL_HOST ) ), static::$servicesInfo );
		if ( $serviceName ) {
			return array_merge( [ 'name' => $serviceName ], config( 'services.' . $serviceName ) ?? [] );
		}

		return null;
	}
}
