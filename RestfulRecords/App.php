<?php
namespace ixavier\Libraries\RestfulRecords;

use Illuminate\Support\Facades\Log;
use ixavier\Libraries\Core\Collection;
use ixavier\Libraries\Core\RestfulRecord;

/**
 * Class App
 *
 * @package ixavier\Libraries\RestfulRecords
 */
class App extends RestfulRecord
{
	/** Base path to these type of records */
	CONST BASE_PATH = '/app';

	/** Type of record */
	CONST RECORD_TYPE = 'app';

	/**
	 * App constructor.
	 *
	 * @param array $fields
	 */
	public function __construct( $fields = null ) {
		parent::__construct( static::BASE_PATH, static::RECORD_TYPE, $fields );
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
		return parent::_find(
			static::BASE_PATH,
			static::RECORD_TYPE,
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
			static::BASE_PATH,
			static::RECORD_TYPE,
			$slugOrQuery,
			function( $record ) {
				return static::create( $record->data );
			}
		);
	}
}
