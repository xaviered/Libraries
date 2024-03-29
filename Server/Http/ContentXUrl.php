<?php
namespace ixavier\Libraries\Server\Http;

use ixavier\Libraries\Server\Core\RestfulRecord;

/**
 * Class ContentXURL adds functionality to URLs coming from the iXavier's `content` service
 *
 * @package ixavier\Libraries\Http
 */
class ContentXURL extends XURL
{
	public $app;
	public $type;
	public $resource;

	/**
	 * @return array
	 */
	public function getRestfulRecordAttributes($keepSlug=false) {
		$attributes = RestfulRecord::fixAttributesFromPath( $this->requestedResource, $keepSlug );
		$attributes[ '__url' ] = $this->apiUrl;

		return $attributes;
	}

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
