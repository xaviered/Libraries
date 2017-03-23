<?php
namespace ixavier\Libraries\Core;

use GuzzleHttp\Psr7\Response;
use ixavier\Libraries\Http\ContentXUrl;
use ixavier\Libraries\Http\XUrl;
use ixavier\Libraries\Requests\ContentHouseApiRequest;

// @todo: Add ability to do multiple requests at the same time, by using $this->newQuery()->where($attributes)->get()
// For immediate calls, we can also do $this->newQuery()->get($attributes)
//
/**
 * Class RestfulRecord
 *
 * @package ixavier\Libraries\Core
 *
 * @internal string $slug
 */
class RestfulRecord extends ContentHouseApiRequest
{
	use IterableAttributes {
		setAttributes as IterableAttributes__setAttributes;
	}

	/** @var string RegEx to use on slugs */
	CONST SLUG_REGEX = '[A-Za-z][A-Za-z0-9\-_]*';

	/** @var string RegEx to use on Resource types */
	CONST RESOURCE_TYPE_REGEX = '[a-z][a-z0-9]+';

	/** @var Response Error Response if bad response from API server */
	public $___error;

	/** @var bool True if already loaded */
	protected $_loaded = false;

	/** @var ModelCollection[] memory cache for collections */
	protected static $_collections = [];

	/** @var array|XUrl|string */
	private $__fixedAttributes;

	/**
	 * RestfulRecord constructor.
	 *
	 * @param array|object|ContentXUrl $attributes Pre-populate with attributes
	 * Attributes can be:
	 * __url    Base URL to make the request
	 * __path   Path to make requests
	 * type     Type of object
	 * slug     Slug of resource.
	 *          Can also be full request, therefore `__path`, and `type` will be determined based on this.
	 */
	public function __construct( $attributes = [] ) {
		parent::__construct();
		$this->setAttributes( $attributes );
	}

	/**
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @return $this
	 */
	public static function query( $attributes = [] ) {
		return ( new static( $attributes ) )->newQuery();
	}

	/**
	 * Gets new query
	 * @return $this
	 */
	public function newQuery() {
		return $this;
	}

	/**
	 * @todo: Need to implement this so it prepares for multiple requests
	 * @param array $attributes
	 * @return $this
	 */
	public function where( $attributes = [] ) {
		return $this;
	}

	/**
	 * Given a path as /{app}/{type}/{slug} will return an array with [$app, $type, $slug]
	 * Not all parts need to be in the $path, and they will be null if they are not there.
	 *
	 * @param string $path
	 * @return array
	 */
	public static function fixAttributesFromPath( $path, $keepSlug = false ) {
		$attributes = [];
		$matches = RestfulRecord::parseResourceSlug( $path, [], true );
		// resource request
		if ( !empty( $matches[ 2 ] ) ) {
			$attributes[ 'slug' ] = $keepSlug ? $matches[ 2 ] : null;
			$attributes[ 'type' ] = $matches[ 1 ];
			$attributes[ '__path' ] = '/' . implode( '/', $matches );
		}
		// list items of given type request
		else if ( !empty( $matches[ 1 ] ) ) {
			$attributes[ 'slug' ] = null;
			array_pop( $matches );
			$attributes[ 'type' ] = $matches[ 1 ];
			$attributes[ '__path' ] = '/' . implode( '/', $matches );
		}
		// app request
		else if ( !empty( $matches[ 0 ] ) ) {
			$attributes[ 'slug' ] = $keepSlug ? $matches[ 0 ] : null;
			$attributes[ '__path' ] = '/';
			$attributes[ 'type' ] = null;
		}
		// list all apps request
		else {
			$attributes[ '__path' ] = '';
		}

		return $attributes;
	}

