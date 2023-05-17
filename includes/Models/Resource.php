<?php

namespace CP_Resources\Models;

use ChurchPlugins\Exception;
use ChurchPlugins\Models\Table;

/**
 * Item DB Class
 *
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Item Class
 *
 * @since 1.0.0
 */
class Resource extends Table  {

	/**
	 * Item speakers
	 *
	 * @var bool
	 */
	protected $speakers = false;

	/**
	 * Item service type
	 *
	 * @var bool
	 */
	protected $service_types = false;

	/**
	 * Item types
	 *
	 * @var bool
	 */
	protected $types = false;

	public function init() {
		$this->type = 'resource';
		$this->post_type = 'cp_resource';

		parent::init();

		$this->table_name  = $this->prefix . 'cp_' . $this->type;
		$this->meta_table_name  = $this->prefix . 'cp_' . $this->type . "_meta";
	}

	/**
	 * Setup instance using an origin id
	 *
	 * Overwriting parent class to remove the post_type check. Resources can be multiple
	 * post types.
	 *
	 * @param $origin_id
	 * @param $create | Should we create the resource if we don't find it?
	 *
	 * @return bool | static self
	 * @throws Exception
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_instance_from_origin( $origin_id, $create = true ) {
		global $wpdb;

		$origin_id = apply_filters( 'cp_origin_id', absint( $origin_id ) );

		if ( ! $origin_id ) {
			return false;
		}

		if ( ! get_post( $origin_id ) ) {
			throw new Exception( 'That post does not exist.' );
		}

		$post_status = get_post_status( $origin_id );
		if ( 'auto-draft' == $post_status ) {
			throw new Exception( 'No instance retrieved for auto-draft' );
		}

		$object = wp_cache_get( $origin_id, static::get_prop( 'cache_group_origin' ) );

		if ( ! $object ) {

			/**
			 * Allow filtering the used ID in case we need to set it to another site on a multisite
			 *
			 * Warning: if this filter is used, then the provided ID will belong to another blog and will not provide
			 * the correct data if the post is accessed without switching to that blog
			 */
			$queried_id = apply_filters( 'cp_origin_id_sql', $origin_id );

			$sql = apply_filters( 'cp_instance_from_origin_sql', $wpdb->prepare( "SELECT * FROM " . static::get_prop( 'table_name' ) . " WHERE origin_id = %s LIMIT 1;", $queried_id ), $queried_id, $origin_id, get_called_class() );
			$object = $wpdb->get_row( $sql );

			// if object does not exist, create it
			if ( ! $object ) {

				// if we shouldn't create the resource, return false
				if ( ! $create ) {
					return false;
				}

				$data   = [ 'origin_id' => $queried_id, 'status' => $post_status ];
				$object = static::insert( $data );
			}

			wp_cache_add( $object->id, $object, static::get_prop( 'cache_group' ) );
			wp_cache_add( $origin_id, $object, static::get_prop( 'cache_group_origin' ) );
		}

		$class = get_called_class();
		return new $class( $object );
	}

	/**
	 * Get columns and formats
	 *
	 * @since   1.0
	*/
	public static function get_columns() {
		return array(
			'id'        => '%d',
			'origin_id' => '%d',
			'title'     => '%s',
			'status'    => '%s',
			'published' => '%s',
			'updated'   => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   1.0
	*/
	public static function get_column_defaults() {
		return array(
			'origin_id' => 0,
			'title'     => '',
			'status'    => '',
			'published' => date( 'Y-m-d H:i:s' ),
			'updated'   => date( 'Y-m-d H:i:s' ),
		);
	}


	/**
	 * Get meta columns and formats
	 *
	 * @since   1.0
	*/
	public static function get_meta_columns() {
		return array(
			'id'           => '%d',
			'key'          => '%s',
			'value'        => '%s',
			'resource_id'  => '%d',
			'secondary_id' => '%d',
			'order'        => '%d',
			'published'    => '%s',
			'updated'      => '%s',
		);
	}

	/**
	 * Get default meta column values
	 *
	 * @since   1.0
	*/
	public function get_meta_column_defaults() {
		return array(
			'key'          => '',
			'value'        => '',
			'resource_id'  => $this->id,
			'secondary_id' => 0,
			'order'        => 0,
			'published'    => date( 'Y-m-d H:i:s' ),
			'updated'      => date( 'Y-m-d H:i:s' ),
		);
	}

}
