<?php
namespace ixavier\Libraries\Http;

use App\Http\Request;
use Illuminate\Support\Str;
use ixavier\Libraries\Core\Common;

/**
 * Class XUrl
 *
 * @package ixavier\Libraries\Http
 */
class XUrl
{
	/** @var string $name of service or site */
	public $name;
	public $scheme;
	public $host;
	public $port;
	public $path;
	public $query;
	public $fragment;
	public $version;
	public $request;
	/** @var string Slug of requested resource */
	public $requestedResource;
	/** @var string $url Reconstructed URL */
	public $url;
	public $originalUrl;

	/**
	 * XURL constructor.
	 * @param string $uri
	 */
	public function __construct( $url = null ) {
		$this->originalUrl = $url;

		$this->parse( $this->originalUrl );
	}

	/**
	 * Clones to a new object
	 *
	 * @param string $castClass Create new instace of this class instead.
	 *  New class should be a subclass of XUrl class
	 * @return XUrl
	 */
	public function clone( $castClass = null ) {
		$cloneClass = get_class( $this );
		if ( !empty( $castClass ) && is_subclass_of( $castClass, self::class, true ) ) {
			$cloneClass = $castClass;
		}
		/** @var self $clone */
		$clone = new $cloneClass;
		$clone->setProperties( $this->getProperties() );

		return $clone;
	}

	/**
	 * Must have a proper name
	 * @return bool
	 */
	public function isValid() {
		return !empty( $this->name );
	}

	/**
	 * Parses given $url and stores values into this instance
	 *
	 * @param string $url
	 * @return $this Chainnable method
	 */
	protected function parse( $url ) {
		if ( is_string( $url ) ) {
			if ( Str::startsWith( $url, 'http' ) ) {
				$purl = $this->parseUrl( $url );
			}
			else if ( Str::contains( $url, '://' ) ) {
				$purl = $this->parseXUrl( $url );
			}

			if ( isset( $purl ) ) {
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
		preg_match( "|/api(?=/v?\d+)?(/.+)?|", $path, $matches );

		return $matches[ 1 ] ?? $path;
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
	protected function getProperties() {
		$properties = [];
		foreach ( ( new \ReflectionObject( $this ) )->getProperties( \ReflectionProperty::IS_PUBLIC ) as $property ) {
			$properties[ $property->getName() ] = $this->{$property->getName()};
		}

		return $properties;
	}

	/**
	 * Builds new URL from parts already assembled by parse()
	 * @return $this Chainnable method
	 */
	protected function buildNewUrl() {
		$this->url = $this->scheme . '://'
			. $this->host
			. ( $this->port != 80 && $this->port != 443 ? ':' . $this->port : '' )
			// @todo: Add in login info
			. $this->path
			. ( isset( $this->query ) ? '?' . $this->query : '' )
			. ( isset( $this->fragment ) ? '#' . $this->fragment : '' );

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
			preg_match( '|([^\.]+)(?=\..+)?|', $purl[ 'host' ], $matches );
			if ( !empty( $matches[ 1 ] ) ) {
				$purl[ 'name' ] = $matches[ 1 ];
			}

			$pos = strpos( $url, $purl[ 'path' ], strpos( $url, '://' . $purl[ 'host' ] ) );
			$purl[ 'request' ] = substr( $url, $pos );
		}

		// fix name
		if ( empty( $purl[ 'name' ] ) ) {
			$purl[ 'name' ] = config( 'app.schemeName' );
		}

		// fix other things with services
		if ( !empty( $purl[ 'name' ] ) && ( $surl = config( 'services.' . $purl[ 'name' ] . '.url' ) ) ) {
			// @todo: Get version from path first
			// fix version
			if ( empty( $purl[ 'version' ] ) ) {
				$purl[ 'version' ] = config( 'services.' . $purl[ 'name' ] . '.version' ) ?? 1;
			}
		}

		// fix requested resource from API request
		if ( !empty( $purl[ 'path' ] ) ) {
			$purl[ 'requestedResource' ] = $this->parseRequestedResource( $purl[ 'path' ] );
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
		// contenthouse:{version}//ixavier.com/app/{slug}
		// contenthouse:{version}//ixavier.com/{type}/{app}
		// contenthouse:{version}//ixavier.com/{type}/{app}/{slug}

		// to...
		// external global = //contenthouse.ixavier.com/api/{version}/app/{slug}
		// external global = //contenthouse.ixavier.com/api/{version}/{type}/{app}
		// external global = //contenthouse.ixavier.com/api/{version}/{type}/{app}/{slug}

		preg_match( '|([a-z][a-z0-9\_\-]+)?\:(?=v?(\d+))?//([a-z][a-z0-9\_\-]{2}[a-z0-9\_\-\.]*)?(/.*)|', $url, $matches );

		// use default service if no service provided
		$pxurl[ 'name' ] = !empty( $matches[ 1 ] ) ? $matches[ 1 ] : config( 'app.schemeName' );
		$pxurl[ 'version' ] = $matches[ 2 ] ?? null;
		$pxurl[ 'domain' ] = $matches[ 3 ] ?? null;
		$pxurl[ 'request' ] = !empty( $matches[ 4 ] ) ? $matches[ 4 ] : '';

		// now merge with service
		if ( !empty( $pxurl[ 'name' ] ) && ( $surl = config( 'services.' . $pxurl[ 'name' ] . '.url' ) ) ) {
			$surl .= $pxurl[ 'request' ];
			$psurl = parse_url( $surl );

			// fix host
			if ( !empty( $pxurl[ 'domain' ] ) ) {
				$psurl[ 'host' ] = $pxurl[ 'name' ] . '.' . $pxurl[ 'domain' ];
			}

			// fix version
			if ( empty( $pxurl[ 'version' ] ) ) {
				$pxurl[ 'version' ] = config( 'services.' . $pxurl[ 'name' ] . '.version' ) ?? 1;
			}

			// get rid of extra info
			unset( $pxurl[ 'domain' ] );

			$pxurl = array_merge( $psurl, $pxurl );
		}

		// fix requested resource from API request
		if ( !empty( $pxurl[ 'path' ] ) ) {
			$pxurl[ 'requestedResource' ] = $this->parseRequestedResource( $pxurl[ 'path' ] );
		}

		return $pxurl;
	}

	/**
	 * Helper method to create new instance of static
	 * Based on $uri, will crete new instaces of subclasses of this
	 *
	 * @param string $uri
	 * @return static
	 */
	public static function create( $uri ) {
		$tmp = new static( $uri );

		if ( $tmp->isValid() ) {
			if ( !empty( $tmp->name ) ) {
				$className = config( 'services.' . $tmp->name . '.url_scheme' );
				if ( empty( $className ) ) {
					$className = Common::getClassNamespace( self::class ) . '\\' . ucfirst( $tmp->name ) . 'XUrl';
				}

				if ( class_exists( $className ) ) {
					$tmp = $tmp->clone( $className );
				}
			}
		}

		return $tmp;
	}
}
