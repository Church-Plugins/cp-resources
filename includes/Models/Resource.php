<?php

namespace CP_Resources\Models;

use ChurchPlugins\Exception;
use ChurchPlugins\Models\Table;

/**
 * Item DB Class
 *
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Item Class
 *
 * @since 1.0.0
 */
class Resource extends Table  {

	/**
	 * Item speakers
	 *
	 * @var bool
	 */
	protected $speakers = false;

	/**
	 * Item service type
	 *
	 * @var bool
	 */
	protected $service_types = false;

	/**
	 * Item types
	 *
	 * @var bool
	 */
	protected $types = false;

	public function init() {
		$this->type = 'resource';
		$this->post_type = 'cp_resource';

		parent::init();

		$this->table_name  = $this->prefix . 'cp_' . $this->type;
		$this->meta_table_name  = $this->prefix . 'cp_' . $this->type . "_meta";
	}

	/**
	 * Get columns and formats
	 *
	 * @since   1.0
	*/
	public static function get_columns() {
		return array(
			'id'        => '%d',
			'origin_id' => '%d',
			'title'     => '%s',
			'status'    => '%s',
			'published' => '%s',
			'updated'   => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since   1.0
	*/
	public static function get_column_defaults() {
		return array(
			'origin_id' => 0,
			'title'     => '',
			'status'    => '',
			'published' => date( 'Y-m-d H:i:s' ),
			'updated'   => date( 'Y-m-d H:i:s' ),
		);
	}


	/**
	 * Get meta columns and formats
	 *
	 * @since   1.0
	*/
	public static function get_meta_columns() {
		return array(
			'id'           => '%d',
			'key'          => '%s',
			'value'        => '%s',
			'resource_id'  => '%d',
			'secondary_id' => '%d',
			'order'        => '%d',
			'published'    => '%s',
			'updated'      => '%s',
		);
	}

	/**
	 * Get default meta column values
	 *
	 * @since   1.0
	*/
	public function get_meta_column_defaults() {
		return array(
			'key'          => '',
			'value'        => '',
			'resource_id'  => $this->id,
			'secondary_id' => 0,
			'order'        => 0,
			'published'    => date( 'Y-m-d H:i:s' ),
			'updated'      => date( 'Y-m-d H:i:s' ),
		);
	}

}
