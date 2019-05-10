<?php

namespace ixavier\Libraries\Server\Core;

use Illuminate\Database\Eloquent\Factory;

/**
 * Interface DataFactoryInterface defines interface for creating data factories
 *
 * @package ixavier\Libraries\Core
 */
abstract class DataFactoryInterface
{
	/** @var Factory */
	public $factory;

	/**
	 * DataFactoryInterface constructor.
	 * @param Factory $factory
	 */
	public function __construct( Factory $factory ) {
		$this->factory = $factory;
	}

	/**
	 * @return $this
	 */
	abstract public function define() ;
}
