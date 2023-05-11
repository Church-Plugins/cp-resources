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
class Templates {

	/**
	 * @var bool Is wp_head complete?
	 */
	public static $wpHeadComplete = false;

	/**
	 * @var bool Is this the main loop?
	 */
	public static $isMainLoop = false;

	/**
	 * The template name currently being used
	 */
	protected static $template = false;

	/*
	 * List of templates which have compatibility fixes
	 */
	public static $themes_with_compatibility_fixes = [];

	/**
	 * Initialize the Template Yumminess!
	 */
	public static function init() {
		// Choose the wordpress theme template to use
		add_filter( 'template_include', [ __CLASS__, 'template_include' ] );

		// don't query the database for the spoofed post
		wp_cache_set( self::spoofed_post()->ID, self::spoofed_post(), 'posts' );
		wp_cache_set( self::spoofed_post()->ID, [ true ], 'post_meta' );

		add_action( 'wp_head', [ __CLASS__, 'wpHeadFinished' ], 999 );
		add_action( 'cpl_after_archive', 'the_posts_pagination' );
		add_action( 'cpl_before_archive', [ __CLASS__, 'type_switcher' ] );

		// add the theme name to the body class when needed
		if ( self::needs_compatibility_fix() ) {
			add_filter( 'body_class', [ __CLASS__, 'theme_body_class' ] );
		}
	}

	/**
	 * @return \WP_Query|null
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_global_query_object() {
		global $wp_query;
		global $wp_the_query;

		if ( ! empty( $wp_query ) ) {
			return $wp_query;
		}

		if ( ! empty( $wp_the_query ) ) {
			return $wp_the_query;
		}

		return null;
	}

	/**
	 * Check if the main query is for a cpl item
	 *
	 * @return bool|mixed|void
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function is_cpl_query() {
		if ( ! $wp_query = self::get_global_query_object() ) {
			return false;
		}

		$cpl_query = false;
		$types = cp_resources()->setup->post_types->get_post_types();

		if ( $wp_query->is_singular( $types ) || $wp_query->is_post_type_archive( $types ) ) {
			$cpl_query = true;
		}

		$taxonomies = cp_resources()->setup->taxonomies->get_taxonomies();
		if ( $wp_query->is_tax( $taxonomies ) ) {
			$cpl_query = true;
		}

		return apply_filters( 'cpl_template_is_query', $cpl_query );
	}

	public static function get_type( $type = false ) {
		if ( ! $type ) {
			$type = get_post_type();
		}

		return str_replace( [ 'cpl_', '_' ], [ '', '-' ], $type );
	}

	/**
	 * Pick the correct template to include
	 *
	 * @param string $template Path to template
	 *
	 * @return string Path to template
	 */
	public static function template_include( $template ) {
		do_action( 'cpl_template_chooser', $template );

		if ( ! self::is_cpl_query() ) {
			return $template;
		}

		// if it's a single 404
		if ( is_single() && is_404() ) {
			return get_404_template();
		}

		// add the theme slug to the body class
		add_filter( 'body_class', [ __CLASS__, 'theme_body_class' ] );

		// add the template name to the body class
		add_filter( 'body_class', [ __CLASS__, 'template_body_class' ] );

		// user has selected a page/custom page template
		if ( $default_template = apply_filters( 'cpl_default_template', false ) ) {
			if ( ! is_single() || ! post_password_required() ) {
				add_action( 'loop_start', [ __CLASS__, 'setup_cpl_template' ] );
			}

			$template = $default_template !== 'default'
				? locate_template( $default_template )
				: get_page_template();

			if ( $template == '' ) {
				$template = get_index_template();
			}

		} else {
			$template = self::get_template_hierarchy( 'default-template' );
		}

		self::$template = $template;

		return $template;
	}

	/**
	 * Include page template body class
	 *
	 * @param array $classes List of classes to filter
	 *
	 * @return mixed
	 */
	public static function template_body_class( $classes ) {

		$template_filename = basename( self::$template );

		$classes[] = 'cpl-template';

		if ( $template_filename == 'default-template.php' ) {
			$classes[] = 'cpl-page-template';
		} else {
			$classes[] = 'page-template-' . sanitize_title( $template_filename );
		}

		return $classes;
	}

	/**
	 * Add the theme to the body class
	 *
	 * @return array $classes
	 **/
	public static function theme_body_class( $classes ) {
		$child_theme  = get_option( 'stylesheet' );
		$parent_theme = get_option( 'template' );

		// if the 2 options are the same, then there is no child theme
		if ( $child_theme == $parent_theme ) {
			$child_theme = false;
		}

		if ( $child_theme ) {
			$theme_classes = "cpl-theme-parent-$parent_theme cpl-theme-child-$child_theme";
		} else {
			$theme_classes = "cpl-theme-$parent_theme";
		}

		$classes[] = $theme_classes;

		return $classes;
	}


