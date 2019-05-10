<?php
namespace ixavier\Libraries\Server\Requests;

/**
 * Class ApiResponse
 *
 * @package ixavier\Libraries\Requests
 */
class ApiResponse
{
	/** @var bool */
	public $error = false;

	/** @var string */
	public $message;

	/** @var array|\stdClass  */
	public $data = [];

	/** @var array */
	public $headers = [];

	/** @var int */
	public $statusCode;

	/** @var array */
	public $request = [];

	/** @return string String representation of this class */
	public function __toString() {
		return json_encode( get_object_vars( $this ) );
	}
}
