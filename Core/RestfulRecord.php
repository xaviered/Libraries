<?php
namespace ixavier\Libraries\Core;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;
use ixavier\Libraries\Requests\ContentHouseApiRequest;

/**
 * Class RestfulRecord
 * @package ixavier\Libraries\Models
 *
 * @internal string $slug
 * @internal string $type Type of record
 */
class RestfulRecord extends ContentHouseApiRequest
{
	use IterableFields {
		getFields as IterableFields__getFields;
	}

	/** @var Response Error Response if bad response from API server */
	public $___error;

	/** @var bool True if already loaded */
	protected $_loaded = false;

	/** @var Collection */
	protected $_relationshipsCollection;

	protected static $_collections = [];

	/**
	 * RestfulRecord constructor.
	 *
	 * @param string $path Base path to restful record
	 * @param string $type Type of record
	 * @param array|object $fields Pre-populate with fields
	 * @throws \Exception Needs type either on $type or $fields['type']
	 */
	public function __construct( $path, $type = null, $fields = null ) {
		if ( is_object( $fields ) ) {
			$fields = get_object_vars( $fields );
		}
		else if ( is_string( $fields ) ) {
			$fields = [ 'slug' => $fields ];
		}
		else if ( !is_array( $fields ) ) {
			$fields = [];
		}

		if ( !empty( $type ) ) {
			$fields[ 'type' ] = $type;
		}

		if ( empty( $fields[ 'type' ] ) ) {
			throw new \Exception( 'New instance of ' . get_class( $this ) . ' must have a type, none given.' );
		}

		$this->setFields( $fields );

		parent::__construct( $path );
	}

	/**
	 * Gets the first record with the given criteria
	 *
	 * @param string $path
	 * @param string $type
	 * @param string|array $slugOrQuery
	 * @param callable $createFunction Signature: function(\StdClass $record) : static
	 * @return static
	 */
	protected static function _findOne( $path, $type, $slugOrQuery, $createFunction = null ) {
		if ( is_string( $slugOrQuery ) ) {
			$slugOrQuery = [ 'slug' => $slugOrQuery ];
		}
		$slugOrQuery[ 'page_size' ] = 1;

		return static::_find( $path, $type, $slugOrQuery, $createFunction )->rewind();
	}

	/**
	 * Finds records with the given criteria
	 *
	 * @param string $path
	 * @param string $type
	 * @param string|array $slugOrQuery
	 * @param callable $createFunction Signature: function(\StdClass $record) : static
	 * @return Collection
	 */
	protected static function _find( $path, $type, $slugOrQuery, $createFunction = null ) {
		if ( is_string( $slugOrQuery ) ) {
			$slugOrQuery = [ 'slug' => $slugOrQuery ];
		}

		$cachedKey = $path . $type . serialize( $slugOrQuery );
		if ( !isset( static::$_collections[ $cachedKey ] ) ) {
			$col = new Collection();

			$recordTmp = new RestfulRecord( $path, $type );
			$response = $recordTmp->index( $slugOrQuery );
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
				$this->path,
				$this->type,
				$this->slug,
				function( $recordData ) use ( $record ) {
					return $record->setFields( $recordData->data )->setLoaded( true );
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
				// one-to-one relationship
				if ( isset( $this->relationships ) ) {
					foreach ( $this->relationships as $rKey => $rData ) {

					}
				}

			}
		}
	}

	/**
	 * Gets all fields
	 *
	 * @return mixed
	 */
	public function getFields() {
		$this->load();

		return $this->IterableFields__getFields();
	}

	/**
	 * @return Collection
	 */
	public function getRelationships() {
		$this->loadRelationships();

		return $this->_relationshipsCollection;
	}

	public function __toString() {
		return $this->getPath() . ': ' . json_encode( $this->getFields() );
	}
}
