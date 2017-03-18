<?php
namespace ixavier\Libraries\Requests;

/**
 * Class ContentHouseApiRequest
 *
 * @package ixavier\Libraries\Requests
 */
class ContentHouseApiRequest extends ApiRequest
{
	public function __construct( $path = '/' ) {
		parent::__construct( config( 'services.content.url' ), $path );
	}
}
