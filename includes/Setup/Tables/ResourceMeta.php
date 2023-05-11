<?php

namespace CP_Resources\Setup\Tables;

use ChurchPlugins\Setup\Tables\Table;

/**
 * ItemMeta DB Class
 *
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ItemMeta Class
 *
 * @since 1.0.0
 */
class ResourceMeta extends Table  {

	/**
	 * Get things started
	 *
	 * @since  1.0.0
	*/
	public function __construct() {
		parent::__construct();

		$this->table_name = $this->prefix . 'cp_resource_meta';
		$this->version    = 1;


		$enum = get_option( 'cp_resources_item_meta_enum' );
		if ( is_admin() && self::get_keys() != $enum ) {
			global $wpdb;
			$keys = "'" . implode( "', '", self::get_keys() ) . "'";
			$sql  = "ALTER TABLE $this->table_name CHANGE `key` `key` ENUM( $keys ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;";
			$wpdb->query($sql);
			update_option( 'cp_resources_item_meta_enum', self::get_keys() );
		}
	}

	/**
	 * Keys for key column
	 * @author Tanner Moushey
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 *
	 */
	public static function get_keys() {
		return apply_filters( 'cpl_item_meta_keys_enum', [ '', 'resource_url', 'relationship' ] );
	}

	/**
	 * SQL to update ENUM values for meta keys
	 *
	 * @return string
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function update_enum_sql() {
		$keys = "'" . implode( "', '", self::get_keys() ) . "'";
		return "ALTER TABLE " . $this->table_name . " MODIFY COLUMN key ENUM( $keys );";
	}

	/**
	 * Create the table
	 *
	 * @since   1.0.0
	*/
	public function get_sql() {

		$keys = "'" . implode( "', '", self::get_keys() ) . "'";

		return "CREATE TABLE " . $this->table_name . " (
			`id` bigint NOT NULL AUTO_INCREMENT,
			`key` ENUM( $keys ),
			`value` longtext,
			`resource_id` bigint,
			`secondary_id` bigint,
			`order` bigint,
			`published` datetime NOT NULL,
			`updated` datetime NOT NULL,
			PRIMARY KEY  (`id`),
			KEY `idx_key` (`key`),
			KEY `idx_resource_id` (`resource_id`),
			KEY `idx_secondary_id` (`secondary_id`)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";


	}

	/**
	 *
	 * @return null
	 * @since  1.0.0
	 *
	 * @author Tanner Moushey
	 */
	public function maybe_update() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = $this->update_enum_sql();

		dbDelta( $sql );

		$this->updated_table();
	}

}
