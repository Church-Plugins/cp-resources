<?php

namespace CP_Resources\Setup\Tables;


/**
 * Setup plugin initialization
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;

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
	 * Class constructor
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Admin init includes
	 *
	 * @return void
	 */
	protected function includes() {}

	protected function actions() {
		add_filter( 'cp_registered_tables', [ $this, 'register_tables' ] );
	}

	/** Actions ***************************************************/

	/**
	 * Add our tables to the Church Plugins registration function
	 *
	 * @param $tables
	 *
	 * @return mixed
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function register_tables( $tables ) {
		$tables[] = Resource::get_instance();
		$tables[] = ResourceMeta::get_instance();

		return $tables;
	}

}
