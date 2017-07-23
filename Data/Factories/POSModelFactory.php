<?php

namespace ixavier\Libraries\Data\Factories;

use Faker\Generator;
use ixavier\Libraries\Core\DataFactoryInterface;
use ixavier\Libraries\RestfulRecords\Sales\Modifier;
use ixavier\Libraries\RestfulRecords\Sales\ModifierClass;
use ixavier\Libraries\RestfulRecords\Sales\Product;

/**
 * Class POSModelFactory
 * @package ixavier\Libraries\Data\Factories
 */
class POSModelFactory extends DataFactoryInterface
{
	/**
	 * @return POSModelFactory
	 */
	public function define() {
		return $this->defineProduct()
			->defineModifierClass()
			->defineModifier()
			;
	}

	/**
	 * Defines a Product generation
	 * @return $this
	 */
	public function defineProduct() {
		$this->factory->define( Product::class, function( Generator $faker ) {
			return [
				// ixavier library details
				'type' => 'product',
				'slug' => $faker->slug(),

				// basic details
				'name' => $faker->title,
				'description' => $faker->text,
				'productClass' => $faker->text,
				'priceEmbedded' => $faker->boolean,
				'sku' => $faker->text,
				'cost' => $faker->numberBetween( 0, 100 ),
				'price' => $faker->numberBetween( 0, 100 ),
				'altPrice' => $faker->numberBetween( 0, 100 ),
				'allowPriceOverride' => $faker->boolean,
				'mayDiscount' => $faker->boolean,
				'productGroups' => $faker->shuffleArray( [] ),
				'customMenus' => $faker->shuffleArray( [] ),
				'active' => $faker->boolean,
				'alternateLookup' => $faker->shuffleArray( [] ),
				'pricingOptions' => $faker->shuffleArray( [] ),
				'taxOptions' => $faker->shuffleArray( [] ),

				// display/print options
				'mainCategory' => $faker->text,
				'additionalCategories' => $faker->shuffleArray( [] ),
				'image' => $faker->text,
				'color' => $faker->hexColor,
				'disableModifierPopup' => $faker->boolean,
				'printers' => $faker->shuffleArray( [] ),
				'kitchenName' => $faker->text,
				'kitchenDescription' => $faker->text,
				'printTags' => $faker->boolean,

				// advance details

				// inventory

				// recipe info

				// modifiers
				'modifierClasses' => $faker->shuffleArray( [] ),
			];
		} );

		return $this;
	}

	/**
	 * Defines a ModifierClass generation
	 * @return $this
	 */
	public function defineModifierClass() {
		$this->factory->define( ModifierClass::class, function( Generator $faker ) {
			return [
				// ixavier library details
				'type' => 'modifierClass',
				'slug' => $faker->slug(),

				// basic details
				'name' => $faker->title,
				'active' => $faker->boolean,
				'allowSplitModifiers' => $faker->boolean,
				'color' => $faker->hexColor,

				// modifiers
				'modifiers' => $faker->shuffleArray( [] ),
			];
		} );

		return $this;
	}

	/**
	 * Defines a Modifier generation
	 * @return $this
	 */
	public function defineModifier() {
		$this->factory->define( Modifier::class, function( Generator $faker ) {
			return [
				// ixavier library details
				'type' => 'modifier',
				'slug' => $faker->slug(),

				// basic details
				'name' => $faker->title,
				'kitchenName' => $faker->text,
				'doNotPrint' => $faker->boolean,
				'cost' => $faker->numberBetween( 0, 100 ),
				'price' => $faker->numberBetween( 0, 100 ),
				'modifierClasses' => $faker->shuffleArray( [] ),
				'barcode' => $faker->text,
				'sku' => $faker->text,
				'active' => $faker->boolean,
				'isQuick' => $faker->boolean,
				'isHot' => $faker->boolean,
				'displayOn3rdParty' => $faker->boolean,
				'displayInKiosk' => $faker->boolean,
				'description' => $faker->text,
				'image' => $faker->text,
				'color' => $faker->hexColor,

				// recipe info
			];
		} );

		return $this;
	}
}
