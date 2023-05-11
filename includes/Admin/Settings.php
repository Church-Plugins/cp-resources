<?php

namespace CP_Resources\Admin;

use CP_Resources\Models\ServiceType;

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
	public static function get( $key, $default = '', $group = 'cpl_main_options' ) {
		$options = get_option( $group, [] );

		if ( isset( $options[ $key ] ) ) {
			$value = $options[ $key ];
		} else {
			$value = $default;
		}

		return apply_filters( 'cpl_settings_get', $value, $key, $group );
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
//		add_action( 'cmb2_admin_init', [ $this, 'register_main_options_metabox' ] );
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
			'option_key'   => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
			'tab_title'    => 'Main',
			'parent_slug'  => 'edit.php?post_type=' . $post_type,
			'display_cb'   => [ $this, 'options_display_with_tabs'],
		);

		$main_options = new_cmb2_box( $args );

		/**
		 * Options fields ids only need
		 * to be unique within this box.
		 * Prefix is not needed.
		 */
		$main_options->add_field( array(
			'name'    => __( 'Primary Color', 'cp-resources' ),
			'desc'    => __( 'The primary color to use in the templates.', 'cp-resources' ),
			'id'      => 'color_primary',
			'type'    => 'colorpicker',
			'default' => '#333333',
		) );

		$main_options->add_field( array(
			'name'         => __( 'Site Logo', 'cp-resources' ),
			'desc'         => sprintf( __( 'The logo to use for %s.', 'cp-resources' ), cp_resources()->setup->post_types->item->plural_label ),
			'id'           => 'logo',
			'type'         => 'file',
			// query_args are passed to wp.media's library query.
			'query_args'   => array(
				// Or only allow gif, jpg, or png images
				 'type' => array(
				     'image/gif',
				     'image/jpeg',
				     'image/png',
				 ),
			),
			'preview_size' => 'thumbnail', // Image size to use when previewing in the admin
		) );

		$main_options->add_field( array(
			'name'         => __( 'Default Thumbnail', 'cp-resources' ),
			'desc'         => sprintf( __( 'The default thumbnail image to use for %s.', 'cp-resources' ), cp_resources()->setup->post_types->item->plural_label ),
			'id'           => 'default_thumbnail',
			'type'         => 'file',
			// query_args are passed to wp.media's library query.
			'query_args'   => array(
				// Or only allow gif, jpg, or png images
				 'type' => array(
				     'image/gif',
				     'image/jpeg',
				     'image/png',
				 ),
			),
			'preview_size' => 'medium', // Image size to use when previewing in the admin
		) );

		$this->item_options();

		$this->advanced_options();
		$this->license_fields();

	}

	protected function license_fields() {
		$license = new \ChurchPlugins\Setup\Admin\License( 'cpl_license', 436, CP_RESOURCES_STORE_URL, CP_RESOURCES_PLUGIN_FILE, get_admin_url( null, 'admin.php?page=cpl_license' ) );

		/**
		 * Registers settings page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_options_page',
			'title'        => 'CP Resources Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_license',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
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
			'id'           => 'cpl_item_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_item_options',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
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

	protected function item_type_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_item_type_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_item_type_options',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
			'tab_title'    => cp_resources()->setup->post_types->item_type->plural_label,
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
			'default' => cp_resources()->setup->post_types->item_type->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-resources' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => cp_resources()->setup->post_types->item_type->plural_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Slug', 'cp-resources' ),
			'id'      => 'slug',
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-resources' ),
			'type'    => 'text',
			'default' => strtolower( sanitize_title( cp_resources()->setup->post_types->item_type->plural_label ) ),
		) );

	}

	protected function speaker_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_speaker_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_speaker_options',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
			'tab_title'    => cp_resources()->setup->post_types->speaker->plural_label,
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
			'default' => cp_resources()->setup->post_types->speaker->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-resources' ),
			'desc'    => __( 'Caution: changing this value will also adjust the url structure and may affect your SEO.', 'cp-resources' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => cp_resources()->setup->post_types->speaker->plural_label,
		) );

	}

	protected function service_type_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_service_type_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_service_type_options',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
			'tab_title'    => cp_resources()->setup->post_types->service_type->plural_label,
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
			'default' => cp_resources()->setup->post_types->service_type->single_label,
		) );

		$options->add_field( array(
			'name'    => __( 'Plural Label', 'cp-resources' ),
			'id'      => 'plural_label',
			'type'    => 'text',
			'default' => cp_resources()->setup->post_types->service_type->plural_label,
		) );

		$service_types = ServiceType::get_all_service_types();

		if ( empty( $service_types ) ) {
			$options->add_field( [
				'desc' => sprintf( __( 'No %s have been created yet. <a href="%s">Create one here.</a>', 'cp-resources' ), cp_resources()->setup->post_types->service_type->plural_label, add_query_arg( [ 'post_type' => cp_resources()->setup->post_types->service_type->post_type ], admin_url( 'post-new.php' ) )  ),
				'type' => 'title',
				'id' => 'cpl_no_service_types',
			] );
		} else {
			$service_types = array_combine( wp_list_pluck( $service_types, 'id' ), wp_list_pluck( $service_types, 'title' ) );

			$options->add_field( array(
				'name'             => __( 'Default Service Type', 'cp-resources' ),
				'id'               => 'default_service_type',
				'type'             => 'select',
				'show_option_none' => true,
				'options'          => $service_types,
			) );
		}

	}

	protected function advanced_options() {
		/**
		 * Registers secondary options page, and set main item as parent.
		 */
		$args = array(
			'id'           => 'cpl_advanced_options_page',
			'title'        => 'Settings',
			'object_types' => array( 'options-page' ),
			'option_key'   => 'cpl_advanced_options',
			'parent_slug'  => 'cpl_main_options',
			'tab_group'    => 'cpl_main_options',
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
