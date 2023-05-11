<?php

namespace CP_Resources\Setup\PostTypes;


use CP_Resources\Exception;

/**
 * Setup plugin initialization for CPTs
 */
class Init {

	/**
	 * @var Init
	 */
	protected static $_instance;

	/**
	 * Setup Item CPT
	 *
	 * @var Resource
	 */
	public $resource;

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
	 * Run includes and actions on instantiation
	 *
	 */
	protected function __construct() {
		$this->includes();
		$this->actions();
	}

	/**
	 * Plugin init includes
	 *
	 * @return void
	 */
	protected function includes() {}

	public function in_post_types( $type ) {
		return in_array( $type, $this->get_post_types() );
	}

	public function get_post_types() {
		return [ $this->resource->post_type ];
	}

	/**
	 * @param $type
	 * @param $id
	 *
	 * @return \CP_Resources\Models\Resource | bool
	 * @throws Exception
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function get_type_model( $type, $id ) {
		switch( $type ) {
			case $this->resource->post_type:
				return \CP_Resources\Models\Resource::get_instance_from_origin( $id );
		}
	}

	/**
	 * Plugin init actions
	 *
	 * @return void
	 * @author costmo
	 */
	protected function actions() {
		add_filter( 'use_block_editor_for_post_type', [ $this, 'disable_gutenberg' ], 10, 2 );
		add_action( 'init', [ $this, 'register_post_types' ], 4 );
	}

	public function register_post_types() {

		$this->resource = Resource::get_instance();
		$this->resource->add_actions();

		do_action( 'cp_register_post_types' );
	}

	public function disable_gutenberg( $status, $post_type ) {
		if ( $this->in_post_types( $post_type ) ) {
			return false;
		}

		return $status;
	}

}
