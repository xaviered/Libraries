<?php

namespace ixavier\Libraries\Server\RestfulRecords\Sales;

use ixavier\Libraries\Server\RestfulRecords\Sales\Traits\HasCategories;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Class Category is a collection of Product and/or Categories (sub-categories)
 *
 * @package ixavier\Libraries\RestfulRecords\Sales
 */
class Category extends Resource
{
	use HasCategories;

	public function getProducts() {
		// @todo: also look for `additionalCategories`
		return Product::query()->find( [ 'mainCategory' => $this->getXURL()->serviceUrl ] );
	}
}
