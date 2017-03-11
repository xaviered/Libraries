<?php
namespace ixavier\Libraries\RestfulRecords;

use ixavier\Libraries\Core\Collection;
use ixavier\Libraries\Core\RestfulRecord;

/**
 * Class Resource represents one record under a given App
 *
 * @package ixavier\Libraries\RestfulRecords
 */
class Resource extends RestfulRecord
{
	/** @var App Resources live under this app */
	protected $_app;

	/**
	 * Resource constructor.
	 *
	 * @param App $app
	 * @param string $type
	 * @param array $fields
	 */
	public function __construct( App $app, $type, $fields = null ) {
		$this->_app = $app;
		$path = $this->_app->getPath() . '/' . $type;
		parent::__construct( $path, $type, $fields );
	}

	/**
	 * Helper function for constructor
	 *
	 * @param App $app
	 * @param string $type
	 * @param array $fields
	 * @return static
	 */
	public function createResource( App $app, $type, $fields = null ) {
		// @todo: don't call static, use overwritten classes for each type
		return ( new static( $app, $type, $fields ) )->setLoaded( true );
	}

	/**
	 * Finds one record with the given criteria
	 *
	 * @param App $app
	 * @param string $type
	 * @param string|array $slugOrQuery
	 * @param callable $createFunction Will use self::_createResource() method if nothing passed.
	 *  Signature: function(\StdClass $record) : static
	 * @return Collection
	 */
	public static function findResource( App $app, $type, $slugOrQuery, $createFunction = null ) {
		if ( !$createFunction ) {
			$createFunction = function( $record ) use ( $app, $type ) {
				return static::createResource( $app, $type, $record->data );
			};
		}

		return parent::_find(
			get_class( $app )::BASE_PATH,
			$type,
			$slugOrQuery,
			$createFunction
		);
	}

	/**
	 * Gets first record found
	 *
	 * @param App $app
	 * @param string $type
	 * @param string|array $slugOrQuery
	 * @param callable $createFunction Will use self::_createResource() method if nothing passed.
	 *  Signature: function(\StdClass $record) : static
	 * @return RestfulRecord|Resource
	 */
	public static function findOneResource( App $app, $type, $slugOrQuery, $createFunction = null ) {
		if ( !$createFunction ) {
			$createFunction = function( $record ) use ( $app, $type ) {
				return static::createResource( $app, $type, $record->data );
			};
		}

		return parent::_findOne(
			get_class( $app )::BASE_PATH,
			$type,
			$slugOrQuery,
			$createFunction
		);
	}
}
