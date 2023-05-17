<?php
/**
 * Templating functionality
 */

namespace CP_Resources;

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}


/**
 * Handle views and template files.
 */
class Templates extends \ChurchPlugins\Templates {


	/**
	 * Return the post types for this plugin
	 *
	 * @return mixed
	 * @author Tanner Moushey, 5/11/23
	 */
	public function get_post_types() {
		return cp_resources()->setup->post_types->get_post_types();
	}

	/**
	 * Return the taxonomies for this plugin
	 *
	 * @return mixed
	 * @author Tanner Moushey, 5/11/23
	 */
	public function get_taxonomies() {
		return cp_resources()->setup->taxonomies->get_taxonomies();
	}

	/**
	 * Return the plugin path for the current plugin
	 *
	 * @return mixed
	 * @author Tanner Moushey, 5/11/23
	 */
	public function get_plugin_path() {
		return cp_resources()->get_plugin_path();
	}

	/**
	 * Get the slug / id for the current plugin
	 *
	 * @return mixed
	 * @author Tanner Moushey, 5/11/23
	 */
	public function get_plugin_id() {
		return cp_resources()->get_id();
	}

}
