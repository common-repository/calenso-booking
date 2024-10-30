<?php
/*
Plugin Name: Calenso Booking
Plugin URI:  https://calenso.com/integration-wordpress/
Description: Calenso Booking Plugin.
Version: 2.2.9
Author: Calenso AG
Author URI:  https://calenso.com
License: GPLv2 or later
Text Domain: zpt-calenso
*/


define( 'ZPT_CALENSO_SLUG', __DIR__ ); // an absolute path to this directory.

define( 'ZPT_CALENSO_DIR', plugin_dir_url( __FILE__ ) ); // for updates.

define( 'ZPT_CALENSO_VERSION', '2.2.9' );

global $wpdb;

$ZPT_CALENSO_TRANS_TXT = array();

require_once __DIR__ . '/autoload.php';

register_activation_hook( __FILE__, 'zpt_calenso_activation' );

register_deactivation_hook( __FILE__, 'zpt_calenso_deactivation' );

register_uninstall_hook( __FILE__, 'zpt_calenso_uninstall' );


function zpt_calenso_activation( $networkwide ) {
	global $wpdb;
	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ( $networkwide ) {
			$old_blog = $wpdb->blogid;
			// Get all blog ids.
			$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				_zpt_calenso_activation();
			}
			switch_to_blog( $old_blog );
			return;
		}
	}
	_zpt_calenso_activation();
}


function _zpt_calenso_activation() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$api_user = $wpdb->prefix . ZPT_CALENSO_USER_DATA_TABLE;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$api_user'" ) != $api_user ) {
		$api_user_query = "CREATE TABLE $api_user (
        id int(11) NOT NULL AUTO_INCREMENT,
        partner_id int(20) DEFAULT NULL,
        partner_name TEXT NULL DEFAULT NULL,
        booking_name TEXT NULL DEFAULT NULL,
        type TEXT NULL DEFAULT NULL,
        service_id TEXT NULL DEFAULT NULL,
        language_id int(20) DEFAULT NULL,
        time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        )";
		dbDelta( $api_user_query );
	}
	$api_language = $wpdb->prefix . ZPT_CALENSO_LANGUAGE_TABLE;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$api_language'" ) != $api_language ) {
		$api_language_query = "CREATE TABLE $api_language (
        id int(11) NOT NULL AUTO_INCREMENT,
        name TEXT NULL DEFAULT NULL,
        attribute TEXT NULL DEFAULT NULL,
        label TEXT NULL DEFAULT NULL,
        time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        )";
		dbDelta( $api_language_query );
	}
	$api_store = $wpdb->prefix . ZPT_CALENSO_STORE_TABLE;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$api_store'" ) != $api_store ) {
		$api_store_query = "CREATE TABLE $api_store (
        id int(11) NOT NULL AUTO_INCREMENT,
        store_id TEXT NULL DEFAULT NULL,
        name TEXT NULL DEFAULT NULL,
        time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        )";
		dbDelta( $api_store_query );
	}
	$api_shortcode = $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$api_shortcode'" ) != $api_shortcode ) {
		$api_shortcode_query = "CREATE TABLE $api_shortcode (
        id int(11) NOT NULL AUTO_INCREMENT,
        title TEXT NULL DEFAULT NULL,
        shortcode TEXT NULL DEFAULT NULL,
        `function` TEXT NULL DEFAULT NULL,
        time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        )";
		dbDelta( $api_shortcode_query );
	}
	$api_event = $wpdb->prefix . ZPT_CALENSO_EVENT_TABLE;
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$api_event'" ) != $api_event ) {
		$api_event_query = "CREATE TABLE $api_event (
        id int(11) NOT NULL AUTO_INCREMENT,
        event_id TEXT NULL DEFAULT NULL,
        name TEXT NULL DEFAULT NULL,
        time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
        )";
		dbDelta( $api_event_query );
	}
}

function zpt_calenso_deactivation() {
}

function zpt_calenso_uninstall( $networkwide ) {
	global $wpdb;

	error_log( 'zpt_calenso_uninstall' );

	if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		// check if it is a network activation - if so, run the activation function for each blog id.

		// if ($networkwide) {
			$old_blog = $wpdb->blogid;
			// Get all blog ids
			$blogids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
		foreach ( $blogids as $blog_id ) {
			switch_to_blog( $blog_id );
			_zpt_calenso_uninstall();
		}
			switch_to_blog( $old_blog );
			return;
		// }
	}
	_zpt_calenso_uninstall();
}

function _zpt_calenso_uninstall() {
	global $wpdb;
	error_log( '_zpt_calenso_uninstall DB: ' . $wpdb->prefix );
	$tableArray = array(
		$wpdb->prefix . 'zpt_calenso_user_data',
		$wpdb->prefix . 'zpt_calenso_languages',
		$wpdb->prefix . 'zpt_calenso_stores',
		$wpdb->prefix . 'zpt_calenso_shortcode',
		$wpdb->prefix . 'zpt_calenso_event',
	);

	foreach ( $tableArray as $tablename ) {
		$wpdb->query( "DROP TABLE IF EXISTS $tablename" );
	}
}

function zpt_wp_dashboard_setup() {
	wp_add_dashboard_widget( 'zpt_calenso_booking_widget', zpt_calenso__( 'Universal_Title' ), 'zpt_calenso_widget_function' );
}
add_action( 'wp_dashboard_setup', 'zpt_wp_dashboard_setup' );

add_action( 'zpt_calenso_widget_hook', 'zpt_calenso_session_init' );


function zpt_calenso_session_init() {
	$id = session_id();
	if ( ! isset( $id ) ) {
		session_start();
	}
}