	/**
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes
	 * String will be slug
	 *
	 * Array|object can have:
	 * __url    Base URL to make the request
	 * __path   Path to make requests
	 * type     Type of object
	 * slug     Slug of resource.
	 *          Can also be full request, therefore `__path`, and `type` will be determined based on this.
	 *
	 * @return array Fixed attributes
	 */
	public static function fixAttributes( $attributes ) {
		if ( is_object( $attributes ) && ( $attributes instanceof XUrl ) ) {
			$attributes = $attributes->getRestfulRecordAttributes();
		}
		else if ( is_object( $attributes ) ) {
			$attributes = get_object_vars( $attributes );
		}
		else if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}
		else if ( !is_array( $attributes ) ) {
			$attributes = [];
		}

		// get path from slug
		if ( empty( $attributes[ '__path' ] ) ) {
			$fixedAttributes = static::fixAttributesFromPath( $attributes[ 'slug' ] ?? '' );
			$attributes = array_merge( $attributes, $fixedAttributes );
		}

		// this is a resource, update path!
		if ( isset( $attributes[ 'data' ] ) && isset( $attributes[ 'data' ]->slug ) && count( explode( '/', trim( $attributes[ '__path' ], '/' ) ) ) != 3 ) {
			$attributes[ '__path' ] .= '/' . $attributes[ 'data' ]->slug;
		}

		$attributes[ '__url' ] = $attributes[ '__url' ] ?? null;

