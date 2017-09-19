<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales\Traits;

use ixavier\Libraries\Server\Core\ModelCollection;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Trait HasModifierClasses enables the use of modifierClasses
 * @package ixavier\Libraries\RestfulRecords\POS\Traits
 *
 * @uses Resource
 */
trait HasModifierClasses
{
	public function setModifierClasses( ModelCollection $modifierClasses ) {
		$this->setRelationship( 'modifierClasses', $modifierClasses );
	}

	public function getModifierClasses() {
		if ( !$this->hasRelationship( 'modifierClasses' ) ) {
			$this->setRelationship( 'modifierClasses', new ModelCollection() );
		}

		return $this->getRelationship( 'modifierClasses' );
	}
}
