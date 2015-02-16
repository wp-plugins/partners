<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

require_once __DIR__ . '/inc/autoload.php';

$partners = new \MightyDev\WordPress\Partners;

global $wpdb;

$wpdb->query( "DELETE FROM $wpdb->options WHERE `option_name` LIKE 'mdpartners%'" );

$wpdb->query( 'DROP TABLE IF EXISTS ' . $partners->members_tb );
