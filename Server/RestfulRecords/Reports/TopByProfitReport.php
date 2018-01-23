<?php

namespace ixavier\Libraries\Server\RestfulRecords\Reports;

use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Class TopByProfitReport contains report of top items that produce the most profit
 *
 * @package ixavier\Libraries\RestfulRecords\Reports
 */
class TopByProfitReport extends Resource
{
	public function getTop( $count = null ) {
		$top = $this->getRelation( 'top_by_profit' );
		if ( $count ) {
			$top = $top->splice( 0, $count );
		}

		return $top;
	}
}
