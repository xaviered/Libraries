<?php
namespace ixavier\Libraries\Requests;

class ContentHouseApiRequest extends ApiRequest
{
	public function __construct( $path = '/' ) {
		parent::__construct( config( 'services.contenthouse.url' ), $path );
	}
}
