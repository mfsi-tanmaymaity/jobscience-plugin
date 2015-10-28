<?php
/**
 * Name			: js-endpoint.php
 * Description	: This file contain the Salesforce outbound message listner functionality.
 * Author		: JobScience
 * Date			: 07/10/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// If the token is not present in the URL then return false.
$url_token = isset( $_GET['js-token'] ) ? $_GET['js-token'] : false;

if ( ! $url_token ) {
	$outputFile = fopen( 'outbound_error.txt', 'a' );
	fwrite( $outputFile, PHP_EOL . date( 'Y-m-d H:i:s' ) . ' Token is not present in the URL.' );
	fclose( $outputFile );
	die( 'Token is not present in the URL.' );
}

// Include the file to load WordPress.
require_once( '../../../../wp-load.php' );

// Get the token from WordPress database and match with the URL token.
$token = get_option( 'js-outbound-token' );

if ( $url_token !== $token ) {
	$outputFile = fopen( 'outbound_error.txt', 'a' );
	fwrite( $outputFile, PHP_EOL . date( 'Y-m-d H:i:s' ) . ' Invalid Token.' );
	fclose( $outputFile );
	die( 'Invalid Token.' );
}

// Include the function file.
require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

// Check the plugin is configure or not.
$plugin_configure = get_option( 'js-organization' );

// If the plugin is not configured then return.
if ( ! $plugin_configure ) {
	die( 'Plugin is not configured.' );
}

$org_id = $id = $job_open = '';

header( "Content-Type: text/plain\r\n" );
ob_start();

$capturedData = file_get_contents( 'php://input' );
//$content = fread( $capturedData, 5000 );
	//$outputFile = fopen( 'capturedData.txt', 'a' );
	//fwrite( $outputFile, PHP_EOL . $capturedData );
	//fclose( $outputFile );
// Enable the XML error handling.
libxml_use_internal_errors( true );
$xml = simplexml_load_string( (string) $capturedData );

// Check the XML is valid or not.
if ( false !== $xml ) {
	$notifications = $xml->children( 'http://schemas.xmlsoap.org/soap/envelope/' )->Body->children( 'http://soap.sforce.com/2005/09/outbound' )->notifications;
	$org_id = $notifications->OrganizationId;
	$job_tag = $notifications->Notification->sObject->children( 'urn:sobject.enterprise.soap.sforce.com' );
	// Get the Job ID.
	$id = $job_tag->Id;
	// Get the Job Name.
	$job_name = $job_tag->Name;
	// Get the job post type.
	$job_open = $job_tag->ts2__Post_Job__c;
	// Get the job description.
	$job_description = $job_tag->ts2__Job_Description__c;
	// Create the patterns and replacement array.
	$patterns = array();
	$patterns[0] = '/class="(.*?)"/';
	$patterns[1] = '/id="(.*?)"/';
	$replacements = array();
	$replacements[0] = '';
	$replacements[1] = '';
	// Remove the inline css and the class from the description.
	$job_description = preg_replace( $patterns, $replacements, $job_description );

	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );
	// Check the variable is array and not empty.
	if ( is_array( $rss_tag ) || ! empty( $rss_tag ) ) {
		// Run a loop for the rss tag.
		foreach ( $rss_tag as $tag ) {
			$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
			$tag_data = $job_tag->$tag['tag'];
			$tag_data = (string) $tag_data;

			// Run a switch case.
			switch ( $tag['rss_field_type'] ) {
				case 'int':
					$tag_data = ! empty( $tag_data ) ? intval( $tag_data ) : '';
					break;

				case 'salary':
					$tag_data = is_numeric( $tag_data ) && ! empty( $tag_data ) ? number_format( $tag_data, 2, '.', ',' ) : '';
					break;

				case 'date':
					$tag_data = ! empty( $tag_data ) && strtotime( $tag_data ) ? date( get_option( 'date_format' ), strtotime( $tag_data ) ) : '';
					break;
			}
			$temp[ $custom_name ] = $tag_data;
		}
	}
} else {
	// If not valid then write the errorn in the file.
	$outputFile = fopen( 'outbound_error.txt', 'a' );
	fwrite( $outputFile, PHP_EOL . date( 'Y-m-d H:i:s' ) . ' Invalid XML in the Outbound message.' );
	if ( is_array( libxml_get_errors() ) ) {
		foreach ( libxml_get_errors() as $error ) {
			fwrite( $outputFile, PHP_EOL . date( 'Y-m-d H:i:s' ) . $error->message );
		}
	}
	fclose( $outputFile );
	die( 'Invalid XML in the Outbound message.' );
}

// Get Organization ID from option table.
$organization = get_option( 'js-organization' );

// Check the outbound Organization ID is same or not and the.
if ( $organization == $org_id ) {
	// Get the post ID of the job.
	$jod_post_id = jobscience_get_post_id( $id, 'js_job_id' );
	// If the job open is true then change the job's post status in publish.
	if ( 'true' == $job_open  ) {
		$status = 'publish';
	} else {
		// Else change the post status in draft.
		$status = 'draft';
	}

	// Get the current post status.
	$old_status = get_post_status( $jod_post_id );
	// If the $jod_post_idis empty then the job is new job.
	if ( empty( $jod_post_id ) ) {
		$url = get_option( 'js-rss-feed-url' );

		// Check the $url in not empty.
		if ( $url ) {
			// Call the function to get all jobs from salesforce.
			$error = jobscience_get_salesforce_jobs_rss( $url, $id );
		}
	} else {
		// Else update the post status.
		$post_arg = array(
			'ID'           => $jod_post_id,
			'post_status' => $status,
		);
		wp_update_post( $post_arg );
		// Update post.
		$post_arg = array(
			'ID'           => $jod_post_id,
			'post_title'   => (string) $job_name,
			'post_content' => (string) $job_description,
			'post_status' => $status,
		);

		// Update the post into the database.
		wp_update_post( $post_arg );

		// Run a loop to update all meta field.
		foreach ( $temp as $key => $value ) {
			update_post_meta( $jod_post_id, $key, (string) $value );
		}
	}
	$outputFile = fopen( 'outbound_result.txt', 'a' );
	fwrite( $outputFile, PHP_EOL . date( 'Y-m-d H:i:s' ) . ' job id= ' . $id . ' Status= ' . $job_open );
	fwrite( $outputFile, ' Post ID= ' . $jod_post_id . ' Name= ' . $job_name );
	fclose( $outputFile );
}

fclose( $capturedData );
ob_end_clean();
print jobscience_outbound_respond( 'true' );
