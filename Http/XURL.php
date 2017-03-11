<?php
namespace ixavier\Libraries\Http;

class XURL
{
	/** @var string */
	protected $uri;

	/**
	 * XURL constructor.
	 * @param string $uri
	 */
	public function __construct( $uri = null ) {
		$this->uri = $uri;
	}

	public function isValid() {
		// DB entry XURL translates from:
		// internal global = {service|site}:{version?}//{domain}/{apiRequest}
		// internal local = {service|site}:{version?}//{apiRequest}
		// internal same service = :{version?}//{apiRequest}

		// translates to:
		// external global = //{service|site}.{domain}/api/{version?}/{apiRequest}
		// external cdn = //{serviceDomain|siteDomain}/api/{version?}/{apiRequest}

		// i.e.
		// from
		// contenthouse:{version}//ixavier.com/app/{slug}
		// contenthouse:{version}//ixavier.com/{type}/{app}
		// contenthouse:{version}//ixavier.com/{type}/{app}/{slug}

		// to:
		// external global = //contenthouse.ixavier.com/api/{version}/app/{slug}
		// external global = //contenthouse.ixavier.com/api/{version}/{type}/{app}
		// external global = //contenthouse.ixavier.com/api/{version}/{type}/{app}/{slug}

		preg_match( '|(.+)?\:(\d+)?//(.+)|', $this->uri, $matches );

		return $matches;
	}

	public static function create( $uri ) {
		$instance = new static( $uri );

		return $instance->isValid() ? $instance : null;
	}

}
