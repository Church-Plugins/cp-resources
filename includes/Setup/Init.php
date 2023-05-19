<?php

namespace CP_Resources\Setup;

use ChurchPlugins\Exception;
use ChurchPlugins\Helpers;
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
	 * @var
	 */
	protected static $_doing_resources_create = false;

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
		add_filter( 'cmb2_save_post_fields_object_resources', [ $this, 'save_object_resources' ], 10 );
		add_action( 'save_post', [ $this, 'save_resource' ], 500 );
	}

	/** Actions ***************************************************/

	/**
	 * Register metaboxes
	 *
	 * @since  1.0.0
	 *
	 * @throws Exception
	 * @author Tanner Moushey, 5/18/23
	 */
	public function register_metaboxes() {
		$this->resource_object_meta();
		$this->has_resources_meta();
	}

	/**
	 * Meta fields for objects that can be resources
	 *
	 * @since  1.0.0
	 *
	 * @throws Exception
	 * @author Tanner Moushey, 5/18/23
	 */
	protected function resource_object_meta() {
		$resource_objects   = Settings::get( 'resource_objects', [] );
		$resource_objects[] = cp_resources()->setup->post_types->resource->post_type;

		$args = apply_filters( "cp_resources_is_resource_metabox_args", [
			'id'           => 'resource_set',
			'object_types' => $resource_objects,
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

		self::visibility_field( $cmb );
	}

	/**
	 * Meta fields for objects that can be assigned resources
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey, 5/18/23
	 */
	protected function has_resources_meta() {
		$has_resources      = Settings::get( 'has_resources', [] );
		$all_resources = wp_list_pluck( Resource::get_all_resources(), 'title', 'id' );

		$args = apply_filters( "cp_resources_has_resources_metabox_args", [
			'id'           => 'object_resources',
			'object_types' => $has_resources,
			'title'        => sprintf( __( '%s', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'context'      => 'normal',
			'show_names'   => true,
			'priority'     => 'high',
			'closed'       => false,
		], $this );

		$cmb = new_cmb2_box( $args );

		$cmb->add_field( [
			'name'    => sprintf( __( 'Object %s', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'id'      => '_object_resources',
			'type'    => 'pw_multiselect',
			'desc'    => sprintf( __( 'Enter the %s for this item.' ), cp_resources()->setup->post_types->resource->plural_label ),
			'options' => $all_resources,
		] );

		$group_field_id = $cmb->add_field( [
			'id'         => '_cp_new_resources',
			'type'       => 'group',
			'repeatable' => true,
			'options'    => [
				'group_title'    => cp_resources()->setup->post_types->resource->single_label . ' {#}',
				'add_button'     => __( 'Add Another', 'cp-library' ) . ' ' . cp_resources()->setup->post_types->resource->single_label,
				'remove_button'  => __( 'Remove', 'cp-library' ) . ' ' . cp_resources()->setup->post_types->resource->single_label,
			    'sortable'      => true,
				'remove_confirm' => sprintf( esc_html__( 'Are you sure you want to remove this %s?', 'cp-library' ), cp_resources()->setup->post_types->resource->single_label ),
				'closed' => false,
			],
		] );

		$cmb->add_group_field( $group_field_id, [
			'name' => 'Title',
			'id'   => 'title',
			'desc' => sprintf( __( 'The title for this %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'type' => 'text',
		] );

		$cmb->add_group_field( $group_field_id, [
			'name' => 'URL',
			'id'   => 'url',
			'desc' => sprintf( __( 'The url for this %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'type' => 'file',
		] );

		$cmb->add_group_field( $group_field_id, [
			'name' => 'Visibility',
			'id'   => 'visibility',
			'desc' => sprintf( __( 'Hide this item from the %s archive.', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'type' => 'checkbox',
		] );

		$cmb->add_group_field( $group_field_id, [
			'name' => 'Type',
			'id'   => 'type',
			'desc' => sprintf( __( 'The Type for this %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'type' => 'pw_multiselect',
			'options' => cp_resources()->setup->taxonomies->type->get_terms_for_metabox(),
		] );

		$cmb->add_group_field( $group_field_id, [
			'name' => 'Topic',
			'id'   => 'topic',
			'desc' => sprintf( __( 'The Topic for this %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'type' => 'pw_multiselect',
			'options' => cp_resources()->setup->taxonomies->topic->get_terms_for_metabox(),
		] );

	}

	/**
	 * Handle resource save, both cp_resource and other post types
	 *
	 * @since  1.0.0
	 *
	 * @param $object_id
	 *
	 * @author Tanner Moushey, 5/18/23
	 */
	public function save_resource( $object_id ) {

		if ( 'auto-draft' == get_post_status( $object_id ) || wp_is_post_autosave( $object_id ) ) {
			return;
		}

		try {

			$is_resource = get_post_meta( $object_id, '_is_resource', true ) || get_post_type( $object_id ) === $this->post_types->resource->post_type;

			// if this item is not set as a resource, make sure there isn't an attached resource and delete if there is
			if ( empty( $is_resource ) ) {
				$resource = Resource::get_instance_from_origin( $object_id, false );

				if ( $resource ) {
					$resource->delete();
				}

				return;
			}

			$resource    = Resource::get_instance_from_origin( $object_id );
			$is_hidden = get_post_meta( $object_id, '_hide_resource', true ) ? 1 : 0;

			// if the Resource Type is set to Always Show, then don't allow the resource to be set to hidden
			if ( 'show' == $resource->get_type_visibility() ) {
				$is_hidden = 0;
				delete_post_meta( $object_id, '_hide_resource' );
			}

			$resource->update( [ 'title' => get_the_title( $object_id ), 'hide_archive' => $is_hidden, 'status' => get_post_status( $object_id ) ] );
		} catch ( Exception $e ) {
			error_log( $e );
		}

	}

	/**
	 * Create new resources for object
	 *
	 * @since  1.0.0
	 *
	 * @param $object_id
	 *
	 * @author Tanner Moushey, 5/18/23
	 */
	public function save_object_resources( $object_id ) {

		$items = get_post_meta( $object_id, '_cp_new_resources', true );

		if ( empty( $items ) ) {
			return;
		}

		// only create resources once
		if ( self::$_doing_resources_create ) {
			delete_post_meta( $object_id, '_cp_new_resources' );
			return;
		}
		self::$_doing_resources_create = true;

		$resources = Resource::get_all_resources( $object_id );
		$count = count( $resources );
		$count ++;

		// don't save CMB2 fields after this point
		add_action( 'cmb2_can_save', '__return_false' );

		foreach ( $items as $index => $data ) {

			// we must have a title
			if ( empty( $data['title'] ) ) {
				continue;
			}

			try {
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

				wp_set_post_terms( $resource_id, $data['type'], cp_resources()->setup->taxonomies->type->taxonomy );
				wp_set_post_terms( $resource_id, $data['topic'], cp_resources()->setup->taxonomies->topic->taxonomy );

				$resource = Resource::get_instance_from_origin( $resource_id );
				$resource->update_object_relationship( $object_id, $count + $index );
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

			} catch ( Exception $e ) {
				error_log( $e );
			}
		}

		// new resources do not persist, they get added to the resources multiselect
		delete_post_meta( $object_id, '_cp_new_resources' );
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

		if ( $data_args['field_id'] !== '_object_resources' ) {
			return $return;
		}

		$old_resources = wp_list_pluck( Resource::get_all_resources( $post_id ), 'id' );

		if ( isset( $data_args['value'] ) ) {
			foreach( $data_args['value'] as $index => $resource_id ) {
				try {
					$resource = Resource::get_instance( $resource_id );
					$resource->update_object_relationship( $post_id, $index );
				} catch ( Exception $e ) {
					error_log( $e );
				}
			}

			// remove all new values from the old resources array
			$old_resources = array_diff( $old_resources, $data_args['value'] );
		}

		// loop through the original resources array and remove those that no longer exist
		foreach( $old_resources as $resource_id ) {
			try {
				$resource = Resource::get_instance( $resource_id );
				$resource->delete_object_relationship( $post_id );
			} catch( Exception $e ) {
				error_log( $e );
			}
		}

		return $return;
	}

	/**
	 * return terms for metabox
	 *
	 * @param $data
	 * @param $object_id
	 * @param $data_args
	 * @param $field
	 *
	 * @return string|array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_get_override( $data, $object_id, $data_args, $field ) {

		if ( ! in_array( $data_args['field_id'], [ '_is_resource', '_hide_resource', '_object_resources' ] ) ) {
			return $data;
		}

		if ( '_object_resources' == $data_args['field_id'] ) {
			return wp_list_pluck( Resource::get_all_resources( $object_id ), 'id' );
		}

		try {
			$resource = Resource::get_instance_from_origin( $object_id, false );
		} catch ( Exception $e ) {
			error_log( $e );
			$resource = false;
		}

		if ( ! $resource ) {
			return '';
		}

		switch( $data_args['field_id'] ) {
			case '_is_resource' :
				return 'on';
			case '_hide_resource' :
				return $resource->is_hidden() ? 'on' : '';
		}

		return '';
	}

	/**
	 * Output the visibility field or a textbox stating the visibility override
	 *
	 * @since  1.0.0
	 *
	 * @param $cmb
	 * @param $post_id
	 *
	 * @throws Exception
	 * @author Tanner Moushey, 5/18/23
	 */
	public static function visibility_field( $cmb, $post_id = 0 ) {
		$visibility_override = false;

		if ( ! $post_id ) {
			$post_id = Helpers::get_request( 'post', false );
		}

		if ( $post_id ) {
			$resource = Resource::get_instance_from_origin( $post_id, false );

			if ( $resource && in_array( $resource->get_type_visibility(), [ 'show', 'hide' ] ) ) {
				$visibility_override = true;
			}
		}

		if ( $visibility_override ) {
			$cmb->add_field( [
				'id'   => '_hide_resource_disabled',
				'desc' => sprintf( __( 'This item is set to always %s because of the Resource Type. Change the Resource Type and then refresh the page to edit the visibility.', 'cp-resources' ), ucwords( $resource->get_type_visibility() ) ),
				'type' => 'title',
			] );
		} else {
			$cmb->add_field( [
				'name' => __( 'Visibility', 'cp-resources' ), // sprintf( , $this->plural_label ),
				'id'   => '_hide_resource',
				'desc' => __( 'Hide this item from the Resources archive.', 'cp-resources' ),
				'type' => 'checkbox',
			] );
		}
	}

}
