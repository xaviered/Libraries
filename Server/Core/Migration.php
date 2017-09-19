<?php
namespace ixavier\Libraries\Server\Core;

use ixavier\Libraries\Server\Console\Console;

/**
 * Class Migration is base class for dealing with Migrations
 *
 * @package ixavier\Libraries\Core
 */
abstract class Migration extends Console
{
	/**
	 * The name of the database connection to use.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * Get the migration connection name.
	 *
	 * @return string
	 */
	public function getConnection() {
		return $this->connection;
	}
}
