<?php

namespace ixavier\Libraries\Server\Helpers;

use ixavier\Libraries\Server\Core\ModelCollection;
use ixavier\Libraries\Server\Exceptions\RestfulRecordNotSavedException;
use ixavier\Libraries\Server\RestfulRecords\App;
use ixavier\Libraries\Server\RestfulRecords\Resource;

/**
 * Trait RestfulRecordArtisanHelper contains helper methods for load RestfulRecords for Artisan libraries
 *
 * @package ixavier\Libraries\Core
 */
trait RestfulRecordArtisanHelper
{
	/** @var App */
	protected $app;

    /**
     * Loads and creates app if it doesn't exits from given $default values
     * This is a helper method, and should be overwritten with app settings
     *
     * @param array $default
     * @param bool $create Will create app if not found
     * @return $this
     * @throws RestfulRecordNotSavedException
     */
	protected function _buildApp( $default = [], $create=true ) {
		$appName = config( 'app.serviceName' );
		$this->app = App::query()->find( $appName );

		if ( !$this->app && $create ) {
			// make app
			$this->app = App::create( $default );
			$this->app->save();

			if ( !$this->app->exists() ) {
			    $error = $this->app->getError();
				unset( $this->app );
				throw new RestfulRecordNotSavedException( "App $appName not able to save: ", $error );
			}
		}

		return $this;
	}

	/**
	 * Loads and creates logo if doesn't exist from given $default values
	 * This is a helper method, and should be overwritten with app settings
	 *
	 * @param array $default
	 * @return $this
	 */
	protected function _buildLogo( $default = [] ) {
		// logo
		if ( !$this->app->hasRelation( 'logo' ) ) {
			$this->app->setRelation( 'logo', $this->buildResources( [ $default ], 'logo' )->first() );
		}

		return $this;
	}

    /**
     * Helper method to create a new collection of resources on the API
     *
     * @param array $resources
     * @param string $type If no type set on a resource, will use this
     * @param string $keyName Key in collection. Pass "-" to use the $resources keys
     * @param \Callable $callable Callable function with signature:
     *  function (RestfulRecord $resource, array $resourceInfo): void
     * @return ModelCollection
     */
	protected function buildResources( $resources, $type, $keyName = 'id', $callable=null ) {
		$col = new ModelCollection();
		foreach ( $resources as $resourceKey => $resourceInfo ) {
			$resourceInfo[ '__app' ] = $this->app;
			if ( !isset( $resourceInfo[ 'type' ] ) ) {
				$resourceInfo[ 'type' ] = $type;
			}
			$options = 'overrideIfExists';
			if ( empty( $resourceInfo[ 'slug' ] ) ) {
				$options .= ' createSlug';
			}
			$resource = Resource::create( $resourceInfo );
			$saved = $resource->save( $options );
			// @todo: need to add throttle and handle bottle-neck scenarios
			sleep(1);
			if ( !$saved ) {
				print( 'Could not save ' . $resourceInfo[ 'type' ] . '. ' . $resourceInfo[ 'slug' ] . '. ' . print_r( $resource->getError(), 1 ) . PHP_EOL);
			}
			else {
				$key = $resource->id;
				if ( $keyName == '-' ) {
					$key = $resourceKey;
				}
				else if ( isset( $resource->{$keyName} ) && is_string( $resource->{$keyName} ) ) {
					$key = $resource->{$keyName};
				}

				$col->put( $key, $resource );
			}

			if(is_callable($callable)) {
			    call_user_func_array($callable, [$resource, $resourceInfo, $saved]);
            }
		}

		return $col;
	}

	protected function removeResources($resources) {
	    foreach($resources as $resourceKey => $resourceInfo) {
	        $resource = Resource::query()->find($resourceInfo);
	        if($resource) {
                $resource->delete();
            }
        }
    }

	/**
	 * @return App
	 */
	public function getApp() {
		return $this->app;
	}

	/**
	 * @param App $app
	 */
	public function setApp( App $app ) {
		$this->app = $app;
	}
}
