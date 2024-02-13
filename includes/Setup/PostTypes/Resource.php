<?php
namespace CP_Resources\Setup\PostTypes;

use ChurchPlugins\Setup\PostTypes\PostType;

// Exit if accessed directly
use CP_Resources\Admin\Settings;
use CP_Resources\Setup\Tables\ResourceMeta;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup for custom post type: Item
 *
 * @author costmo
 * @since 1.0
 */
class Resource extends PostType  {

	/**
	 * Child class constructor. Punts to the parent.
	 *
	 * @author costmo
	 */
	protected function __construct() {
		$this->post_type = "cp_resource";

		$this->single_label = apply_filters( "cp_single_{$this->post_type}_label", Settings::get_resource( 'singular_label', 'Resource' ) );
		$this->plural_label = apply_filters( "cp_plural_{$this->post_type}_label", Settings::get_resource( 'plural_label', 'Resources' ) );

		parent::__construct();
	}

	public function add_actions() {
		add_filter( "{$this->post_type}_slug", [ $this, 'custom_slug' ] );
		add_action( 'pre_get_posts', [ $this, 'archive_query' ] );
		add_filter( 'posts_join', [ $this, 'archive_join' ], 10, 2 );
		add_filter( 'posts_where', [ $this, 'archive_where' ], 10, 2 );

		parent::add_actions(); // TODO: Change the autogenerated stub
	}

	/**
	 * Determine if the provided term has any visible content
	 *
	 * @since  1.0.0
	 *
	 * @param $term_id
	 * @param $taxonomy
	 *
	 * @return bool
	 * @author Tanner Moushey, 6/16/23
	 */
	public function has_visible_resources( $term_id, $taxonomy ) {
		$resource_objects   = Settings::get( 'resource_objects', [] );
		array_unshift( $resource_objects, $this->post_type );

		// Set up the query arguments
		$query_args = array(
			'is_resource_query'      => true,
			'post_type'              => $resource_objects,
			'tax_query'              => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'no_found_rows'          => true,
			'cache_results'          => true,
		);

		// Run the query
		$query = new \WP_Query( $query_args );

		return $query->post_count > 0;
	}

	/**
	 *
	 *
	 * @since  1.0.5
	 *
	 * @param $query \WP_Query
	 *
	 * @author Tanner Moushey, 5/22/23
	 */
	public function archive_query( $query ) {
		if ( is_admin() ) {
			return;
		}

		if ( ! $query->is_post_type_archive( $this->post_type ) ) {
			return;
		}

		$resource_objects   = Settings::get( 'resource_objects', [] );
		array_unshift( $resource_objects, $this->post_type );

		$query->set( 'post_type', $resource_objects );
		$query->set( 'is_resource_query', true );

		// Add tax query to include only those taxonomies that should be shown in the archive
		$tax_query = $query->get( 'tax_query', [] );

		$tax_query[] = [
			'taxonomy' => cp_resources()->setup->taxonomies->type->taxonomy,
			'terms'    => cp_resources()->setup->taxonomies->type->get_visible_types(),
		];

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 *
	 *
	 * @since  1.0.5
	 *
	 * @param $query \WP_Query
	 *
	 * @author Tanner Moushey, 5/22/23
	 */
	public function archive_join( $join, $query ) {
		if ( ! $query->get( 'is_resource_query' ) ) {
			return $join;
		}

		global $wpdb;
		$table_name = \CP_Resources\Setup\Tables\Resource::get_instance()->table_name;
		$join .= " INNER JOIN {$table_name} AS resources ON {$wpdb->posts}.ID = resources.origin_id ";

		return $join;
	}

	/**
	 *
	 *
	 * @since  1.0.5
	 *
	 * @param $query \WP_Query
	 *
	 * @author Tanner Moushey, 5/22/23
	 */
	public function archive_where( $where, $query ) {
		if ( ! $query->get( 'is_resource_query' ) ) {
			return $where;
		}

		$where .= " AND resources.hide_archive = 0 ";

		return $where;
	}

	 /* Allow for user defined slug
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function custom_slug() {
		return Settings::get_resource( 'slug', strtolower( sanitize_title( $this->plural_label ) ) );
	}

	/**
	 * Return custom meta keys
	 *
	 * @return array|mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function meta_keys() {
		return ResourceMeta::get_keys();
	}

	/**
	 * Setup arguments for this CPT
	 *
	 * @return array
	 * @author costmo
	 */
	public function get_args() {
		$args              = parent::get_args();
		$args['menu_icon'] = apply_filters( "{$this->post_type}_icon", 'dashicons-book' );

		if ( ! Settings::get( 'archive_enabled', false ) ) {
			$args['has_archive'] = false;
		}

		return $args;
	}

	public function register_metaboxes() {
		$this->meta_details();
	}

	protected function meta_details() {
		$cmb = new_cmb2_box( [
			'id' => 'resource_meta',
			'title' => $this->single_label . ' ' . __( 'Details', 'cp-resources' ),
			'object_types' => [ $this->post_type ],
			'context' => 'normal',
			'priority' => 'high',
			'show_names' => true,
		] );

		$cmb->add_field( [
			'name' => __( 'Resource URL', 'cp-resources' ),
			'desc' => __( 'The URL of the resource.', 'cp-resources' ),
			'id'   => 'resource_url',
			'type' => 'file',
		] );

		cp_resources()->setup::visibility_field( $cmb );
	}

}
