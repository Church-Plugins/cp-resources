<?php

namespace CP_Resources\Setup;

use ChurchPlugins\Exception;
use CP_Resources\Admin\Settings;
use CP_Resources\Models\Resource;

/**
 * Setup plugin initialization
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;

	/**
	 * @var Tables\Init
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public $tables;

	/**
	 * @var PostTypes\Init;
	 */
	public $post_types;

	/**
	 * @var Taxonomies\Init;
	 */
	public $taxonomies;

	/**
	 * Only make one instance of Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Admin init includes
	 *
	 * @return void
	 */
	protected function includes() {
		Shortcode::get_instance();
		$this->tables = Tables\Init::get_instance();
		$this->post_types = PostTypes\Init::get_instance();
		$this->taxonomies = Taxonomies\Init::get_instance();
	}

	protected function actions() {
		add_action( 'cmb2_admin_init', [ $this, 'register_metaboxes' ], 5 );
		add_filter( 'cmb2_override_meta_save', [ $this, 'meta_save_override' ], 10, 4 );
		add_filter( 'cmb2_override_meta_remove', [ $this, 'meta_save_override' ], 10, 4 );
		add_filter( 'cmb2_override_meta_value', [ $this, 'meta_get_override' ], 10, 4 );
	}

	/** Actions ***************************************************/

	public function register_metaboxes() {
		$types   = Settings::get( 'resource_objects', [] );
		$types[] = cp_resources()->setup->post_types->resource->post_type;

		$args = apply_filters( "cp_resources_is_resource_metabox_args", [
			'id'           => 'resource_set',
			'object_types' => $types,
			'title'        => sprintf( __( '%s Settings', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'context'      => 'side',
			'show_names'   => false,
			'priority'     => 'default',
			'closed'       => false,
		], $this );

		$cmb = new_cmb2_box( $args );

		$cmb->add_field( [
			'name' => sprintf( __( 'This is a %s', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'id'   => '_is_resource',
			'type' => 'checkbox',
			'desc' => sprintf( __( 'This is a %s'), cp_resources()->setup->post_types->resource->single_label ),
		] );

		$cmb->add_field( [
			'name'    => __( 'Visibility', 'cp-resources' ), // sprintf( , $this->plural_label ),
			'id'      => '_hide_resource',
			'desc'    => __( 'Hide this item from the Resources archive.', 'cp-resources' ),
			'type'    => 'checkbox',
		] );
	}

	/**
	 * Hijack the meta save filter to save to our tables
	 *
	 * Currently will also save to postmeta
	 *
	 * @param $return
	 * @param $data_args
	 * @param $field_args
	 * @param $field
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_save_override( $return, $data_args, $field_args, $field ) {

		$post_id = $data_args['id'];

		if ( $data_args['field_id'] !== '_is_resource' ) {
			return $return;
		}

		$return = true;

		try {
			if ( isset( $data_args['value'] ) ) {
				$result = Resource::get_instance_from_origin( $post_id );
			} else {
				$resource = Resource::get_instance_from_origin( $post_id, false );

				if ( $resource ) {
					$resource->delete();
				}
			}
		} catch ( Exception $e ) {
			error_log( $e );
			$result = false;
		}

		// if the update failed, let CMB2 do it's thing
		if ( empty( $result ) ) {
			return $return;
		}

		return true;
	}

	/**
	 * return terms for metabox
	 *
	 * @param $data
	 * @param $object_id
	 * @param $data_args
	 * @param $field
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_get_override( $data, $object_id, $data_args, $field ) {

		if ( $data_args['field_id'] != '_is_resource' ) {
			return $data;
		}

		try {
			$resource = Resource::get_instance_from_origin( $object_id, false );
		} catch ( Exception $e ) {
			error_log( $e );
			$resource = false;
		}

		return $resource ? 'on' : '';
	}

}
