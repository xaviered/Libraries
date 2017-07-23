<?php

namespace ixavier\Libraries\RestfulRecords\Sales;

use ixavier\Libraries\RestfulRecords\Sales\Traits\HasCategories;
use ixavier\Libraries\RestfulRecords\Sales\Traits\HasModifierClasses;
use ixavier\Libraries\RestfulRecords\Resource;

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
