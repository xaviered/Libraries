<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales;

use ixavier\Libraries\Server\RestfulRecords\Sales\Traits\HasCategories;
use ixavier\Libraries\Server\RestfulRecords\Sales\Traits\HasModifierClasses;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Class Product represents a single product
 *
 * @package ixavier\Libraries\RestfulRecords\Sales
 */
class Product extends Resource
{
	use HasCategories;
	use HasModifierClasses;
}
