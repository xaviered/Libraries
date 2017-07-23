<?php

namespace ixavier\Libraries\RestfulRecords\Sales;

use ixavier\Libraries\RestfulRecords\Sales\Traits\HasModifierClasses;
use ixavier\Libraries\RestfulRecords\Resource;

/**
 * Class Modifier contains modification information for a product
 *
 * @package ixavier\Libraries\RestfulRecords\Sales
 */
class Modifier extends Resource
{
	use HasModifierClasses;
}
