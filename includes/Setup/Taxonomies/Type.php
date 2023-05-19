<?php
namespace CP_Resources\Setup\Taxonomies;

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

		parent::__construct();
	}

	public function add_actions() {
		parent::add_actions();

		add_filter( "{$this->taxonomy}_metabox_field_args", [ $this, 'field_args' ] );
	}

	public function get_args() {
		$args = parent::get_args();

		$args['show_ui'] = true;

		return $args;
	}

	public function field_args( $args ) {
		$args['attributes'] = [
			'placeholder'                   => sprintf( __( 'Select a %s', 'cp-resources' ), $this->single_label ),
			'data-maximum-selection-length' => '1',
		];

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


	public function register_metaboxes() {
		parent::register_metaboxes();

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

		$cmb->add_field( apply_filters( "{$this->taxonomy}_metabox_field_args", [
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
