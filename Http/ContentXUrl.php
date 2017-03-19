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
	 * @return array
	 */
	public function getRestfulRecordAttributes() {
		$attributes = [];
		$matches = RestfulRecord::parseResourceSlug( $this->requestedResource );
		$attributes[ 'slug' ] = array_pop( $matches );
		// type is empty, it is app
		if ( empty( $matches[ 1 ] ) ) {
			$attributes[ 'slug' ] = $matches[ 0 ];
			$attributes[ '__path' ] = '/';
		}
		else {
			$attributes[ '__path' ] = implode( '/', $matches );
		}

		$attributes[ '__url' ] = $this->serviceUrl;

		return array_merge( parent::getRestfulRecordAttributes(), $attributes );
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
