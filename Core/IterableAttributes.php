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
	 * Gets public variables, not attributes
	 * @return array
	 */
	protected function getPublicVars() {
		$properties = [];
		foreach ( ( new \ReflectionClass( $this ) )->getProperties( \ReflectionProperty::IS_PUBLIC ) as $property ) {
			$properties[ $property->getName() ] = $this->{$property->getName()};
		}

		return $properties;

	}

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
	 * @param string $name
	 * @return mixed
	 */
	public function getAttribute( $name ) {
		return $this->getAttributes()[ $name ] ?? null;
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
	 * @param string $name
	 * @param mixed $value
	 * @return $this Chainnable method
	 * @internal param array $attributes
	 */
	public function setAttribute( $name, $value, $external = false ) {
		if ( $this->hasAttributeListener( $name ) ) {
			$value = call_user_func( $this->getAttributeListener( $name ), [ $name, $value, $this->_attributes[ $name ] ?? null ] );
		}

		$this->_attributes[ $name ] = $value;

		return $this;
	}

	/**
	 * @param array $attributes
	 * @param bool $merge
	 * @return $this Chainnable method
	 */
	public function setAttributes( array $attributes, $merge = false ) {
		if ( $merge ) {
			$oldAttributes = $this->_attributes;
		}
		$this->_attributes = [];

		foreach ( $attributes as $name => $value ) {
			$this->setAttribute( $name, $value, true );
		}

		if ( $merge ) {
			$this->_attributes = array_merge( $oldAttributes, $this->_attributes );
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function isAttribute( $name ) {
		return isset( $this->_attributes[ $name ] );
	}

	/**
	 * @param string $name
	 */
	public function removeAttribute( $name ) {
		unset( $this->_attributes[ $name ] );
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
		return $this->getAttribute( $name );
	}

	public function __set( $name, $value ) {
		$this->setAttribute( $name, $value );

		return $value;
	}

	public function __isset( $name ) {
		return $this->isAttribute( $name );
	}

	public function __unset( $name ) {
		$this->removeAttribute( $name );
	}
}
