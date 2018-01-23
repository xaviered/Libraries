<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales\Traits;

use ixavier\Libraries\Server\Core\ModelCollection;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Trait HasModifiers enables the use of modifiers
 * @package ixavier\Libraries\RestfulRecords\POS\Traits
 *
 * @uses Resource
 */
trait HasModifiers
{
	public function setModifiers( ModelCollection $modifiers ) {
		$this->setRelation( 'modifiers', $modifiers );
	}

	public function getModifiers() {
		if ( !$this->hasRelation( 'modifiers' ) ) {
			$this->setRelation( 'modifiers', new ModelCollection() );
		}

		return $this->getRelation( 'modifiers' );
	}
}
