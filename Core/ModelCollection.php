<?php
namespace ixavier\Libraries\Core;

use Illuminate\Support\Collection;

/**
 * Class ModelCollection
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
class ModelCollection extends Collection
{
	/**
	 * Get a dictionary keyed by primary keys.
	 *
	 * Need to mimic Laravel's Illuminate/Database/Eloquent/Collection::getDictionary() for loading relations
	 *
	 * @return array
	 */
	public function getDictionary() {
		return $this->all();
	}

	/**
	 * Returns new collection with XURL representation of the Records
	 * @return static
	 */
	public function getXURLs() {
		$xurls = new static();
		$this->each( function( RestfulRecord $record ) use ( $xurls ) {
			return $xurls->push( $record->getXURL() );
		} );

		return $xurls;
	}

	// @todo: add pagination
	/**
	 * API array representation of this collection
	 *
	 * @param int $relationsDepth Current depth of relations loaded. Default = 1
	 * @param bool $hideLinks Hide links section
	 * @param bool $withKeys Show keys for Collections
	 * @param bool $ignorePaging Will not load paging mechanism
	 * @return array
	 */
	public function toApiArray( $relationsDepth = -1, $hideLinks = false, $withKeys = false, $ignorePaging = false ) {
		$count = 0;
		$modelsArray = [];
		$paginator = $this;
		foreach ( $paginator as $itemKey => $item ) {
			if ( $item instanceof self ) {
				$item = $item->toApiArray( $relationsDepth + 1, true, false, true )[ 'data' ] ?? [];
			}
			else if ( $item instanceof RestfulRecord ) {
				$item = $item->toApiArray( $relationsDepth + 1, true );
			}

			$modelsArray[ 'data' ][ $withKeys ? $itemKey : $count ] = $item;
			$count++;
		}

		$modelsArray[ 'count' ] = $paginator->count();

		if ( !$hideLinks ) {
			// this is a "collection", so don't pass any params
//			$r = Request::create( $this->getRootModel()->uri( 'show', [''] ) );
//			$modelsArray[ 'links' ][ 'self' ] = $request->query->count() ? $r->fullUrlWithQuery( $request->all() ) : $r->url();
		}

		return $modelsArray;
	}
}
