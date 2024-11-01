<?php
/**
 * Responsible for managing plugin admin area.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM;

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for registering admin menus.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Admin {

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'admin_menus' ] );
		add_action( 'admin_init', [ $this, 'check_database_schema' ] );
		add_action( 'admin_init', [ $this, 'create_table_if_not_exist' ] );
	}

	/**
	 * Registers admin menus.
	 *
	 * @since 2.12.15
	 */
	public function admin_menus() {
		add_menu_page(
			__( 'FormFlow', 'simpleform' ),
			__( 'FormFlow', 'simpleform' ),
			'manage_options',
			'simpleform-dashboard',
			[ $this, 'dashboardPage' ],
			SIMPLEFORM_BASE_URL . 'assets/public/icons/logo.svg'
			// 'dashicons-welcome-widgets-menus'
		);

		if ( current_user_can( 'manage_options' ) ) {
			global $submenu;

			$submenu['simpleform-dashboard'][] = [ __( 'Dashboard', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/' ]; // phpcs:ignore

			$submenu['simpleform-dashboard'][] = [ __( 'Preset', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/preset-form' ]; // phpcs:ignore

			// $submenu['simpleform-dashboard'][] = [ __( 'Create Form', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/create-form' ]; // phpcs:ignore

			$submenu['simpleform-dashboard'][] = [ __( 'Leads', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/Leads' ]; // phpcs:ignore

			$submenu['simpleform-dashboard'][] = [ __( 'Settings', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/settings' ]; // phpcs:ignore

			$submenu['simpleform-dashboard'][] = [ __( 'Get started', 'simpleform-dashboard' ), 'manage_options', 'admin.php?page=simpleform-dashboard#/doc' ]; // phpcs:ignore
		}
	}

	/**
	 * Displays admin page.
	 *
	 * @return void
	 */
	public static function dashboardPage() {
		echo '<div id="simpleform-app-root"></div>';
		echo '<div id="simpleform-app-portal"></div>';
	}


	/**
	 * Check and update the database schema if necessary.
	 *
	 * @since 2.12.15
	 */
	public function check_database_schema() {
		global $wpdb;

		$table   = $wpdb->prefix . 'simple_form_leads';
		$charset = $wpdb->get_charset_collate();

		// Check if the 'meta' column exists in the table.
		$column_exists = $wpdb->get_var( "SHOW COLUMNS FROM $table LIKE 'meta'" );//phpcs:ignore

		// Check if the schema update has already been performed.
		$schema_updated = get_option( 'simple_form_schema_updated' );

		if ( null === $column_exists && ! $schema_updated ) {
			// 'meta' column does not exist, let's add it.
			$sql = "ALTER TABLE $table ADD COLUMN `meta` text NULL DEFAULT NULL AFTER `fields`"; //phpcs:ignore
			$wpdb->query( $sql ); //phpcs:ignore

			// Set the flag indicating that the schema has been updated.
			update_option( 'simple_form_schema_updated', true );
		}
	}

	/**
	 * Check and create the table schema if not exist.
	 *
	 * @since 2.12.15
	 */
	public function create_table_if_not_exist() {
		global $wpdb;

		// Check if the table has already been created.
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}simple_form_cf7_leads'") === $wpdb->prefix . 'simple_form_cf7_leads';

		// Check if the flag indicating table creation status is set.
		$table_creation_flag = get_option('simple_form_cf7_leads_table_created', false);

		// If table does not exist and the flag is not set, create the table.
		if ( ! $table_exists && ! $table_creation_flag ) {
			// Create the table.
			global $wpdb;
			$table_name = $wpdb->prefix . 'formflow_cf7_config_data';

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id INT AUTO_INCREMENT PRIMARY KEY,
				selectedCF7toconfigureID INT NOT NULL,
				disableCF7Mail VARCHAR(20) NOT NULL,
				cf7formData TEXT NOT NULL,
				UNIQUE (selectedCF7toconfigureID)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Set the flag indicating table creation.
			update_option('simple_form_cf7_leads_table_created', true);
		}
	}
}
