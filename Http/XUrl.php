<?php
namespace ixavier\Libraries\Http;

class XUrl
{
	/** @var string */
	protected $uri;

	/**
	 * XUrl constructor.
	 * @param string $uri
	 */
	public function __construct( $uri = null ) {
		$this->uri = $uri;
	}

	public function isValid() {
		// https:80//api/service/v1/app
		// https:80//api/service/v1/app/slug

		// https:80//api/service/v1/resource/app
		// https:80//api/service/v1/resource/app/type
		// https:80//api/service/v1/resource/app/type/resource

		// https:80//api/service/v1/app/type
		// https:80//api/service/v1/app/type/resource
		preg_match('|(https?\:(\d+)?//)?|', $this->uri, $matches);
	}

	public static function create( $uri ) {
		return ( new static( $uri ) );
	}

}
