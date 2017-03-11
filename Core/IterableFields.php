<?php
namespace ixavier\Libraries\Core;

/**
 * Class IterableFields adds fields and enables array iteration on them
 *
 * @package ixavier\Libraries\Core
 */
trait IterableFields
{
	/** @var array */
	protected $_fields;

	/** @var callable[] Listeners on fields */
	protected $_fieldListeners;

	/**
	 * Basic iterator object
	 * @return ObjectIterator
	 */
	public function getIterator() {
		return new ObjectIterator( $this->_fields );
	}

	/**
	 * Gets one field
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function getField( $key ) {
		return $this->getFields()[ $key ] ?? null;
	}

	/**
	 * Gets all fields
	 *
	 * @return mixed
	 */
	public function getFields() {
		return $this->_fields;
	}

	/**
	 * @param mixed $_fields
	 * @return $this Chainnable method
	 */
	public function setFields( $_fields ) {
		$this->_fields = $_fields;

		return $this;
	}

	/**
	 * @return \callable[] Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 */
	public function getFieldListeners() {
		return $this->_fieldListeners;
	}

	/**
	 * @param \callable[] $fieldListeners Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 */
	public function setFieldListeners( $fieldListeners ) {
		$this->_fieldListeners = $fieldListeners;
	}

	/**
	 * Adds a field listener to a particular field
	 *
	 * @param string $fieldName
	 * @param callable $callback Callable signature: function(string $name, mixed $newValue, $oldValue) : mixed
	 * @return $this Chainnable method
	 */
	public function addFieldListener( $fieldName, $callback ) {
		$this->_fieldListeners[ $fieldName ] = $callback;

		return $this;
	}

	/**
	 * Gets the callback field listener
	 *
	 * @param string $fieldName
	 * @return callable|null Null if not found
	 */
	public function getFieldListener( $fieldName ) {
		return $this->_fieldListeners[ $fieldName ] ?? null;
	}

	/**
	 * Checks if there is a field listener
	 *
	 * @param string $fieldName
	 * @return bool
	 */
	public function hasFieldListener( $fieldName ) {
		return isset( $this->_fieldListeners[ $fieldName ] );
	}

	//
	// Helper magic methods
	//

	public function __get( $name ) {
		return $this->_fields[ $name ] ?? null;
	}

	public function __set( $name, $value ) {
		if ( $this->hasFieldListener( $name ) ) {
			$value = call_user_func( $this->getFieldListener( $name ), [ $name, $value, $this->_fields[ $name ] ?? null ] );
		}
		$this->_fields[ $name ] = $value;

		return $value;
	}

	public function __isset( $name ) {
		return isset( $this->_fields[ $name ] );
	}

	public function __unset( $name ) {
		unset( $this->_fields[ $name ] );
	}
}
