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
	 * @param mixed $urlBase
	 * @return $this Chainnable method
	 */
	public function setUrlBase( $urlBase ) {
		if ( empty( $urlBase ) ) {
			$urlBase = config( 'services.content.url' );
		}

		return parent::setUrlBase( $urlBase );
	}
}
