<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales\Traits;

use ixavier\Libraries\Server\Core\ModelCollection;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Trait HasCategories enables the use of categories
 * @package ixavier\Libraries\RestfulRecords\Hierarchy\Traits
 *
 * @uses Resource
 */
trait HasCategories
{
	public function setCategories( ModelCollection $categories ) {
		$this->setRelationship( 'categories', $categories );
	}

	public function getCategories() {
		if ( !$this->hasRelationship( 'categories' ) ) {
			$this->setRelationship( 'categories', new ModelCollection() );
		}

		return $this->getRelationship( 'categories' );
	}
}
