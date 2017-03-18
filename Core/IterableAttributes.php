<?php
namespace ixavier\Libraries\Core;

/**
 * Class IterableAttributes adds attributes and enables array iteration on them
 *
 * @package ixavier\Libraries\Core
 */
trait IterableAttributes
{
	/** @var array */
	protected $_attributes = [];

	/** @var callable[] Listeners on attributes */
	protected $_attributeListeners;

	/**
	 * Basic iterator object
	 * @return ObjectIterator
	 */
	public function getIterator() {
		return new ObjectIterator( $this->_attributes );
	}

	/**
	 * Gets one attribute
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getAttribute( $key ) {
		return $this->getAttributes()[ $key ] ?? null;
	}

	/**
	 * Gets all attributes
	 *
	 * @return array
	 */
	public function getAttributes() {
		return $this->_attributes;
	}

	/**
	 * @param array $_attributes
	 * @return $this Chainnable method
	 */
	public function setAttributes( array $_attributes ) {
		$this->_attributes = $_attributes;

		return $this;
	}

	/**
	 * @return \callable[] Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 */
	public function getAttributeListeners() {
		return $this->_attributeListeners;
	}

	/**
	 * @param \callable[] $attributeListeners Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 */
	public function setAttributeListeners( $attributeListeners ) {
		$this->_attributeListeners = $attributeListeners;
	}

	/**
	 * Adds a attribute listener to a particular attribute
	 *
	 * @param string $attributeName
	 * @param callable $callback Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 * @return $this Chainnable method
	 */
	public function addAttributeListener( $attributeName, $callback ) {
		$this->_attributeListeners[ $attributeName ] = $callback;

		return $this;
	}

	/**
	 * Gets the callback attribute listener
	 *
	 * @param string $attributeName
	 * @return callable|null Null if not found
	 */
	public function getAttributeListener( $attributeName ) {
		return $this->_attributeListeners[ $attributeName ] ?? null;
	}

	/**
	 * Checks if there is a attribute listener
	 *
	 * @param string $attributeName
	 * @return bool
	 */
	public function hasAttributeListener( $attributeName ) {
		return isset( $this->_attributeListeners[ $attributeName ] );
	}

	//
	// Helper magic methods
	//

	public function __get( $name ) {
		return $this->_attributes[ $name ] ?? null;
	}

	public function __set( $name, $value ) {
		if ( $this->hasAttributeListener( $name ) ) {
			$value = call_user_func( $this->getAttributeListener( $name ), [ $name, $value, $this->_attributes[ $name ] ?? null ] );
		}
		$this->_attributes[ $name ] = $value;

		return $value;
	}

	public function __isset( $name ) {
		return isset( $this->_attributes[ $name ] );
	}

	public function __unset( $name ) {
		unset( $this->_attributes[ $name ] );
	}
}
