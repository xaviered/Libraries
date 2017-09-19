<?php
namespace ixavier\Libraries\Server\RestfulRecords;

use ixavier\Libraries\Server\Core\RestfulRecord;

/**
 * Class App
 *
 * @package ixavier\Libraries\RestfulRecords
 */
class App extends RestfulRecord
{
	/**
	 * Creates a new Resource that's linked to this App
	 *
	 * @param array $fields
	 * @param string $type
	 * @param bool $exists
	 * @return Resource
	 */
	public function createResource( $fields, $type = null, $exists = null ) {
		if ( !isset( $fields[ '__app' ] ) ) {
			$fields[ '__app' ] = $this;
		}
		if ( !isset( $fields[ 'type' ] ) ) {
			$fields[ 'type' ] = $type;
		}

		return Resource::create( $fields, $exists );
	}
}
