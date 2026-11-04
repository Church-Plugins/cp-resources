<?php
namespace CP_Resources\Setup;

use CP_Resources\Admin\Settings;
use CP_Resources\Models\Resource;

/**
 * Shortcode controller class
 *
 * @author costmo
 */
class Shortcode
{

	/**
	 * Singleton instance
	 *
	 * @var Shortcode
	 */
	protected static $_instance;

	/**
	 * Enforce singleton instantiation
	 *
	 * @return Shortcode
	 */
	public static function get_instance() {
		if( !self::$_instance instanceof Shortcode ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Class constructor
	 *
	 * @author costmo
	 * @return void
	 */
	protected function __construct() {
		$this->add_shortcodes();
	}

	/**
	 * Add the app's custom shortcodes to WP
	 *
	 * @return void
	 * @author costmo
	 */
	public function add_shortcodes() {
		add_shortcode( 'item-resources', [ $this, 'resource_cb' ] );
		add_shortcode( 'cp-resources-archive', [ $this, 'archive_cb' ] );
		add_shortcode( 'cp-resources-filters', [ $this, 'filters_cb' ] );
	}

	/**
	 * Displays a resource
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function resource_cb( $atts ) {
		$args = shortcode_atts(
			array(
				// The ID of the resource to display (defaults to the current post)
				'id' => 0,
			),
			$atts,
			'item-resources'
		);

		if ( empty( $args['id'] ) ) {
			$args['id'] = get_the_ID();
		}

		$resource_objects = Settings::get( 'has_resources', [] );
		if ( ! in_array( get_post_type( $args['id'] ), $resource_objects ) ) {
			return '';
		}

		ob_start();

		cp_resources()->templates->get_template_part( 'widgets/item-resources', $args );

		return ob_get_clean();
	}

	/**
	 * Displays the resource archive page.
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function archive_cb( $atts ) {
		$atts = shortcode_atts(
			array(
				'test' => 'test',
			),
			$atts,
			'cp-resources-archive'
		);

		ob_start();

		cp_resources()->templates->get_template_part( 'archive' );

		return ob_get_clean();
	}

	/**
	 * Displays the resource filters
	 *
	 * @param array $atts Shortcode attributes
	 * @return string
	 */
	public function filters_cb( $atts ) {
		$atts = shortcode_atts(
			array(
				'test' => 'test',
			),
			$atts,
			'cp-resources-filters'
		);

		ob_start();

		cp_resources()->templates->get_template_part( 'parts/filter' );

		return ob_get_clean();
	}
}
