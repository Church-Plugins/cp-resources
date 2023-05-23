<?php

namespace CP_Resources\Setup\Tables;

use ChurchPlugins\Setup\Tables\Table;

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
 * @since 1.0
 */
class Resource extends Table  {
	/**
	 * Get things started
	 *
	 * @since  1.0.0
	*/
	public function __construct() {
		parent::__construct();

		$this->table_name = $this->prefix . 'cp_resource';
		$this->version    = 1;
	}

	/**
	 * Create the table
	 *
	 * @since   1.0.0
	*/
	public function get_sql() {

		return "CREATE TABLE " . $this->table_name . " (
			`id` bigint NOT NULL AUTO_INCREMENT,
			`origin_id` bigint,
			`title` varchar(255),
			`status` ENUM( 'draft', 'publish', 'scheduled' ),
			`hide_archive` tinyint(1) DEFAULT 0,
			`published` datetime NOT NULL,
			`updated` datetime NOT NULL,
			PRIMARY KEY  (`id`),
			KEY `idx_origin_id` (`origin_id`),
			KEY `idx_status` (`status`),
			KEY `idx_hide_archive` (`hide_archive`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	}

	public function maybe_update() {
		global $wpdb;

		$sql = "ALTER TABLE " . $this->table_name . " ADD COLUMN title varchar(255) AFTER origin_id;";

		$wpdb->query( $sql );
		$this->updated_table();
	}

}

