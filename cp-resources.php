<?php
/**
 * Plugin Name: CP Resources
 * Plugin URL: https://churchplugins.com
 * Description: Church resources plugin
 * Version: 0.1.0
 * Author: Church Plugins
 * Author URI: https://churchplugins.com
 * Text Domain: cp-resources
 * Domain Path: languages
 */

if( !defined( 'CP_RESOURCES_PLUGIN_VERSION' ) ) {
	 define ( 'CP_RESOURCES_PLUGIN_VERSION',
	 	'0.1.0'
	);
}

require_once( dirname( __FILE__ ) . "/includes/Constants.php" );

require_once( CP_RESOURCES_PLUGIN_DIR . "/includes/ChurchPlugins/init.php" );
require_once( CP_RESOURCES_PLUGIN_DIR . 'vendor/autoload.php' );

use CP_Resources\Init as CP_Init;

/**
 * @var CP_Resources\Init
 */
global $cp_resources;
$cp_resources = cp_resources();

/**
 * @return CP_Resources\Init
 */
function cp_resources() {
	return CP_Init::get_instance();
}

/**
 * Load plugin text domain for translations.
 *
 * @return void
 */
function cp_resources_load_textdomain() {

	// Traditional WordPress plugin locale filter
	$get_locale = get_user_locale();

	/**
	 * Defines the plugin language locale used in RCP.
	 *
	 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
	 *                  otherwise uses `get_locale()`.
	 */
	$locale        = apply_filters( 'plugin_locale',  $get_locale, 'cp-resources' );
	$mofile        = sprintf( '%1$s-%2$s.mo', 'cp-resources', $locale );

	// Setup paths to current locale file
	$mofile_global = WP_LANG_DIR . '/cp-resources/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		// Look in global /wp-content/languages/cp-resources folder
		load_textdomain( 'cp-resources', $mofile_global );
	}

}
add_action( 'init', 'cp_resources_load_textdomain' );

// lifecycle hooks
register_activation_hook( __FILE__, function() { do_action( 'cp_resources_activated' ); } );
register_deactivation_hook( __FILE__, function() { do_action( 'cp_resources_deactivated' ); } );
