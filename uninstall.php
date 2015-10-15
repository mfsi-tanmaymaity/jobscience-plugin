<?php
/**
 * Name			: uninstall.php
 * Description	: This file will execute when the user clicks on the uninstall link that calls for the plugin to uninstall itself.
 * Author		: JobScience
 * Date			: 05/18/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// If uninstall not called from WordPress exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;

$sql = "DELETE FROM $wpdb->posts WHERE post_type='jobscience_job'";
$wpdb->query( $sql );

$sql = "DELETE FROM $wpdb->postmeta WHERE post_id NOT IN (SELECT id FROM $wpdb->posts)";
$wpdb->query( $sql );

// Remove all option data from option table.
delete_option( 'js-outbound-token' );
delete_option( 'js-rss-tag' );
delete_option( 'js-rss-feed-url' );
delete_option( 'js_field_position' );
delete_option( 'js_display_fields' );
delete_option( 'js_fields_heading' );
delete_option( 'js_title_heading' );
delete_option( 'js_content_count' );
delete_option( 'js_total_number' );
delete_option( 'js_custom_css' );
