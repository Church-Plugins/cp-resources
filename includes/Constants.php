<?php
/**
 * Plugin constants
 */

/**
 * Setup/config constants
 */
if( !defined( 'CP_RESOURCES_PLUGIN_FILE' ) ) {
	 define ( 'CP_RESOURCES_PLUGIN_FILE',
	 	dirname( dirname( __FILE__ ) ) . "/cp-resources.php"
	);
}
if( !defined( 'CP_RESOURCES_PLUGIN_DIR' ) ) {
	 define ( 'CP_RESOURCES_PLUGIN_DIR',
	 	plugin_dir_path( CP_RESOURCES_PLUGIN_FILE )
	);
}
if( !defined( 'CP_RESOURCES_PLUGIN_URL' ) ) {
	 define ( 'CP_RESOURCES_PLUGIN_URL',
	 	plugin_dir_url( CP_RESOURCES_PLUGIN_FILE )
	);
}
if( !defined( 'CP_RESOURCES_INCLUDES' ) ) {
	 define ( 'CP_RESOURCES_INCLUDES',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'includes'
	);
}
if( !defined( 'CP_RESOURCES_TEXT_DOMAIN' ) ) {
	 define ( 'CP_RESOURCES_TEXT_DOMAIN',
		'cp_resources'
   );
}
if( !defined( 'CP_RESOURCES_DIST' ) ) {
	 define ( 'CP_RESOURCES_DIST',
		CP_RESOURCES_PLUGIN_URL . "/dist/"
   );
}

/**
 * Licensing constants
 */
if( !defined( 'CP_RESOURCES_STORE_URL' ) ) {
	 define ( 'CP_RESOURCES_STORE_URL',
	 	'https://churchplugins.com'
	);
}
if( !defined( 'CP_RESOURCES_ITEM_NAME' ) ) {
	 define ( 'CP_RESOURCES_ITEM_NAME',
	 	'CP Resources'
	);
}

/**
 * App constants
 */
if( !defined( 'CP_RESOURCES_APP_PATH' ) ) {
	 define ( 'CP_RESOURCES_APP_PATH',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'app'
	);
}
if( !defined( 'CP_RESOURCES_ASSET_MANIFEST' ) ) {
	 define ( 'CP_RESOURCES_ASSET_MANIFEST',
	 	plugin_dir_path( dirname( __FILE__ ) ) . 'app/build/asset-manifest.json'
	);
}
