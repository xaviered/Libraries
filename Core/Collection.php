<?php
namespace ixavier\Libraries\Core;

// @todo: Use use Illuminate\Support\Collection instead;
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

	/** @alias self::getContents() */
	public function all() {
		return $this->getContents();
	}

	// @todo: add pagination
	/**
	 * API array representation of this model
	 *
	 * @param bool $withKeys Show keys for Collections
	 * @param bool $hideLink Hide self link in Models
	 * @param bool $hideSelfLinkQuery Don't add query info to self link for Models
	 * @return array
	 */
	public function toApiArray( $withKeys = true, $hideLink = false, $hideSelfLinkQuery = false ) {
		$count = 0;
		$modelsArray = [];
		$paginator = $this->getContents();
		foreach ( $paginator as $itemKey => $item ) {
			$key = ( $withKeys ? $itemKey : $count );
			if ( is_object( $item ) && method_exists( $item, 'toApiArray' ) ) {
				$item = $item->toApiArray( $withKeys, $hideLink, $hideSelfLinkQuery );
			}
			$modelsArray[ 'data' ][ $key ] = $item;
			$count++;
		}
		$modelsArray[ 'count' ] = $this->count();

		return $modelsArray;
	}

	/**
	 * Basic iterator object
	 * @return ObjectIterator
	 */
	public function getIterator() {
		return new ObjectIterator( $this->_contents );
	}

	/**
	 * Total number of contents
	 * @return int
	 */
	public function count() {
		return count( $this->_contents );
	}

	/**
	 * @alias self::rewind()
	 * @return RestfulRecord|mixed
	 */
	public function first() {
		return $this->rewind();
	}

	/**
	 * @return array
	 */
	public function getContents(): array {
		return $this->_contents;
	}

	/**
	 * Gets the content set with the given $name
	 *
	 * @param string $name
	 * @param mixed $default Returns this if nothing found
	 * @return mixed
	 */
	public function get( $name, $default = null ) {
		return $this->_contents[ $name ] ?? $default;
	}

	/**
	 * Adds and sets $content with a particular $name
	 *
	 * @param string $name
	 * @param mixed $content
	 * @return $this
	 */
	public function set( $name, $content ) {
		$this->_contents[ $name ] = $content;

		return $this;
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
