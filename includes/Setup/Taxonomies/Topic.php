<?php
namespace CP_Resources\Setup\Taxonomies;

use ChurchPlugins\Setup\Taxonomies\Taxonomy;
use CP_Resources\Admin\Settings;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup for custom taxonomy: Topic
 *
 * @author tanner moushey
 * @since 1.0
 */
class Topic extends Taxonomy  {

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->taxonomy = "cp_resource_topic";

		$this->single_label = apply_filters( "{$this->taxonomy}_single_label", 'Resource Topic' );
		$this->plural_label = apply_filters( "{$this->taxonomy}_plural_label", 'Resource Topics' );

		parent::__construct();
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

		$topic_terms = wp_list_pluck( $data, 'term' );
		return array_combine( array_map( 'esc_attr', $topic_terms ), $topic_terms );
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
		$topics_file = cp_resources()->templates->get_template_hierarchy( '__data/topics.json' );

		if ( ! $topics_file ) {
			return [];
		}

		return apply_filters( "{$this->taxonomy}_get_term_data", json_decode( file_get_contents( $topics_file ) ) );
	}

}