	/**
	 * Checks if theme needs a compatibility fix
	 *
	 * @param string $theme Name of template from WP_Theme->Template, defaults to current active template
	 *
	 * @return mixed
	 */
	public static function needs_compatibility_fix( $theme = null ) {
		// Defaults to current active theme
		if ( $theme === null ) {
			$theme = get_stylesheet();
		}

		$theme_compatibility_list = apply_filters( 'cpl_themes_compatibility_fixes', self::$themes_with_compatibility_fixes );

		return in_array( $theme, $theme_compatibility_list );
	}


	/**
	 * Determine when wp_head has been triggered.
	 */
	public static function wpHeadFinished() {
		self::$wpHeadComplete = true;
	}

	/**
	 * Add type switcher to archive header
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function type_switcher() {

		if( is_tax() ) {
			return;
		}

		$type   = self::get_type();
		$link   = '';
		$button = '';
		$item_cpt = get_post_type_object(cp_resources()->setup->post_types->item->post_type);
		$item_type_cpt = get_post_type_object(cp_resources()->setup->post_types->item_type->post_type);

		$split = explode( '?', $_SERVER['REQUEST_URI'] );
		$req_uri = $split[0] ?? '';
		$query_params = $split[1] ?? '';

		// Extract all path components up to the page delimiter
		$uri_split = explode( "/", $req_uri );
		$exclusions = [ $item_type_cpt->rewrite['slug'], $item_cpt->rewrite['slug'] ];
		$have_target = false;
		foreach( $uri_split as $token ) {
			if( 'page' === $token ) { $have_target = true; }
			if( $have_target ) { continue; }
			if( strlen( trim( $token ) ) > 0 && !in_array( $token, $exclusions ) ) {
				$link .= trailingslashit( $token );
			}
		}

		switch( $type ) {
			case 'item':
				$link .= $item_type_cpt->rewrite['slug'];
				$button = sprintf( __( 'Switch to %s' ), $item_type_cpt->label );
				break;
			case 'item-type':
				$link .= $item_cpt->rewrite['slug'];
				$button = sprintf( __( 'Switch to %s' ), $item_cpt->label );
				break;
		}

		if ( empty( $link ) ) {
			return;
		}

		// normalize the output
		if( 1 !== preg_match( "/^\//", $link ) ) {
			$link = '/' . $link;
		}
		$link = trailingslashit( $link );

		printf( '<a class="cpl-archive--item-switcher" href="%s">%s</a>', $link, $button );
	}


	/**
	 * This is where the magic happens where we run some ninja code that hooks the query to resolve to an library template.
	 *
	 * @param \WP_Query $query
	 */
	public static function setup_cpl_template( $query ) {

		if ( $query->is_main_query() && self::$wpHeadComplete ) {
			// on loop start, unset the global post so that template tags don't work before the_content()
			add_action( 'the_post', [ __CLASS__, 'spoof_the_post' ] );

			// on the_content, load our template
			add_filter( 'the_content', [ __CLASS__, 'load_cpl_into_page_template' ] );

			// only do this once
			remove_action( 'loop_start', [ __CLASS__, 'setup_cpl_template' ] );
		}
	}

	/**
	 * Spoof the global post just once
	 *
	 **/
	public static function spoof_the_post() {
		$GLOBALS['post'] = self::spoofed_post();
		remove_action( 'the_post', [ __CLASS__, 'spoof_the_post' ] );
	}

	/**
	 * Return the correct view template
	 *
	 * @param bool $view
	 *
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public static function get_view( $view = false ) {
		do_action( 'cpl_pre_get_view' );

		if ( $view ) {
			$template_file = self::get_template_hierarchy( $view, [ 'disable_view_check' => true ] );
		} else {
			$template_file = self::get_current_page_template();
		}

		if ( file_exists( $template_file ) ) {
			do_action( 'cpl_before_view', $template_file );
			include( $template_file );
			do_action( 'cpl_after_view', $template_file );
		}

	}

	/**
	 * Get the correct internal page template
	 *
	 * @return string Template path
	 */
	public static function get_current_page_template() {

		$template = '';


		if ( apply_filters( 'cpl_template_use_react', false ) ) {
			$template = self::get_template_hierarchy( 'app', [ 'disable_view_check' => true ] );
		} else {

			$wp_query = self::get_global_query_object();

			$types     = cp_resources()->setup->post_types->get_post_types();
			$taxonomies = cp_resources()->setup->taxonomies->get_taxonomies();

			if ( $wp_query->is_tax( $taxonomies ) ) {
				$template = self::get_template_hierarchy( 'archive-tax', [ 'disable_view_check' => true ] );
			}

			if ( $wp_query->is_post_type_archive( $types ) ) {
				$template = self::get_template_hierarchy( 'archive', [ 'disable_view_check' => true ] );
			}

			if ( $wp_query->is_singular( $types ) ) {
				$template = self::get_template_hierarchy( 'single', [ 'disable_view_check' => true ] );
			}

		}

		// apply filters
		return apply_filters( 'cpl_current_view_template', $template );

	}

	/**
	 * Loads the contents into the page template
	 *
	 * @return string Page content
	 */
	public static function load_cpl_into_page_template( $contents = '' ) {
		// only run once!!!
		remove_filter( 'the_content', [ __CLASS__, 'load_cpl_into_page_template' ] );

		ob_start();

		echo apply_filters( 'cpl_default_template_before_content', '' );
		self::get_view();
		echo apply_filters( 'cpl_default_template_after_content', '' );

		$contents = ob_get_clean();

		// make sure the loop ends after our template is included
		if ( ! is_404() ) {
			self::endQuery();
		}

		return $contents;
	}

