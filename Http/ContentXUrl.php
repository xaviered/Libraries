<?php
namespace ixavier\Libraries\Http;

use ixavier\Libraries\Core\RestfulRecord;

/**
 * Class ContentXUrl adds functionality to URLs coming from the iXavier's `content` service
 *
 * @package ixavier\Libraries\Http
 */
class ContentXUrl extends XUrl
{
	public $app;
	public $type;
	public $resource;

	/**
	 * Sets object's properties
	 *
	 * @param array $properties New properties; only class properties will be set
	 * @return $this
	 */
	public function setProperties( $properties ) {
		parent::setProperties( $properties );

		if ( !empty( $this->requestedResource ) ) {
			list( $this->app, $this->type, $this->resource ) = RestfulRecord::parseResourceSlug( $this->requestedResource );
		}

		return $this;
	}
}
