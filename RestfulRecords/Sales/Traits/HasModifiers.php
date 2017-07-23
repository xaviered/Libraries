<?php

namespace ixavier\Libraries\RestfulRecords\Sales\Traits;

use ixavier\Libraries\Core\ModelCollection;
use ixavier\Libraries\RestfulRecords\Resource;

/**
 * Trait HasModifiers enables the use of modifiers
 * @package ixavier\Libraries\RestfulRecords\POS\Traits
 *
 * @uses Resource
 */
trait HasModifiers
{
	public function setModifiers( ModelCollection $modifiers ) {
		$this->setRelationship( 'modifiers', $modifiers );
	}

	public function getModifiers() {
		if ( !$this->hasRelationship( 'modifiers' ) ) {
			$this->setRelationship( 'modifiers', new ModelCollection() );
		}

		return $this->getRelationship( 'modifiers' );
	}
}
