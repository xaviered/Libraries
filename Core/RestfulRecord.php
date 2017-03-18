<?php
namespace ixavier\Libraries\Core;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use ixavier\Libraries\Requests\ContentHouseApiRequest;

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

	/**
	 * RestfulRecord constructor.
	 *
	 * @param array|object $attributes Pre-populate with attributes
	 */
	public function __construct( $attributes = [] ) {
		if ( is_object( $attributes ) ) {
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
			$path = '/';
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
				$path = '/' . implode( '/', $matches );
			}
		}
		else {
			$path = $attributes[ '__path' ];
		}

		$this->setAttributes( static::cleanAttributes( $attributes ) );

		parent::__construct( $path );
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
	 * Finds one record with the given criteria
	 *
	 * @param string|array $slugOrQuery
	 * @return Collection
	 */
	public static function find( $slugOrQuery ) {
		return static::_find(
			$slugOrQuery,
			function( $record ) {
				return static::create( $record->data );
			}
		);
	}

	/**
	 * Gets first record found
	 *
	 * @param string|array $slugOrQuery
	 * @return RestfulRecord|App
	 */
	public static function findOne( $slugOrQuery ) {
		return static::_findOne(
			$slugOrQuery,
			function( $record ) {
				return static::create( $record->data );
			}
		);
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
	 * Given Resource slug, will parse out its app, type and actual slug
	 *
	 * @param string $slug
	 * @param array $defaults Array with default values to return if nothing found
	 * @return array i.e.
	 * [
	 * 0 => slug of app,
	 * 1 => type of resource,
	 * 2 => true slug of resource
	 * ]
	 */
	public static function parseResourceSlug( $slug, $defaults = [ 'app' => null, 'type' => null ] ) {
		if ( strpos( $slug, '/' ) !== FALSE ) {
			$regex = sprintf(
				"|(?=/(%s)(?=/(%s)(?=/(%s))?)?)?|",
				RestfulRecord::SLUG_REGEX,
				RestfulRecord::RESOURCE_TYPE_REGEX,
				RestfulRecord::SLUG_REGEX
			);
			preg_match( $regex, $slug, $matches );
			$matches = [
				'app' => $matches[ 1 ] ?? null,
				'type' => $matches[ 2 ] ?? null,
				'resource' => $matches[ 3 ] ?? ( isset( $matches[ 1 ] ) ? null : $slug ),
			];
		}
		else {
			$matches = [ 'resource' => $slug ];
		}

		return array_values( array_merge( $defaults, $matches ) );
	}

	/**
	 * Cleans runtime vars from real attributes
	 *
	 * @param array $attributes
	 * @return array
	 */
	public static function cleanAttributes( $attributes ) {
		unset( $attributes[ '__app' ] );
		unset( $attributes[ '__path' ] );

		return $attributes;
	}

	/**
	 * Gets the first record with the given criteria
	 *
	 * @param string $path
	 * @param string|array $attributes
	 * @param callable $createFunction Signature: function(\StdClass $record) : static
	 * @return static
	 */
	protected static function _findOne( $attributes, $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}
		$attributes[ 'page_size' ] = 1;

		return static::_find( $attributes, $createFunction )->rewind();
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string $path
	 * @param string|array $attributes
	 * @param callable $createFunction Signature: function(\StdClass $record) : static
	 * @return Collection
	 */
	protected static function _find( $attributes, $createFunction = null ) {
		if ( is_string( $attributes ) ) {
			$attributes = [ 'slug' => $attributes ];
		}

		$cachedKey = serialize( $attributes );
		if ( !isset( static::$_collections[ $cachedKey ] ) ) {
			$col = new Collection();

			$recordTmp = new RestfulRecord( $attributes );
			$response = $recordTmp->indexRequest( $recordTmp->getAttributes() );
			if ( !$response->error ) {
				foreach ( $response->data as $recordData ) {
					if ( $createFunction ) {
						$recordData = call_user_func_array( $createFunction, [ $recordData ] );
					}
					$col->append( $recordData );
				}
			}

			static::$_collections[ $cachedKey ] = $col;
		}

		return static::$_collections[ $cachedKey ];
	}
}
