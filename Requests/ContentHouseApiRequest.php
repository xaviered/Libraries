<?php
namespace ixavier\Libraries\Requests;

/**
 * Class ContentHouseApiRequest
 *
 * @package ixavier\Libraries\Requests
 */
class ContentHouseApiRequest extends ApiRequest
{
	/**
	 * ContentHouseApiRequest constructor.
	 *
	 * @param string $urlBase
	 * @param string $path
	 */
	public function __construct( $urlBase = null, $path = '/' ) {
		if ( empty( $urlBase ) ) {
			$urlBase = config( 'services.content.url' );
		}
		parent::__construct( $urlBase, $path );
	}
}
