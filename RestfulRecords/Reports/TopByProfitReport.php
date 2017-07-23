<?php

namespace ixavier\Libraries\RestfulRecords\Reports;

use ixavier\Libraries\RestfulRecords\Resource;

/**
 * Class TopByProfitReport contains report of top items that produce the most profit
 *
 * @package ixavier\Libraries\RestfulRecords\Reports
 */
class TopByProfitReport extends Resource
{
	public function getTop( $count = null ) {
		$top = $this->getRelationship( 'top_by_profit' );
		if ( $count ) {
			$top = $top->splice( 0, $count );
		}

		return $top;
	}
}
