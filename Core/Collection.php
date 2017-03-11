<?php
namespace ixavier\Libraries\Core;

/**
 * Class Collection
 *
 * @package ixavier\Libraries\Core
 *
 * @see ObjectIterator
 * @method RestfulRecord rewind()
 * @method RestfulRecord current()
 * @method RestfulRecord next()
 * @method string|int key()
 * @method bool valid()
 */
class Collection implements \IteratorAggregate
{
	/** @var mixed[] */
	protected $_contents = [];

	/**
	 * Collection constructor.
	 * @param array $contents
	 */
	public function __construct( $contents = [] ) {
		$this->setContents( $contents );
	}

	/**
	 * Basic iterator object
	 * @return ObjectIterator
	 */
	public function getIterator() {
		return new ObjectIterator( $this->_contents );
	}

	/**
	 * @return array
	 */
	public function getContents(): array {
		return $this->_contents;
	}

	/**
	 * @param array $contents
	 * @return $this Chainnable method
	 */
	public function setContents( array $contents ) {
		$this->_contents = $contents;

		return $this;
	}

	/** @alias of self::unshift() */
	public function append( $content ) {
		return $this->unshift( $content );
	}

	/**
	 * Appends new content item to beginning of collection
	 *
	 * @param mixed $content
	 * @return $this Chainnable method
	 */
	public function unshift( $content ) {
		array_unshift( $this->_contents, $content );

		return $this;
	}

	/**
	 * Removes content item from beginning of the collection and returns it.
	 *
	 * @return mixed
	 */
	public function shift() {
		return array_shift( $this->_contents );
	}

	/**
	 * Adds content item to the end of the collection
	 *
	 * @param mixed $content
	 * @return $this
	 */
	public function push( $content ) {
		array_push( $this->_contents, $content );

		return $this;
	}

	/**
	 * Removes content item from end of the collection and returns it.
	 *
	 * @return mixed
	 */
	public function pop() {
		return array_pop( $this->_contents );
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 * @throws \Exception
	 */
	public function __call( $name, $arguments ) {
		if ( method_exists( $this->getIterator(), $name ) ) {
			return call_user_func( [ $this->getIterator(), $name ], $arguments );
		}

		throw new \Exception( 'Undefined method ' . $name . ' called on ' . get_class( $this ) );
	}
}
