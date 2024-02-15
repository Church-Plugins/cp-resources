<?php
namespace CP_Resources;

use CP_Resources\Admin\Settings;
use CP_Resources\Controllers\Shortcode as Shortcode_Controller;

/**
 * Provides the global $cp_resources object
 *
 * @author costmo
 */
class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var Setup\Init
	 */
	public $setup;

	/**
	 * @var API\Init
	 */
	public $api;

	/**
	 * @var Admin\Init
	 */
	public $admin;

	/**
	 * @var Templates
	 */
	public $templates;

	public $enqueue;

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
	 * Class constructor: Add Hooks and Actions
	 *
	 */
	protected function __construct() {
		$this->enqueue = new \WPackio\Enqueue( 'cpResources', 'dist', $this->get_version(), 'plugin', CP_RESOURCES_PLUGIN_FILE );
		add_action( 'cp_core_loaded', [ $this, 'maybe_setup' ], - 9999 );
		add_action( 'init', [ $this, 'maybe_init' ] );
	}

	/**
	 * Plugin setup entry hub
	 *
	 * @return void
	 */
	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		$cp = \ChurchPlugins\Setup\Init::get_instance();

		Setup\Tables\Init::get_instance();

		// make sure needed tables are installed
		if ( ! $cp->is_installed() ) {
			add_filter( 'cp_table_needs_check', '__return_true' );
			$cp->update_install();

			if ( ! $cp->is_installed() ) {
				return;
			}
		}

		$this->includes();
		$this->actions();
		$this->app_init();

		$this->setup = Setup\Init::get_instance();
		$this->api   = API\Init::get_instance();

		$this->admin = Admin\Init::get_instance();
		$this->templates = Templates::get_instance();
		Download::get_instance();
	}

	/**
	 * Actions that must run through the `init` hook
	 *
	 * @return void
	 * @author costmo
	 */
	public function maybe_init() {

		if ( ! $this->check_required_plugins() ) {
			return;
		}

	}

	/**
	 * Entry point for initializing the React component
	 *
	 * @return void
	 * @author costmo
	 */
	protected function app_init() {
		add_action( 'wp_enqueue_scripts', [ $this, 'app_enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'init', [ $this, 'rewrite_rules' ], 100 );
	}

	public function rewrite_rules() {
		$flush = '1';

		if ( get_option( '_cp_resources_needs_flush' ) != $flush ) {
			flush_rewrite_rules(true);
			update_option( '_cp_resources_needs_flush', $flush );
		}
	}

	public function admin_scripts() {
		$this->enqueue->enqueue( 'styles', 'admin', [] );
		$this->enqueue->enqueue( 'scripts', 'admin', [] );
	}

	public function is_admin_page() {
		return in_array( get_post_type(), $this->setup->post_types->get_post_types() );
	}

	/**
	 * `wp_enqueue_scripts` actions for the app's compiled sources
	 *
	 * @return void
	 * @author costmo
	 */
	public function app_enqueue() {
		$this->enqueue->enqueue( 'styles', 'main', [] );
		$this->enqueue->enqueue( 'scripts', 'main', [ 'js_dep' => ['jquery'] ] );
		wp_enqueue_style( 'material-icons' );
	}

	/**
	 * Required Plugins notice
	 *
	 * @return void
	 */
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Your system does not meet the requirements for CP Resources', 'cp-resources' ) );
	}


	protected function actions() {}

	protected function includes() {}

	/** Helper Methods **************************************/

	/**
	 * Make sure required plugins are active
	 *
	 * @return bool
	 */
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		// @todo check for requirements before loading
		if ( 1 ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );

		return false;
	}

	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_support_url() {
		return 'https://churchplugins.com/support';
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'CP Resources', 'cp-resources' );
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_path() {
		return CP_RESOURCES_PLUGIN_DIR;
	}

	/**
	 * Provide a unique ID tag for the plugin
	 *
	 * @return string
	 */
	public function get_id() {
		return 'cp-resources';
	}

	/**
	 * Provide a unique ID tag for the plugin
	 *
	 * @return string
	 */
	public function get_version() {
		return CP_RESOURCES_PLUGIN_VERSION;
	}

	/**
	 * Get the API namespace to use
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_api_namespace() {
		return $this->get_id() . '/v1';
	}

}