	public static function get_template_part( $template, $args = [] ) {
		$template = self::get_template_hierarchy( $template );
		include( $template );
	}

	/**
	 * Loads theme files in appropriate hierarchy: 1) child theme,
	 * 2) parent template, 3) plugin resources. will look in the cp-resources/
	 * directory in a theme and the templates/ directory in the plugin
	 *
	 * @param string $template template file to search for
	 * @param array  $args     additional arguments to affect the template path
	 *                         - namespace
	 *                         - plugin_path
	 *                         - disable_view_check - bypass the check to see if the view is enabled
	 *
	 * @return template path
	 **/
	public static function get_template_hierarchy( $template, $args = [] ) {
		if ( ! is_array( $args ) ) {
			$passed        = func_get_args();
			$args          = [];
			$backwards_map = [ 'namespace', 'plugin_path' ];
			$count         = count( $passed );

			if ( $count > 1 ) {
				for ( $i = 1; $i < $count; $i ++ ) {
					$args[ $backwards_map[ $i - 1 ] ] = $passed[ $i ];
				}
			}
		}

		$args = wp_parse_args(
			$args, [
				'namespace'          => '/',
				'plugin_path'        => '',
				'disable_view_check' => false,
			]
		);

		/**
		 * @var string $namespace
		 * @var string $plugin_path
		 * @var bool   $disable_view_check
		 */
		extract( $args );

		// append .php to file name
		if ( substr( $template, - 4 ) != '.php' && false === strpos( $template, '.json' ) ) {
			$template .= '.php';
		}

		// Allow base path for templates to be filtered
		$template_base_paths = apply_filters( 'cpl_template_paths', ( array ) cp_resources()->get_plugin_path() );

		// backwards compatibility if $plugin_path arg is used
		if ( $plugin_path && ! in_array( $plugin_path, $template_base_paths ) ) {
			array_unshift( $template_base_paths, $plugin_path );
		}

		$file = false;

		/* potential scenarios:

		- the user has no template overrides
			-> we can just look in our plugin dirs, for the specific path requested, don't need to worry about the namespace
		- the user created template overrides without the namespace, which reference non-overrides without the namespace and, their own other overrides without the namespace
			-> we need to look in their theme for the specific path requested
			-> if not found, we need to look in our plugin views for the file by adding the namespace
		- the user has template overrides using the namespace
			-> we should look in the theme dir, then the plugin dir for the specific path requested, don't need to worry about the namespace

		*/

		// check if there are overrides at all
		if ( locate_template( [ 'cp-resources/' ] ) ) {
			$overrides_exist = true;
		} else {
			$overrides_exist = false;
		}

		if ( $overrides_exist ) {
			// check the theme for specific file requested
			$file = locate_template( [ 'cp-resources/' . $template ], false, false );
			if ( ! $file ) {
				// if not found, it could be our plugin requesting the file with the namespace,
				// so check the theme for the path without the namespace
				$files = [];
				foreach ( array_keys( $template_base_paths ) as $namespace ) {
					if ( ! empty( $namespace ) && ! is_numeric( $namespace ) ) {
						$files[] = 'cp-resources' . str_replace( $namespace, '', $template );
					}
				}
				$file = locate_template( $files, false, false );
				if ( $file ) {
					_deprecated_function( sprintf( esc_html__( 'Template overrides should be moved to the correct subdirectory: %s', 'cp-resources' ), str_replace( get_stylesheet_directory() . '/cp-resources/', '', $file ) ), '3.2', $template );
				}
			} else {
				$file = apply_filters( 'cpl_template', $file, $template );
			}
		}

		// if the theme file wasn't found, check our plugins views dirs
		if ( ! $file ) {

			foreach ( $template_base_paths as $template_base_path ) {

				// make sure directories are trailingslashed
				$template_base_path = ! empty( $template_base_path ) ? trailingslashit( $template_base_path ) : $template_base_path;

				$file = $template_base_path . 'templates/' . $template;

				$file = apply_filters( 'cpl_template', $file, $template );

				// return the first one found
				if ( file_exists( $file ) ) {
					break;
				} else {
					$file = false;
				}
			}
		}

		return apply_filters( 'cpl_template_' . $template, $file );
	}

	/**
	 * Query is complete: stop the loop from repeating.
	 */
	private static function endQuery() {

		$wp_query = self::get_global_query_object();

		$wp_query->current_post = - 1;
		$wp_query->post_count   = 0;
	}


	/**
	 * Spoof the query so that we can operate independently of what has been queried.
	 *
	 * @return object
	 */
	private static function spoofed_post() {
		return (object) [
			'ID'                    => 0,
			'post_status'           => 'draft',
			'post_author'           => 0,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => '',
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'is_404'                => false,
			'is_page'               => false,
			'is_single'             => false,
			'is_archive'            => false,
			'is_tax'                => false,
		];
	}

}
