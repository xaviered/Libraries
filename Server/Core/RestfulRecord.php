<?php

namespace ixavier\Libraries\Server\Core;

use GuzzleHttp\Psr7\Response;
use ixavier\Libraries\Server\Http\ContentXURL;
use ixavier\Libraries\Server\Http\XURL;
use ixavier\Libraries\Server\Requests\ApiResponse;
use ixavier\Libraries\Server\Requests\ContentHouseApiRequest;
use ixavier\Libraries\Server\RestfulRecords\App;

// @todo: Add ability to do multiple requests at the same time, by using $this->newQuery()->where($attributes)->get()
// For immediate calls, we can also do $this->newQuery()->get($attributes)
//

/**
 * Class RestfulRecord represents one record of a given type
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
	CONST RESOURCE_TYPE_REGEX = '[A-Za-z][A-Za-z0-9]+';

	/** @var array A list of links related to this record, including 'self' */
	public $links;

	/** @var ModelCollection Related content */
	public $relations;

	/** @var array Special runtime attributes */
	public static $specialAttributes = [ '__app', '__path', '__url', ];

	/** @var App $app */
	protected $app;

	/** @var bool Indicates if the model exists. */
	protected $_exists = false;

	/** @var Response Error Response if bad response from API server */
	protected $_error;

	/** @var bool True if already loaded */
	protected $_loaded = false;

	/** @var ModelCollection[] memory cache for collections */
	protected static $_collections = [];

	/** @var array|XURL|string */
	private $__fixedAttributes;

	/**
	 * RestfulRecord constructor.
	 *
	 * @param array|object|ContentXURL $attributes Pre-populate with attributes
	 * Attributes can be:
	 * __app    App that this resource belongs to, only if instance is not an App itself
	 * __url    Base URL to make the request
	 * __path   Path to make requests
	 * type     Type of object
	 * slug     Slug of resource.
	 *          Can also be full request, therefore `__path`, and `type` will be determined based on this.
	 */
	public function __construct( $attributes = [] ) {
		$this->relations = new ModelCollection();

		parent::__construct();
		$this->setAttributes( $attributes );
	}

	/**
	 * @param string|array|object|XURL $attributes Pre-populate with attributes. @see self::fixAttributes()
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
			$attributes[ 'type' ] = null;
			$attributes[ '__path' ] = '/' . $matches[ 0 ];
		}
		// list all apps request
		else {
			$attributes[ '__path' ] = '';
		}

		return $attributes;
	}

	/**
	 * @param string|array|object|XURL $attributes Pre-populate with attributes
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
		if ( is_object( $attributes ) && ( $attributes instanceof XURL ) ) {
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
		if ( empty( $attributes[ '__path' ] ) && !empty( $attributes[ 'slug' ] ) && strpos( $attributes[ 'slug' ], '/' ) !== false ) {
			$fixedAttributes = static::fixAttributesFromPath( $attributes[ 'slug' ] ?? '' );
			$attributes = array_merge( $attributes, $fixedAttributes );
		}

		// this is a resource, update path!
		if ( isset( $attributes[ 'data' ] ) && isset( $attributes[ 'data' ]->slug ) && count( explode( '/', trim( $attributes[ '__path' ] ?? '', '/' ) ) ) != 3 ) {
			$attributes[ '__path' ] .= '/' . $attributes[ 'data' ]->slug;
		}

		$attributes[ '__url' ] = $attributes[ '__url' ] ?? null;

		return $attributes;
	}

	/**
	 * Cleans attributes before adding them
	 *
	 * @param array $attributes
	 * @param bool $merge
	 * @return $this Chainnable method
	 */
	public function setAttributes( $attributes, $merge = false ) {
		$attributes = $this->setFixedAttributes( $attributes );
		$attributes = static::cleanAttributes( $attributes );

		return $this->IterableAttributes__setAttributes( $attributes, $merge );
	}

	/**
	 * Set runtime vars from $attributes
	 *
	 * @param array $attributes
	 * @return array
	 */
	protected function setFixedAttributes( $attributes ) {
		$attributes = static::fixAttributes( $attributes );

		if ( !empty( $attributes[ '__app' ] ) ) {
			$this->setApp( $attributes[ '__app' ] );

			if ( $attributes[ '__app' ] instanceof RestfulRecord ) {
				$attributes[ '__app' ] = $attributes[ '__app' ]->getAttributes();
			}

			// fix path
			if ( $this->getApp() && isset( $attributes[ 'type' ] ) && empty( $attributes[ '__path' ] ) ) {
				$attributes[ '__path' ] = $this->getPath() . '/' . $attributes[ 'type' ];
			}
		}

		// save only special attributes
		$specialAttributes = array_combine( static::$specialAttributes, static::$specialAttributes );
		$this->__fixedAttributes = array_intersect_key( $attributes, $specialAttributes );

        if (isset($attributes['__url'])) {
            $this->setUrlBase($attributes['__url'] ?? '');
        }
        if (isset($attributes['__path'])) {
            $this->setPath($attributes['__path'] ?? '/');
        }

		return $attributes;
	}

	/**
	 * @return array|XURL|string
	 */
	public function getFixedAttributes() {
		return $this->__fixedAttributes;
	}

	/**
	 * Gets all attributes; fixed and normal
	 * @param bool $includeRelations If true, will include loaded relations
	 * @return array
	 */
	public function getAllAttributes( $includeRelations = false ) {
		return array_merge(
			$this->getFixedAttributes(),
			static::fixAttributes( $this->getAttributes() ),
			$includeRelations ? $this->relations->all() : []
		);
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
		$attributes = $this->getAttributes();

		return [
			'data' => $attributes,
			'relations' => $this->relations,
			'links' => $this->links
		];
	}

	/**
	 * Helper method for constructor
	 *
	 * @param array $fields
	 * @param bool $exists If record exists on data store
	 * @return static
	 */
	public static function create( $fields = null, $exists = false ) {
		// @todo: don't call static, use overwritten classes for each type
		return ( new static( $fields ) )
			->setLoaded( true )
			->exists( $exists )
			;
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string|array|object|XURL $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @return ModelCollection
	 */
	public function get( $attributes = [] ) {
		return $this->_find(
			$attributes,
			[ $this, 'createFromApiRecord' ]
		);
	}

	/**
	 * @return App|RestfulRecord
	 */
	public function getApp() {
		return $this->app;
	}

	/**
	 * @param App|RestfulRecord|string $app App or slug of app
	 * @return $this Chainable method.
	 */
	public function setApp( $app ) {
		if ( is_string( $app ) ) {
			$this->app = App::query()->find( $app ) ?? $this->app;
		}
		else if ( !( $this instanceof App ) ) {
			$this->app = $app;
		}

		if ( isset( $this->app ) && isset( $this->app->slug ) ) {
			$this->setPath( rtrim( $app->getPath(), '/' ) . '/' . $app->slug );
		}

		return $this;
	}

	/**
	 * Creates a new RestfulRecord from the data given by the content API
	 *
	 * @param \stdClass $record API response of a single record
	 * @param array $creatorAttributes The attributes sent to API
	 * @return RestfulRecord
	 */
	protected function createFromApiRecord( $record, $creatorAttributes ) {
		// fix data
		if ( isset( $record->data ) ) {
			$record->data = array_merge( $creatorAttributes, (array)$record->data );
		}

		// pre-load all relations
		$relations = new ModelCollection();
		if ( isset( $record->relations ) ) {
			$tmpCreator = new RestfulRecord;
			foreach ( $record->relations as $relationKey => $relation ) {
				// single
				if ( !( $relation instanceof RestfulRecord ) && isset( $relation->data ) ) {
					$relation = $tmpCreator->createFromApiRecord( $relation, $creatorAttributes );
				}
				// multi
				else if ( is_array( $relation ) ) {
					$tmpCol = new ModelCollection();
					foreach ( $relation as $relationKey2 => $relation ) {
						if ( !( $relation instanceof RestfulRecord ) && isset( $relation->data ) ) {
							$tmpCol->put( $relationKey2, $tmpCreator->createFromApiRecord( $relation, $creatorAttributes ) );
						}
					}
					$relation = $tmpCol;
				}

				$relations->put( $relationKey, $relation );
			}
		}

		// create instance
		$tmp = static::create( $record->data ?? [], true );
		$tmp->relations = $relations;
		$tmp->links = $record->links ?? [];

		return $tmp;
	}


	/**
	 * Same as get(), but will get first item
	 *
	 * @param string|array|object|XURL $attributes Pre-populate with attributes. @see self::fixAttributes()
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
     * Save the model to the API
     *
     * @param  array $options Currently available:
     *  - overrideIfExists
     *  - createSlug: Creates a unique slug. If passing a string, will use as prefix.
     * @return bool True if succeeded, false otherwise. Check object's error for details if failed.
     */
    public function save($options = [])
    {
//        print_r($options);
        $options = Common::fixOptions($options, 'overrideIfExists createSlug');

        return $this->__save($options);
    }

	/**
	 * Save the model to the API
	 *
	 * @param  array $options Currently available:
	 *  - ignoreIfExists
	 *  - overrideIfExists
	 *  - createSlug: Creates a unique slug. If passing a string, will use as prefix.
	 * @return bool True if succeeded, false otherwise. Check object's error for details if failed.
	 */
	private function __save( $options = [] ) {
		$options = Common::fixOptions( $options, 'ignoreIfExists overrideIfExists createSlug' );
		$originalAttributes = $this->getAttributes();

		// get original attributes from builder
		$fixedAttributes = $this->getFixedAttributes();
		// prepare attributes
		$attributesForCacheKey = $this->getAllAttributes();
		$attributes = RestfulRecord::cleanAttributes( $attributesForCacheKey );

        // find existing object first
        if (!$options['ignoreIfExists'] && !empty($this->slug) && isset($attributesForCacheKey['type']) ) {
            $record = static::query($attributesForCacheKey)->find($this->slug);
            if ($record && $record->exists()) {
                if ($options['overrideIfExists']) {
                    $record->setAttributes($attributes);

                    return $record->__save('ignoreIfExists');
                }

                return true;
            }
        }

		// send update
		if ( $this->exists() && !empty( $this->slug ) ) {
			$methodName = 'updateRequest';
			$methodArgs = [ $this->slug, $attributes ];
		}
		// send create
		else {
			if ( $options[ 'createSlug' ] ) {
                $attributes[ 'slug' ] = Common::createSlug($options[ 'createSlug' ] ?? null);
			}
			$methodName = 'storeRequest';
			$methodArgs = [ $attributes ];
		}

		// make the request
		$response = $this->{$methodName}( ...$methodArgs );

		/** @var ApiResponse $response */
		if ( !$response->error && $response->data ) {
			$instance = $this->createFromApiRecord( $response->data, $attributesForCacheKey );
			$this->setAttributes( $instance->getAllAttributes() )
                ->exists( true )
            ;

			return true;
		}
		else {
			$response->headers = array_combine( array_keys( $response->headers ), array_flatten( $response->headers ) );
			$this->setError( is_string($response->error) ? $response->error : $response );

			return false;
		}
	}

	/**
	 * Delete the model from the API
	 *
	 * @return bool True if succeeded, false otherwise. Check object's error for details if failed.
	 */
	public function delete() {

		// send update
		if ( empty( $this->slug ) ) {
			$this->setError( [ 'code' => 0, 'message' => 'Cannot delete record with empty slug.' ] );

			return false;
		}

		// make the request
		$response = $this->destroyRequest( $this->slug );

		/** @var ApiResponse $response */
		if ( !$response->error && $response->data ) {
			$this->setAttributes( [] )->exists( false );

			return true;
		}
		else {
			$this->setError( [ 'code' => $response->statusCode, 'message' => $response->message, 'response' => $this->getLastResponse() ] );

			return false;
		}
	}

	/**
	 * Sets/checks for existence of record on data store.
	 *
	 * @param bool $value If bool passed, will set existence to its value.
	 * @return bool|$this If passed bool, will return $this, otherwise will return current existence value.
	 */
	public function exists( $value = null ) {
		if ( is_bool( $value ) ) {
			$this->_exists = $value;

			return $this;
		}

		return $this->_exists;
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
	 * Gets all relations
	 *
	 * @return ModelCollection
	 */
	public function getRelations() {
		return $this->relations;
	}

	/**
	 * Sets all relations
	 *
	 * @param ModelCollection $relations
	 * @return $this Chainnable method
	 */
	public function setRelations( ModelCollection $relations ) {
		$this->relations = $relations;

		return $this;
	}

	/**
	 * Gets relation object on this record.
	 *
	 * @param string $relationKey
	 * @param string $returnInCollectionByKey Will wrap output in ModelCollection with the given column as its main key.
	 * @return ModelCollection|RestfulRecord Empty ModelCollection if no relation found. Use `$this->hasRelation()` to truly find out if there is a relation.
	 */
	public function getRelation( $relationKey, $returnInCollectionByKey = null ) {
		/** @var ModelCollection|RestfulRecord $relation */
		$relation = $this->getRelations()->get( $relationKey );
		if ( $relation && $returnInCollectionByKey && $relation instanceof ModelCollection ) {
			$relation = $relation->keyBy( $returnInCollectionByKey );
		}
		else if ( empty( $relation ) ) {
			$relation = new ModelCollection();
		}

		return $relation;
	}

	/**
	 * Sets relation object and value on this record.
	 *
	 * @param string $relationKey
	 * @param RestfulRecord|ModelCollection $relationValue
	 * @return $this
	 */
	public function setRelation( $relationKey, $relationValue ) {
		$this->relations->offsetSet( $relationKey, $relationValue );
		if ( $relationValue instanceof RestfulRecord ) {
			$this->setAttribute( $relationKey, $relationValue->getXURL()->serviceUrl );
		}
		else if ( $relationValue instanceof ModelCollection ) {
			$this->setAttribute( $relationKey, $relationValue->getXURLs()->pluck( 'serviceUrl' )->all() );
		}

		return $this;
	}

	/**
	 * Removes relation object and value from this record.
	 *
	 * @param string $relationKey
	 * @param bool $deleteRelationRecord If true, will also remove the record itself from datastore
	 * @return ModelCollection|RestfulRecord|null Original relation object. Null if no relation found.
	 */
	public function removeRelation( $relationKey, $deleteRelationRecord = false ) {
		$relation = $this->getRelation( $relationKey );

		$this->relations->offsetUnset( $relationKey );
		unset( $this->{$relationKey} );

		if ( $deleteRelationRecord ) {
			$relation->delete();
		}

		return $relation;
	}

	/**
	 * Checks if there is an existing relation
	 *
	 * @param string $relationKey
	 * @return bool
	 */
	public function hasRelation( $relationKey ) {
		return $this->getRelations()->offsetExists( $relationKey );
	}

	/**
	 * @return XURL
	 */
	public function getXURL() {
		return XURL::createFromRecord( $this );
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
		// A good URL will not have spaces
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
		foreach ( static::$specialAttributes as $specialAttribute ) {
			unset( $attributes[ $specialAttribute ] );
		}
		if ( array_key_exists( 'slug', $attributes ) && $attributes[ 'slug' ] === null ) {
			unset( $attributes[ 'slug' ] );
		}

		return $attributes;
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string|array|object|XURL $attributes Pre-populate with attributes. @see self::fixAttributes()
	 * @param callable $createFunction Signature: function(\StdClass $record, array $creatorAttributes) : static
	 * @return ModelCollection
	 */
	protected function _find( $attributes = [], $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		// update fixed in case passed via $attributes
		$this->setFixedAttributes( array_merge(
			$this->getFixedAttributes(),
			static::fixAttributes( $attributes )
		) );

		// get original attributes from builder
		$fixedAttributes = $this->getFixedAttributes();

		// prepare attributes
		$attributesForCacheKey = array_merge( $fixedAttributes, static::fixAttributes( $attributes ) );

		$cachedKey = serialize( $attributesForCacheKey ) . static::class;
		if ( !isset( static::$_collections[ $cachedKey ] ) ) {
			$col = new ModelCollection();

			$attributes = RestfulRecord::cleanAttributes( $attributesForCacheKey );
			$parts = static::parseResourceSlug( $fixedAttributes[ '__path' ] ?? '' );

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

			/** @var ApiResponse $response */
			if ( !$response->error ) {
				if ( $methodName == 'showRequest' ) {
					$recordData = $response->data;
					if ( $recordData[ 'type' ] == 'app' ) {
						$fixedAttributes[ '__app' ] = $recordData[ 'slug' ];
					}
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
				// @todo: find another way to handle errors
//				$tmp = new RestfulRecord( $attributes );
//				$tmp->setError( [ 'code' => $response->statusCode, 'message' => $response->message, 'response' => $this->getLastResponse() ] );
//				$col->push( $tmp );
			}

			static::$_collections[ $cachedKey ] = $col;
		}

		return static::$_collections[ $cachedKey ];
	}

	/**
	 * @return mixed
	 */
	public function getError() {
		return $this->_error;
	}

	/**
	 * @param mixed $error
	 */
	public function setError( $error ) {
		$this->_error = $error;
	}
}
