<?php

namespace CP_Resources\Admin;

/**
 * Plugin settings
 *
 */
class Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \CP_Resources\Settings
	 *
	 * @return Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get a value from the options table
	 *
	 * @param $key
	 * @param $default
	 * @param $group
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get( $key, $default = '', $group = 'cp_resources_main_options' ) {
		$options = get_option( $group, [] );

		if ( isset( $options[ $key ] ) ) {
			$value = $options[ $key ];
		} else {
			$value = $default;
		}

		return apply_filters( 'cp_resources_settings_get', $value, $key, $group );
	}

	/**
	 * Get advanced options
	 *
	 * @param $key
	 * @param $default
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_advanced( $key, $default = '' ) {
		return self::get( $key, $default, 'cp_resources_advanced_options' );
	}

	public static function get_resource( $key, $default = '' ) {
		return self::get( $key, $default, 'cp_resources_resource_options' );
	}

	/**
	 * Class constructor. Add admin hooks and actions
	 *
	 */
	protected function __construct() {
		add_action( 'cmb2_admin_init', [ $this, 'register_main_options_metabox' ] );
//		add_action( 'cmb2_save_options_page_fields', 'flush_rewrite_rules' );
	}

	public function register_main_options_metabox() {

		$post_type = cp_resources()->setup->post_types->resource->post_type;

		/**
		 * Registers main options page menu item and form.
		 */
		$args = array(
			'id'           => 'cp_resources_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_resources_main_options',
			'tab_group'    => 'cp_resources_main_options',
			'tab_title'    => 'Main',
			'parent_slug'  => 'edit.php?post_type=' . $post_type,
			'display_cb'   => [ $this, 'options_display_with_tabs'],
		);

		$main_options = new_cmb2_box( $args );


		$objects = get_post_types( apply_filters( 'cp_resource_objects_args', [ 'public' => true ] ), 'objects' );
		$objects = wp_list_pluck( $objects, 'label', 'name' );

		// don't include Media or Resources (Resources are always enabled)
		unset( $objects['attachment'] );
		unset( $objects[ $post_type ] );

		$main_options->add_field( array(
			'name'    => sprintf( __( '%s Objects', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'desc'    => sprintf( __( 'Specify the objects that can be tagged as a %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'id'      => 'resource_objects',
			'type'    => 'pw_multiselect',
			'options' => $objects,
		) );

		$main_options->add_field( array(
			'name'    => sprintf( __( 'Objects with %s', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
			'desc'    => sprintf( __( 'Specify the objects that can be assigned a %s.', 'cp-resources' ), cp_resources()->setup->post_types->resource->single_label ),
			'id'      => 'has_resources',
			'type'    => 'pw_multiselect',
			'options' => $objects,
		) );

		// handle display settings for each Object with Resources
		$has_resources = self::get( 'has_resources', [] );
		$display_options = apply_filters( 'cp_resources_settings_display_options', [
			'before_content' => __( 'Before Content', 'cp-resources' ),
			'after_content'  => __( 'After Content', 'cp-resources' ),
			'none'           => __( 'None', 'cp-resources' ),
		] );

		if ( ! empty( $has_resources ) ) {
			$main_options->add_field( array(
				'name'    => __( 'Display Settings', 'cp-resources' ),
				'desc'    => sprintf( __( 'Specify where %s should display for each object.', 'cp-resources' ), cp_resources()->setup->post_types->resource->plural_label ),
				'id'      => 'resource_display_title',
				'type'    => 'title',
			) );
		}

		foreach( $has_resources as $post_type ) {
			$type = get_post_type_object( $post_type );
			$main_options->add_field( array(
				'name'    => $type->label,
				'id'      => 'resource_display_' . $post_type,
				'type'    => 'radio_inline',
				'options' => $display_options,
				'default' => 'before_content',
			) );
		}

//		$this->item_options();
//		$this->advanced_options();

		$this->license_fields();

	}

	protected function license_fields() {
		$license = new \ChurchPlugins\Setup\Admin\License( 'cp_resources_license', 0, CP_RESOURCES_STORE_URL, CP_RESOURCES_PLUGIN_FILE, get_admin_url( null, 'admin.php?page=cp_resources_license' ) );

		/**
		 * Registers settings page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cp_resources_license_page',
			'title'        => 'CP Resources Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_resources_license',
			'parent_slug'  => 'cp_resources_main_options',
			'tab_group'    => 'cp_resources_main_options',
			'tab_title'    => 'License',
			'display_cb'   => [ $this, 'options_display_with_tabs' ]
		);

		$options = new_cmb2_box( $args );
		$license->license_field( $options );
	}


	protected function item_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cp_resources_item_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_resources_item_options',
			'parent_slug'  => 'cp_resources_main_options',
			'tab_group'    => 'cp_resources_main_options',
			'tab_title'    => cp_resources()->setup->post_types->item->plural_label,
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$options = new_cmb2_box( $args );

		$options->add_field( array(
			'name' => __( 'Labels' ),
			'id'   => 'labels',
			'type' => 'title',
		) );

		$options->add_field( array(
			'name'    => __( 'Singular Label', 'cp-resources' ),
			'id'      => 'singular_label',
			'type'    => 'text',
			'default' => cp_resources()->setup->post_types->item->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-resources' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => cp_resources()->setup->post_types->item->plural_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Slug', 'cp-resources' ),
			'id'      => 'slug',
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-resources' ),
			'type'    => 'text',
			'default' => strtolower( sanitize_title( cp_resources()->setup->post_types->item_type->plural_label ) ),
		) );

	}

	protected function advanced_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cp_resources_advanced_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cp_resources_advanced_options',
			'parent_slug'  => 'cp_resources_main_options',
			'tab_group'    => 'cp_resources_main_options',
			'tab_title'    => 'Advanced',
			'display_cb'   => [ $this, 'options_display_with_tabs' ],
		);

		$advanced_options = new_cmb2_box( $args );

		$advanced_options->add_field( array(
			'name' => __( 'Modules' ),
			'id'   => 'modules_enabled',
			'type' => 'title',
		) );

		$advanced_options->add_field( array(
			'name'    => __( 'Enable' ) . ' ' . cp_resources()->setup->post_types->item_type->plural_label,
			'id'      => 'item_type_enabled',
			'type'    => 'radio_inline',
			'default' => 1,
			'options' => [
				1 => __( 'Enable', 'cp-resources' ),
				0 => __( 'Disable', 'cp-resources' ),
			]
		) );

		$advanced_options->add_field( array(
			'name'    => __( 'Enable' ) . ' ' . cp_resources()->setup->post_types->speaker->plural_label,
			'id'      => 'speaker_enabled',
			'type'    => 'radio_inline',
			'default' => 1,
			'options' => [
				1 => __( 'Enable', 'cp-resources' ),
				0 => __( 'Disable', 'cp-resources' ),
			]
		) );

		$advanced_options->add_field( array(
			'name'    => __( 'Enable' ) . ' ' . cp_resources()->setup->post_types->service_type->plural_label,
			'id'      => 'service_type_enabled',
			'type'    => 'radio_inline',
			'default' => 0,
			'options' => [
				1 => __( 'Enable', 'cp-resources' ),
				0 => __( 'Disable', 'cp-resources' ),
			]
		) );

		$advanced_options->add_field( array(
			'name'    => __( 'Enable Podcast Feed' ),
			'id'      => 'podcast_feed_enable',
			'type'    => 'radio_inline',
			'default' => 0,
			'options' => [
				1 => __( 'Enable', 'cp-resources' ),
				0 => __( 'Disable', 'cp-resources' ),
			]
		) );

	}

	/**
	 * A CMB2 options-page display callback override which adds tab navigation among
	 * CMB2 options pages which share this same display callback.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 */
	public function options_display_with_tabs( $cmb_options ) {
		$tabs = $this->options_page_tabs( $cmb_options );
		?>
		<div class="wrap cmb2-options-page option-<?php echo $cmb_options->option_key; ?>">
			<?php if ( get_admin_page_title() ) : ?>
				<h2><?php echo wp_kses_post( get_admin_page_title() ); ?></h2>
			<?php endif; ?>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $option_key => $tab_title ) : ?>
					<a class="nav-tab<?php if ( isset( $_GET['page'] ) && $option_key === $_GET['page'] ) : ?> nav-tab-active<?php endif; ?>"
					   href="<?php menu_page_url( $option_key ); ?>"><?php echo wp_kses_post( $tab_title ); ?></a>
				<?php endforeach; ?>
			</h2>
			<form class="cmb-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="POST"
				  id="<?php echo $cmb_options->cmb->cmb_id; ?>" enctype="multipart/form-data"
				  encoding="multipart/form-data">
				<input type="hidden" name="action" value="<?php echo esc_attr( $cmb_options->option_key ); ?>">
				<?php $cmb_options->options_page_metabox(); ?>
				<?php submit_button( esc_attr( $cmb_options->cmb->prop( 'save_button' ) ), 'primary', 'submit-cmb' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Gets navigation tabs array for CMB2 options pages which share the given
	 * display_cb param.
	 *
	 * @param \CMB2_Options_Hookup $cmb_options The CMB2_Options_Hookup object.
	 *
	 * @return array Array of tab information.
	 */
	public function options_page_tabs( $cmb_options ) {
		$tab_group = $cmb_options->cmb->prop( 'tab_group' );
		$tabs      = array();

		foreach ( \CMB2_Boxes::get_all() as $cmb_id => $cmb ) {
			if ( $tab_group === $cmb->prop( 'tab_group' ) ) {
				$tabs[ $cmb->options_page_keys()[0] ] = $cmb->prop( 'tab_title' )
					? $cmb->prop( 'tab_title' )
					: $cmb->prop( 'title' );
			}
		}

		return $tabs;
	}


}
