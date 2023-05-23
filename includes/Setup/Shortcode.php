<?php
namespace CP_Resources\Setup;

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
	 * @param array $params
	 * @return void
	 * @author costmo
	 */
	public function add_shortcodes() {
		add_shortcode( 'item-resources', [ $this, 'resource_cb' ] );
	}

	public function resource_cb( $atts ) {
		$args = shortcode_atts( [
			'id' => 0,
		], $atts, 'cp-resources' );


		if ( empty( $args['id'] ) ) {
			$args['id'] = get_the_ID();
		}

		$resources = Resource::get_instance_from_origin( $args['id'] );

		if ( empty( $resources ) ) {
			return '';
		}

		ob_start();

		cp_resources()->templates->get_template_part( 'widgets/item-resources', $args );

		return ob_get_clean();
	}

}
