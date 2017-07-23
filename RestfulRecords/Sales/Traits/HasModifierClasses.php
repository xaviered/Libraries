<?php

namespace ixavier\Libraries\RestfulRecords\Sales\Traits;

use ixavier\Libraries\Core\ModelCollection;
use ixavier\Libraries\RestfulRecords\Resource;

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
