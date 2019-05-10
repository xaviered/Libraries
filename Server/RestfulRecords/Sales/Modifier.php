<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales;

use ixavier\Libraries\Server\RestfulRecords\Sales\Traits\HasModifierClasses;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Class Modifier contains modification information for a product
 *
 * @package ixavier\Libraries\RestfulRecords\Sales
 */
class Modifier extends Resource
{
	use HasModifierClasses;
}
