<?php
namespace ixavier\Libraries\Requests;

class ApiResponse
{
	public $error = false;

	public $message;

	public $data = [];

	public $links = [];

	public $statusCode;

	public function __toString() {
		return json_encode( get_object_vars( $this ) );
	}
}
