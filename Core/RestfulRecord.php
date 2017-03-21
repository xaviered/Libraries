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
		getAttributes as IterableAttributes__getAttributes;
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

	/** @var Collection */
	protected $_relationshipsCollection;

	/** @var Collection[] memory cache for collections */
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
		if ( is_object( $attributes ) && ( is_a( $attributes, ContentXUrl::class ) || is_subclass_of( $attributes, ContentXUrl::class ) ) ) {
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
			$attributes[ '__path' ] = '/';
			// precedence for values: slug, attributes, else
			$defaults = [
				'app' => $attributes[ '__app' ] ?? null,
				'type' => $attributes[ 'type' ] ?? null
			];
			$matches = RestfulRecord::parseResourceSlug( $attributes[ 'slug' ] ?? '', $defaults );
			$attributes[ 'slug' ] = array_pop( $matches );
			// record is an app, as type is empty in /{app}/{type}/{resource}
			if ( empty( $matches[ 1 ] ) ) {
				$attributes[ 'slug' ] = $matches[ 0 ];
			}
			else {
				$attributes[ '__path' ] = '/' . implode( '/', $matches );
			}
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
	 * @param bool $withKeys Show keys for Collections
	 * @param bool $hideLink Hide self link in Models
	 * @param bool $hideSelfLinkQuery Don't add query info to self link for Models
	 * @return array
	 */
	public function toApiArray( $withKeys = true, $hideLink = false, $hideSelfLinkQuery = false ) {
		$modelArray = [];
		$modelArray[ 'data' ] = $this->getAttributes();

		if ( !$hideLink ) {
			$modelArray[ 'links' ][ 'self' ] = $this->prepareUrl( $this->slug );
		}

		return $modelArray;
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
	 * @return Collection
	 */
	public function get( $attributes = [] ) {
		return $this->_find(
			$attributes,
			function( $record, $creatorAttributes ) {
				$record = [ $record ];
				Common::array_walk_recursive( $record, function( &$value ) {
					if ( is_object( $value ) && isset( $value->data ) && isset( $value->links->self ) ) {
						$value->data->apiUrl = $value->links->self;
						$value = $value->data;
					}
				} );
				$attributes = array_merge( $creatorAttributes, (array)reset( $record ) );

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
	 * Loads from slug
	 *
	 * @param bool $force
	 * @return $this Chainnable method
	 */
	public function load( $force = false ) {
		if ( !empty( $this->slug ) && ( $force || !$this->_loaded ) ) {
			$record = $this;
			static::_findOne(
				$this->slug,
				function( $recordData ) use ( $record ) {
					return $record->setAttributes( $recordData->data )->setLoaded( true );
				}
			);

			if ( !$record->isLoaded() ) {
				$this->___error = $this->getLastResponse();
			}
		}

		return $this;
	}

	public function loadRelationships() {
		$this->load();

		if ( empty( $this->_relationshipsCollection ) ) {
			$this->_relationshipsCollection = new Collection();
			if ( $this->relationships ) {
				// @todo: Revise if it doesn't overlap with functionality
				foreach ( $this->relationships as $rKey => $rData ) {
					$this->_relationshipsCollection->set( $rKey, $this->relationships[ $rKey ] );
				}
			}
		}
	}

	/**
	 * Gets all attributes
	 *
	 * @return mixed
	 */
	public function getAttributes() {
		$this->load();

		return $this->IterableAttributes__getAttributes();
	}

	/**
	 * @return Collection
	 */
	public function getRelationships() {
		$this->loadRelationships();

		return $this->_relationshipsCollection;
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
	public static function parseResourceSlug( $path, $defaults = [ 'app' => null, 'type' => null ] ) {
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
		unset( $attributes[ 'apiUrl' ] );
		if ( array_key_exists( 'slug', $attributes ) && $attributes[ 'slug' ] === null ) {
			unset( $attributes[ 'slug' ] );
		}

		return $attributes;
	}

	/**
	 * Gets the first record with the given criteria
	 *
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @param callable $createFunction Signature: function(\StdClass $record, array $creatorAttributes) : static
	 * @return static
	 */
	protected function _findOne( $attributes = [], $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		if ( is_array( $attributes ) ) {
			$attributes[ 'page_size' ] = 1;
		}

		return $this->_find( $attributes, $createFunction )->rewind();
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string|array|object|XUrl $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @param callable $createFunction Signature: function(\StdClass $record, array $creatorAttributes) : static
	 * @return Collection
	 */
	protected function _find( $attributes = [], $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		// get original attributes from builder
		$fixedAttributes = $this->getFixedAttributes();
		// prepare attributes
		$attributes = array_merge( $fixedAttributes, RestfulRecord::fixAttributes( $attributes ) );

		$cachedKey = serialize( array_merge( $fixedAttributes, $attributes ) );
		if ( !isset( static::$_collections[ $cachedKey ] ) ) {
			$col = new Collection();

			$attributes = RestfulRecord::cleanAttributes( $attributes );
			$response = $this->indexRequest( $attributes );
			if ( !$response->error ) {
				foreach ( $response->data as $recordData ) {
					if ( $createFunction ) {
						$recordData = call_user_func_array( $createFunction, [ $recordData, $fixedAttributes ] );
					}
					$col->append( $recordData );
				}
			}

			static::$_collections[ $cachedKey ] = $col;
		}

		return static::$_collections[ $cachedKey ];
	}
}
