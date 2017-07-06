<?php
namespace ixavier\Libraries\RestfulRecords;

use ixavier\Libraries\Core\RestfulRecord;

/**
 * Class Resource represents one record under a given App
 *
 * @package use ixavier\Libraries\ModelCollection;
 */
class Resource extends RestfulRecord
{
	/**
	 * Resource constructor.
	 *
	 * @param array $attributes
	 * @param App $app
	 * @param string $type
	 */
	public function __construct( $attributes = [], $app = null, $type = null ) {
		$attributes[ '__app' ] = ( $attributes[ '__app' ] ?? $app );
		$attributes[ 'type' ] = ( $attributes[ 'type' ] ?? $type );

		parent::__construct( $attributes );
	}
}
