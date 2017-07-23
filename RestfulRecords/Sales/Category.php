<?php

namespace ixavier\Libraries\RestfulRecords\Sales;

use ixavier\Libraries\RestfulRecords\Sales\Traits\HasCategories;
use ixavier\Libraries\RestfulRecords\Resource;

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
