<?php

namespace CP_Resources\Models;

use ChurchPlugins\Exception;
use ChurchPlugins\Models\Table;
use CP_Resources\Setup\Tables\ResourceMeta;

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
	protected $hide_archive = 0;

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
	 * Get all types
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_all_resources( $object_id = 0 ) {
		global $wpdb;

		$meta     = ResourceMeta::get_instance();
		$instance = new self();

		if ( $object_id ) {
			$sql = sprintf( 'SELECT %1$s.* FROM %1$s
INNER JOIN %2$s
ON %1$s.id = %2$s.resource_id
WHERE %2$s.key = "relationship" AND %2$s.secondary_id = %3$d
ORDER BY %2$s.order ASC', $instance->table_name, $meta->table_name, $object_id );
		} else {
			$sql = sprintf( 'SELECT %1$s.* FROM %1$s ORDER BY %1$s.title ASC', $instance->table_name );
		}

		$resources = $wpdb->get_results( $sql );

		if ( ! $resources ) {
			$resources = [];
		}

		return apply_filters( 'cp_resources_get_all_resources', $resources, $object_id );
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
	public static function get_instance_from_origin( $origin_id, $create = false ) {
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
	 * Create a new Resource
	 *
	 * @since  1.0.0
	 *
	 * @param $data       array {
	 *                          The data for the new resource object
	 *
	 * @type string $title      The title for the new resource. Required.
	 * @type string $url        The url for the Resource
	 * @type string $visibility Whether the resource should be hidden from the archive. Set to 'on' for hidden,
	 *                              otherwise, keep blank.
	 * @type array  $type       The Type taxonomy term to use for this Resource.
	 * @type array  $topic      The Topic taxonomy term to use for this Resource.
	 * }
	 *
	 * @param int $object_id    The object to attach this resource to. Optional.
	 * @param int $order        The order for the object relationship.
	 *
	 * @return bool|static
	 * @throws Exception
	 * @author Tanner Moushey, 5/26/23
	 */
	public static function create( $data, $object_id = 0, $order = 0 ) {
		$data = wp_parse_args( $data, [
			'visibility' => '',
		] );

		if ( empty( $data['title'] ) ) {
			throw new Exception( 'title is a required parameter to create a resource.' );
		}

		if ( empty( $data['url'] ) ) {
			throw new Exception( 'url is a required parameter to create a resource.' );
		}

		$resource_id = wp_insert_post( [
			'post_type'   => cp_resources()->setup->post_types->resource->post_type,
			'post_title'  => $data['title'],
			'post_status' => 'publish',
			'post_meta'   => [
				'resource_url'   => $data['url'],
				'_hide_resource' => $data['visibility'],
			],
		], true );

		if ( ! $resource_id || is_wp_error( $resource_id ) ) {
			throw new Exception( 'Unable to create new resource.' );
		}

		if ( ! empty( $data['type'] ) ) {
			wp_set_post_terms( $resource_id, $data['type'], cp_resources()->setup->taxonomies->type->taxonomy );
		}

		if ( ! empty( $data['topic'] ) ) {
			wp_set_post_terms( $resource_id, $data['topic'], cp_resources()->setup->taxonomies->topic->taxonomy );
		}

		$resource = self::get_instance_from_origin( $resource_id );

		if ( $object_id ) {
			$resource->update_object_relationship( $object_id, $order );
		}

		$resource->update_meta( [ 'key' => 'resource_url', 'value' => esc_url( $data['url'] ) ] );

		$is_hidden = $data['visibility'] ? 1 : 0;

		// if the Resource Type is set to Always Show, then don't allow the resource to be set to hidden
		if ( 'show' == $resource->get_type_visibility() ) {
			$is_hidden = 0;
			delete_post_meta( $object_id, '_hide_resource' );
		}

		$resource->update( [
			'hide_archive' => $is_hidden,
			'status'       => get_post_status( $object_id )
		] );

		return $resource;
	}

	/**
	 * Whether to show this resource in the archive
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/17/23
	 */
	public function is_hidden() {
		$hidden = $this->hide_archive;

		switch( $this->get_type_visibility() ) {
			case 'show' : $hidden = 0;
				break;
			case 'hide' : $hidden = 1;
		}

		return apply_filters( 'cp_resources_resource_is_hidden', $hidden, $this );
	}

	/**
	 * Return the types for this resource.
	 *
	 * @since  1.0.0
	 *
	 * @param $field
	 *
	 * @return false|mixed|\WP_Term
	 * @author Tanner Moushey, 5/17/23
	 */
	public function get_type( $field = 'object' ) {
		$types = get_the_terms( $this->origin_id, 'cp_resource_type' );

		if ( is_wp_error( $types ) || empty( $types ) ) {
			return false;
		}

		$type = apply_filters( 'cp_resources_resource_get_type', $types[0], $this );

		if ( 'object' == $field ) {
			return $type;
		}

		return $type->$field;
	}

	/**
	 * Get the visibility settings for the main Resource Type
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/17/23
	 */
	public function get_type_visibility() {
		$type = $this->get_type( 'term_id' );
		if ( ! $visibility = get_term_meta( $type, 'cp_resource_type_visibility', true ) ) {
			$visibility = 'optional-show';
		}

		return apply_filters( 'cp_resources_resource_get_type_visibility', $visibility, $type, $this );
	}

	public function has_object_relationship( $object_id ) {
		global $wpdb;

		$meta_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $this->meta_table_name . ' WHERE `key` = "relationship" AND resource_id = %d AND secondary_id = %d', absint( $this->id ), $object_id ) );

		return apply_filters( 'cp_resources_resource_has_object_relationship', $meta_id, $this );
	}

	/**
	 * Update the object relationship for this Resource
	 *
	 * @since  1.0.5
	 *
	 * @param $object_id
	 * @param $order
	 *
	 * @return bool|int|\mysqli_result|resource|null
	 * @author Tanner Moushey, 5/17/23
	 */
	public function update_object_relationship( $object_id, $order = 0 ) {
		global $wpdb;

		// Initialise column format array
		$column_formats = static::get_meta_columns();

		$data = [ 'key' => 'relationship', 'resource_id' => $this->id, 'secondary_id' => $object_id, 'order' => $order ];

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );


		if ( $meta_id = $this->has_object_relationship( $object_id ) ) {
			$result = $wpdb->update( static::get_prop( 'meta_table_name' ), $data, array( 'id' => absint( $meta_id ) ), $column_formats );
		} else {
			$data = wp_parse_args( $data, $this->get_meta_column_defaults() );
			$wpdb->insert( static::get_prop( 'meta_table_name' ), $data, $column_formats );
			$result = $wpdb->insert_id;
		}

		wp_cache_delete( $this->id, static::get_prop( 'cache_group' ) . '_meta' );

		static::set_last_changed();

		return $result;
	}

	/**
	 * Delete the object relationship for this Resource
	 *
	 * @since  1.0.5
	 *
	 * @param $object_id
	 *
	 * @return bool
	 * @throws Exception
	 * @author Tanner Moushey, 5/17/23
	 */
	public function delete_object_relationship( $object_id ) {
		return $this->delete_meta( $object_id, 'secondary_id' );
	}

	/**
	 * Update the resource url for this Resource
	 *
	 * @since  1.0.0
	 *
	 * @param string $url
	 *
	 * @author Tanner Moushey, 5/26/23
	 */
	public function update_url( $url ) {
		$url = esc_url( $url );
		update_post_meta( $this->origin_id, 'resource_url', $url );
		$this->update_meta( [ 'key' => 'resource_url', 'value' => esc_url( $url ) ] );
	}

	/**
	 * Get columns and formats
	 *
	 * @since   1.0
	*/
	public static function get_columns() {
		return array(
			'id'           => '%d',
			'origin_id'    => '%d',
			'title'        => '%s',
			'status'       => '%s',
			'hide_archive' => '%d',
			'published'    => '%s',
			'updated'      => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   1.0
	*/
	public static function get_column_defaults() {
		return array(
			'origin_id'    => 0,
			'title'        => '',
			'status'       => '',
			'hide_archive' => 0,
			'published'    => date( 'Y-m-d H:i:s' ),
			'updated'      => date( 'Y-m-d H:i:s' ),
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
