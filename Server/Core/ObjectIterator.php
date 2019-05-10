<?php
namespace ixavier\Libraries\Server\Core;

/**
 * Class ObjectIterator is basic iterator
 *
 * @package ixavier\Libraries\Core
 */
class ObjectIterator implements \Iterator
{
	/** @var mixed[] */
	protected $_fields = [];

	public function __construct( &$fields = [] ) {
		if ( is_array( $fields ) ) {
			$this->_fields = $fields;
		}
	}

	public function rewind() {
		return reset( $this->_fields );
	}

	public function current() {
		$var = current( $this->_fields );

		return $var;
	}

	public function key() {
		$var = key( $this->_fields );

		return $var;
	}

	public function next() {
		$var = next( $this->_fields );

		return $var;
	}

	public function valid() {
		$key = key( $this->_fields );
		$var = ( $key !== NULL && $key !== FALSE );

		return $var;
	}
}
