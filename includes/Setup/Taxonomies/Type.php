<?php
namespace CP_Resources\Setup\Taxonomies;

use ChurchPlugins\Helpers;
use CP_Resources\Admin\Settings;
use CP_Resources\Templates;
use ChurchPlugins\Setup\Taxonomies\Taxonomy;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup for custom taxonomy: Scripture
 *
 * @author tanner moushey
 * @since 1.0
 */
class Type extends Taxonomy  {

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->taxonomy = "cp_resource_type";

		$this->single_label = apply_filters( "{$this->taxonomy}_single_label", 'Resource Type' );
		$this->plural_label = apply_filters( "{$this->taxonomy}_plural_label", 'Resource Types' );

		// put our metaboxes above the others
		$this->metabox_priority = 8;

		parent::__construct();
	}

	public function get_edit_url() {
		return admin_url( sprintf( 'edit-tags.php?taxonomy=%s&post_type=%s', $this->taxonomy, cp_resources()->setup->post_types->resource->post_type ) );
	}

	public function add_actions() {
		parent::add_actions();

		add_filter( "{$this->taxonomy}_metabox_field_args", [ $this, 'field_args' ] );
	}

	public function get_args() {
		global $current_page;

		$args = parent::get_args();
		$args['show_ui'] = false;

		if ( $this->taxonomy == Helpers::get_request( 'taxonomy' ) ) {
			$args['show_ui'] = true;
		}

		return $args;
	}

	public function field_args( $args ) {
		$args['attributes'] = [
			'placeholder'                   => sprintf( __( 'Select a %s', 'cp-resources' ), $this->single_label ),
			'data-maximum-selection-length' => '1',
		];

		$args['desc'] = sprintf( __( 'The %2$s for this %1$s. <a href="%3$s" target="_blank">Click here to add a new %2$s.</a>', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label, $this->single_label, $this->get_edit_url() );

		return $args;
	}

	/**
	 * Return the object types for this taxonomy
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_object_types() {
		$types = Settings::get( 'resource_objects', [] );
		$types[] = cp_resources()->setup->post_types->resource->post_type;

		return $types;
	}

	/**
	 * A key value array of term data "esc_attr( Name )" : "Name"
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_terms() {
		$data = $this->get_term_data();

		if ( empty( $data ) ) {
			return [];
		}

		$terms = [];

		foreach ( $data as $term ) {
			$terms[ $term->name ] = $term->name;
		}

		return $terms;
	}

	/**
	 * Get term data from json file
	 *
	 * @return array
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_term_data() {
		$terms = get_terms( [ 'taxonomy' => $this->taxonomy, 'hide_empty' => false ] );

		return apply_filters( "{$this->taxonomy}_get_term_data", $terms );
	}

	/**
	 * Get Types that are assigned the provided visibility
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/24/23
	 */
	public function get_types_by_visibility( $visibility = 'hidden' ) {
		$terms = $this->get_term_data();
		$hidden_terms = [];

		foreach( $terms as $term ) {
			if ( $visibility != get_term_meta( $term->term_id, $this->taxonomy . '_visibility', true ) ) {
				continue;
			}

			$hidden_terms[] = $term->term_id;
		}

		return apply_filters( 'cp_resources_type_get_hidden_types', $hidden_terms );
	}

	/**
	 * Get Types that should be included in the Archive query
	 *
	 * @since  1.0.0
	 *
	 * @return mixed|void
	 * @author Tanner Moushey, 5/24/23
	 */
	public function get_visible_types() {
		$terms = $this->get_term_data();
		$visible_terms = [];

		foreach( $terms as $term ) {
			if ( 'hide' == get_term_meta( $term->term_id, $this->taxonomy . '_visibility', true ) ) {
				continue;
			}

			$visible_terms[] = $term->term_id;
		}

		return apply_filters( 'cp_resources_type_get_visible_types', $visible_terms );
	}


	public function register_metaboxes() {

		// only show the Type metabox if we are on a Resource... otherwise we set it automatically
		if ( cp_resources()->setup->post_types->resource->post_type == Helpers::get_request( 'post_type', get_post_type( Helpers::get_request( 'post' ) ) ) ) {
			parent::register_metaboxes();
		}

		$args = apply_filters( "{$this->taxonomy}_term_metabox_args", [
			'id'           => sprintf( '%s_visibility', $this->taxonomy ),
			'object_types' => [ 'term' ],
			'taxonomies'   => [ $this->taxonomy ],
			'title'        => __( "Visibility", 'cp-resources' ),
			//			'context'      => 'side',
			'show_names'   => true,
			'priority'     => 'default',
			'closed'       => false,
		], $this );

		$cmb = new_cmb2_box( $args );

		$cmb->add_field( apply_filters( "{$this->taxonomy}_term_metabox_field_args", [
			'name'       => __( 'Thumbnail', 'cp-resources' ), // sprintf( , $this->plural_label ),
			'id'         => $this->taxonomy . '_thumbnail',
			'desc'       => sprintf( __( 'The thumbnail to use for %s using this Type.', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'type'       => 'file',
			'query_args' => array(
				'type' => array(
					'image/gif',
					'image/jpeg',
					'image/png',
				),
			),
		], $this ) );

		$cmb->add_field( apply_filters( "{$this->taxonomy}_term_metabox_field_args", [
			'name'    => __( 'Visibility', 'cp-resources' ), // sprintf( , $this->plural_label ),
			'id'      => $this->taxonomy . '_visibility',
			'desc'    => __( 'Define whether or not resources in this Type should show up in the Resources archive.', 'cp-resources' ),
			'type'    => 'select',
			'options' => [
				'show'          => 'Always Show',
				'hide'          => 'Always Hide',
				'optional-show' => 'Optional, Default: Show',
				'optional-hide' => 'Optional, Default: Hide',
			],
		], $this ) );
	}

}