		return $attributes;
	}

	/**
	 * Cleans attributes before adding them
	 *
	 * @param array $attributes
	 * @return $this Chainnable method
	 */
	public function setAttributes( $attributes ) {
		$oa = $attributes;
		$this->__fixedAttributes = $attributes = static::fixAttributes( $attributes );

		$this->setUrlBase( $attributes[ '__url' ] );
		$this->setPath( $attributes[ '__path' ] );
		$attributes = static::cleanAttributes( $attributes );

		return $this->IterableAttributes__setAttributes( $attributes );
	}

	/**
	 * @return array|XUrl|string
	 */
	public function getFixedAttributes() {
		return $this->__fixedAttributes;
	}

	/**
	 * API array representation of this model
	 *
	 * @param int $relationsDepth Current depth of relations loaded. Default = 1
	 * @param bool $hideSelfLinkQuery Don't add query info to self link for Models
	 * @return array
	 */
	public function toApiArray( $relationsDepth = 0, $hideSelfLinkQuery = false ) {
		// @todo: Handle loading relations
		return $this->getAttributes();
	}

	/**
	 * Helper method for constructor
	 *
	 * @param array $fields
	 * @return static
	 */
	public static function create( $fields = null ) {
		// @todo: don't call static, use overwritten classes for each type
		return ( new static( $fields ) )->setLoaded( true );
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @return ModelCollection
	 */
	public function get( $attributes = [] ) {
		return $this->_find(
			$attributes,
			function( $record, $creatorAttributes ) {
				$attributes = array_merge( $creatorAttributes, (array)$record );

				return static::create( $attributes );
			}
		);
	}

	/**
	 * Same as get(), but will get first item
	 *
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @return RestfulRecord
	 */
	public function find( $attributes = [] ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		if ( is_array( $attributes ) ) {
			$attributes[ 'page_size' ] = 1;
		}

		// @todo: Fix arguments once `where` is working
		return $this->where( $attributes )->get( $attributes )->first();
	}

	/**
	 * @return bool
	 */
	public function isLoaded() {
		return $this->_loaded;
	}

	/**
	 * @param bool $loaded
	 * @return $this Chainnable method
	 */
	public function setLoaded( bool $loaded ) {
		$this->_loaded = $loaded;

		return $this;
	}

	/**
	 * @return ModelCollection
	 */
	public function getRelationships() {
		return new ModelCollection( $this->relations ?? [] );
	}

	public function __toString() {
		return $this->getPath() . ': ' . json_encode( $this->getAttributes() );
	}

	/**
	 * Given Resource path, will parse out its app, type and slug
	 *
	 * @param string $path
	 * @param array $defaults Array with default values to return if nothing found
	 * @return array i.e.
	 * [
	 * 0 => slug of app,
	 * 1 => type of resource,
	 * 2 => true slug of resource
	 * ]
	 */
	public static function parseResourceSlug( $path, $defaults = [ 'app' => null, 'type' => null ], $onlySlugs = false ) {
		// A good URL will not spaces
		$path = trim( $path );

		// no app, type, or resource
		if ( $path == '/' ) {
			$path = null;
		}

		if ( strpos( $path, '/' ) !== FALSE ) {
			$regex = sprintf(
				"|(?=/(%s)(?=/(%s)(?=/(%s))?)?)?|",
				RestfulRecord::SLUG_REGEX,
				RestfulRecord::RESOURCE_TYPE_REGEX,
				RestfulRecord::SLUG_REGEX
			);
			preg_match( $regex, $path, $matches );
			$matches = [
				'app' => $matches[ 1 ] ?? null,
				'type' => $matches[ 2 ] ?? null,
				'resource' => $matches[ 3 ] ?? ( isset( $matches[ 1 ] ) ? null : $path ),
			];
		}
		else {
			$matches = [ 'resource' => $path ];
		}

		if ( $onlySlugs ) {
			array_walk( $matches, function( &$ele ) {
				if ( $ele instanceof RestfulRecord ) {
					$ele = $ele->slug ?? '';
				}
			} );
		}

		return array_values( array_merge( $defaults, $matches ) );
	}

	/**
	 * Cleans runtime vars from real attributes
	 *
	 * @param array $attributes
	 * @return array
	 */
	public static function cleanAttributes( array $attributes ) {
		unset( $attributes[ '__app' ] );
		unset( $attributes[ '__path' ] );
		unset( $attributes[ '__url' ] );
		if ( array_key_exists( 'slug', $attributes ) && $attributes[ 'slug' ] === null ) {
			unset( $attributes[ 'slug' ] );
		}

		return $attributes;
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @param callable $createFunction Signature: function(\StdClass $record, array $creatorAttributes) : static
	 * @return ModelCollection
	 */
	protected function _find( $attributes = [], $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		// get original attributes from builder
		$fixedAttributes = $this->getFixedAttributes();
		// prepare attributes
		$attributesForCacheKey = array_merge( $fixedAttributes, RestfulRecord::fixAttributes( $attributes ) );

		$cachedKey = serialize( $attributesForCacheKey ) . uniqid();
		if ( !isset( static::$_collections[ $cachedKey ] ) ) {
			$col = new ModelCollection();

			$attributes = RestfulRecord::cleanAttributes( $attributes );
			$parts = static::parseResourceSlug( $fixedAttributes[ '__path' ] );

			// get single resource record
			if ( !empty( $parts[ 2 ] ) ) {
				$methodName = 'showRequest';
				$methodArgs = [ '', $attributes ];
			}
			// get list of given type
			else if ( !empty( $parts[ 1 ] ) ) {
				$methodName = 'indexRequest';
				$methodArgs = [ $attributes ];
			}
			// get app resource record
			else if ( !empty( $parts[ 0 ] ) ) {
				$methodName = 'showRequest';
				$methodArgs = [ '', $attributes ];
			}
			// get list of apps
			else {
				$methodName = 'indexRequest';
				$methodArgs = [ $attributes ];
			}

			$response = $this->{$methodName}( ...$methodArgs );

			if ( !$response->error ) {
				if ( $methodName == 'showRequest' ) {
					$recordData = $response->data;
					if ( $createFunction ) {
						$recordData = call_user_func_array( $createFunction, [ $recordData, $fixedAttributes ] );
					}

					$col->push( $recordData );
				}
				else if ( isset( $response->data->data ) ) {
					foreach ( $response->data->data as $recordData ) {
						if ( $createFunction ) {
							$recordData = call_user_func_array( $createFunction, [ $recordData, $fixedAttributes ] );
						}
						$col->push( $recordData );
					}
				}
			}
			else {
				$tmp = new RestfulRecord( $attributes );
				$tmp->___error = $response->statusCode;
				$tmp->message = $response->message;
				$col->push( $tmp );
			}

			static::$_collections[ $cachedKey ] = $col;
		}

		return static::$_collections[ $cachedKey ];
	}
}
