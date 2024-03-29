<?php

namespace ixavier\Libraries\Server\Data\Factories;

use Faker\Generator;
use ixavier\Libraries\Server\Core\DataFactoryInterface;
use ixavier\Libraries\Server\RestfulRecords\App;

/**
 * Class CoreModelFactory
 * @package ixavier\Libraries\Data\Factories
 */
class CoreModelFactory extends DataFactoryInterface
{
	/**
	 * @return CoreModelFactory
	 */
	public function define() {
		return $this->defineApp();
	}

	/**
	 * Defines an App generation
	 * @return $this
	 */
	public function defineApp() {
		$this->factory->define( App::class, function( Generator $faker ) {
			return [
				'title' => $faker->title,
				'type' => 'app',
				'slug' => $faker->slug(),
			];
		} );

		return $this;
	}
}
