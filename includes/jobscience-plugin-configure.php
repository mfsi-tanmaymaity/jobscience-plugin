<?php
/**
 * Name			: jobscience-plugin-configure.php
 * Description	: This file contains HTML for Plugin Configure page and the Configuration check functionality.
 * Author		: JobScience
 * Date			: 05/19/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// Include the function file.
require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );
?>

<div id="jobscience-plugin-configure" class="wrap">
	<h2>JobScience Plugin Configuration</h2>
<?php
$error	= false;
$url	= '';

// Check the nonce field is coming from configure page form.
if ( isset( $_POST['jobscience_configure_nonce_name'] ) && wp_verify_nonce( wp_unslash( $_POST['jobscience_configure_nonce_name'] ), 'jobscience_configure_nonce' ) ) {
	// Collect all data from POST.
	$username = trim( $_POST['js_configure_username'] );
	$password = trim( $_POST['js_configure_password'] );
	$security = trim( $_POST['js_configure_security'] );

	// Check any field is empty or not.
	if ( $username && $password && $security ) {
		// Call the function to SOAP call.
		$return = jobscience_salesforce_configuration( $username, $password, $security );

		// If return is not true(boolean) then show error message.
		if ( true !== $return ) {
		?>
			<div class="error"><p><?php echo $return; ?></p></div>
		<?php
		}
	} else {
	?>
		<div class="error"><p>Please insert all values.</p></div>
	<?php
	}
} else if ( isset( $_POST['jobscience_reconfigure_nonce_name'] ) && wp_verify_nonce( $_POST['jobscience_reconfigure_nonce_name'], 'jobscience_reconfigure_nonce' ) ) {
	// Check the nonce field is coming from reconfigure form.
	// Remove all configure data from option table.
	delete_option( 'js-organization' );
} else if ( isset( $_POST['jobscience_rss_feed_nonce_name'] ) && wp_verify_nonce( $_POST['jobscience_rss_feed_nonce_name'], 'jobscience_rss_feed_nonce' ) ) {
	// Check the nonce field is coming from RSS Feed form.
	$url = trim( $_POST['js_rss_feed'] );
	// If the RSS Feed URL in not empty then call the function to get the jobs.
	if ( ! empty( $url ) ) {
		$return = jobscience_get_salesforce_jobs_rss( $url );
		if ( 'invalid' === $return ) {
			$rss_error = true;
		?>
			<div class="error"><p>Invalid RSS Feed URL.</p></div>
		<?php
		} else if ( 'tag_absent' === $return ) {
			$rss_error = true;
		?>
			<div class="error"><p>Please fill the RSS Feed Tag names.</p></div>
		<?php
		} else {
		?>
			<div class="updated"><p>All Jobs copied from the. Number of jobs is <strong><?php echo $return; ?></strong></p></div>
		<?php
		}
	} else {
		// Set the $rss_error true.
		$rss_error = true;
	?>
		<div class="error"><p>Please insert the RSS Feed URL on the text field.</p></div>
	<?php
	}
} else if ( isset( $_POST['jobscience_rss_nonce_name'] ) && wp_verify_nonce( $_POST['jobscience_rss_nonce_name'], 'jobscience_rss_nonce' ) ) {
	// Check the nonce field is coming from RSS Feed Tags form.
	// Declare variable.
	$tag_name = isset( $_POST['js_rss_tag_name'] ) ? $_POST['js_rss_tag_name'] : '';
	$custom_name = isset( $_POST['js_custom_name'] ) ? $_POST['js_custom_name'] : '';
	$rss_field_type = isset( $_POST['js_rss_field_type'] ) ? $_POST['js_rss_field_type'] : '';
	$tag_array = array();

	// Proceed if all are array.
	if ( is_array( $tag_name ) && is_array( $custom_name ) && is_array( $rss_field_type ) ) {
		// Run a loop on $tag_name.
		foreach ( $tag_name as $key => $tag ) {
			// Remove white space.
			$tag = trim( $tag );
			$custom = trim( $custom_name[ $key ] );
			$field_type = trim( $rss_field_type[ $key ] );

			// Check for not empty value.
			if ( ! empty( $tag ) && ! empty( $custom ) && ! empty( $field_type ) ) {
				// Make a associative array.
				$temp_array = array(
					'tag'			=> $tag,
					'custom_name'	=> $custom,
					'rss_field_type'	=> $field_type,
				);
				array_push( $tag_array, $temp_array );
			}
		}
	}

	// If the form is not empty then update else keep the old value.
	if ( ! empty( $tag_array ) ) {
		// Update the option table.
		update_option( 'js-rss-tag', $tag_array );

		// Section to delete unwanted meta value.
		// Get all meta key and all present meta key from meta table.
		$js_all_meta_keys = jobscience_get_all_meta_key();
		$js_meta_keys = jobscience_get_meta_key();

		// Add Job ID and job Siteurl meta key to not add them in remove meta key
		array_push( $js_meta_keys, 'js_job_id', 'js_job_siteURL' );

		// Remove $js_meta_keys from $js_all_meta_keys to get a array which will contain the meta key which is not using.
		$remove_meta_keys = array();
		if ( is_array( $js_all_meta_keys ) && is_array( $js_meta_keys ) ) {
			$remove_meta_keys = array_diff( $js_all_meta_keys, $js_meta_keys );
		}

		// Check the array is not empty
		if ( is_array( $remove_meta_keys ) && ! empty( $remove_meta_keys ) ) {
			// Get display fields name from the option table.
			$display_fields = get_option( 'js_display_fields' );
			// Get display fields heading from the option table.
			$fields_heading = get_option( 'js_fields_heading' );

			foreach ( $remove_meta_keys as $remove_meta_key ) {
				// Remove the unwanted meta key from $display_fields array and the heading of the meta key form $field_heading array.
				$array_key = array_search( $remove_meta_key, $display_fields );
				unset( $display_fields[ $array_key ] );
				unset( $fields_heading[ $array_key ] );
				// Delete the meta key from meta table.
				delete_post_meta_by_key( $remove_meta_key );
			}
			// Reindex the array.
			$display_fields = array_values( $display_fields );
			$fields_heading = array_values( $fields_heading );
			update_option( 'js_display_fields', $display_fields );
			update_option( 'js_fields_heading', $fields_heading );
		}
		// Display the message.
		?>
		<div class="error">
			<p>After add/delete/modify any field in the RSS Feed Form, you need to do the below tasks.</p>
			<p>Please insert the RSS Feed URL and "Copy all jobs from Salesforce" Form Plugin Configure Page.</p>
			<p>Please go to the Plugin Styling Page and set the Field order.</p>
			<p>Please go to the Job Template Page and save the template once.</p>
		</div>
	<?php
	}
}

// Check the plugin is configure or not.
$plugin_configure = get_option( 'js-organization' );
// Get the rss tags name from option table.
$rss_tag = get_option( 'js-rss-tag' );

// If the plugin is not configured then display the configure form.
if ( ! $plugin_configure ) {
?>
	<p>Please insert all values and configure the plugin with your Salesforce account.</p>

	<form action="" method="POST" name="js_plugin_configure">
		<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_configure_nonce', 'jobscience_configure_nonce_name' );
		?>
		<!-- HTML for the Plugin Configure form  -->
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="js_configure_username">Username</label></th>
					<td><input type="text" name="js_configure_username" id="js-configure-username" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="js_configure_password">Password</label></th>
					<td><input type="password" name="js_configure_password" id="js-configure-password" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="js_configure_security">Security Token</label></th>
					<td><input type="text" name="js_configure_security" id="js-configure-security" /></td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input id="submit-configure" class="button button-primary" type="submit" value="Configure" />
		</p>
	</form>
<?php
} else {
	// If the plugin is configured, then below section will be display.
	$organization = get_option( 'js-organization' );
?>
	<p>Plugin is configured with your Salesforce account.</p>
	<p>Organization ID: <strong><?php echo $organization; ?></strong></p>
	<p>To reset the configuration, press below button.</p>

	<!-- HTML for the reconfigure form  -->
	<form action="" method="POST" name="js_plugin_reconfigure">
		<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_reconfigure_nonce', 'jobscience_reconfigure_nonce_name' );
		?>
		<p class="submit">
			<input id="submit-reconfigure" class="button button-primary" type="submit" value="Reconfigure" />
		</p>
	</form>

	<!-- HTML for the RSS Feed tags form  -->
	<h3>RSS Feed Tags form</h3>
	<form action="" method="POST" name="js_rss_fields_list" id="js-rss-fields-list">
		<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_rss_nonce', 'jobscience_rss_nonce_name' );
		?>
		<p><strong>Please insert the RSS Feed tag names and the custom name for the WordPress.</strong></p>
		<p><strong>Job Title and Job description are default field, no need to add them in below form.</strong></p>
		<p><strong>After add/delete/modify any field in the RSS Feed Form, you need to do the below tasks.</strong></p>
		<p><strong>Please insert the RSS Feed URL and "Copy all jobs from Salesforce" Form Plugin Configure Page.</strong></p>
		<p><strong>Please go to the Plugin Styling Page and set the Field order.</strong></p>
		<p><strong>Please go to the Job Template Page and save the template once.</strong></p>
		<!-- HTML for the RSS Feed tags table  -->
		<table class="wp-list-table widefat fixed striped" id="js-rss-feed-tag">
			<tbody>
				<tr>
					<th scope="col">Field Name in RSS Feed(Tag)</th>
					<th scope="col">Custom name( For WordPress)</th>
					<th scope="col">Field Type</th>
				</tr>
	<?php
	// Check the variable is array and not empty.
	if ( is_array( $rss_tag ) && ! empty( $rss_tag ) ) {
		// Run loop.
		foreach ( $rss_tag as $tag_value ) {
	?>
				<tr>
					<td><input type="text" name="js_rss_tag_name[]" class="js-rss-tag" value="<?php echo $tag_value['tag']; ?>" /></td>
					<td><input type="text" name="js_custom_name[]" class="js-custom-name" value="<?php echo $tag_value['custom_name']; ?>" /></td>
					<td>
						<select name="js_rss_field_type[]" class="js-rss-field-type">
							<option value="text" <?php echo 'text' == $tag_value['rss_field_type'] ? 'selected' : ''; ?>>Text</option>
							<option value="int" <?php echo 'int' == $tag_value['rss_field_type'] ? 'selected' : ''; ?>>Int</option>
							<option value="salary" <?php echo 'salary' == $tag_value['rss_field_type'] ? 'selected' : ''; ?>>Salary</option>
							<option value="date" <?php echo 'date' == $tag_value['rss_field_type'] ? 'selected' : ''; ?>>Date</option>
						</select>
					</td>
				</tr>
	<?php
		}
	} else {
		// Else display one empty row.
	?>
				<tr>
					<td><input type="text" name="js_rss_tag_name[]" class="js-rss-tag" /></td>
					<td><input type="text" name="js_custom_name[]" class="js-custom-name" /></td>
					<td>
						<select name="js_rss_field_type[]" class="js-rss-field-type">
							<option value="text">Text</option>
							<option value="int">Int</option>
							<option value="salary">Salary</option>
							<option value="date">Date</option>
						</select>
					</td>
				</tr>
	<?php } ?>
			</tbody>
		</table>

		<p class="submit">
			<input id="submit-rss-feed-tag" class="button button-primary" type="submit" value="Save" />
			<input id="js-rss-new-row" class="button button-primary" type="submit" value="Add new row" />
		</p>
	</form>
<?php
}

// If the plugin is configured and the rss tag are present then display RSS Feed URL form.
if ( $plugin_configure && is_array( $rss_tag ) && ! empty( $rss_tag )  ) {
?>
	<h3>RSS Feed URL Form</h3>
<?php
	$url_old = get_option( 'js-rss-feed-url' );
	if ( ! empty( $url_old ) ) {
	?>
		<p>Below RSS Feed is using currently:</p><p><strong><?php echo $url_old; ?></strong></p>
	<?php
	}

	// Check the rss field is present or not.
	$rss_check = get_option( 'js-rss-feed-url' );
	if ( ! $rss_check ) {
	?>
		<div class="error"><p>No valid RSS Feed used.</p></div>
	<?php
	}
?>
	<p>Please insert the RSS Feed URL.</p>

	<form action="" method="POST" name="js_plugin_rss_feed">
		<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_rss_feed_nonce', 'jobscience_rss_feed_nonce_name' );
		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="js_rss_feed">RSS Feed URL</label></th>
					<td>
					<?php
						// If error is present then add error class.
					if ( isset( $rss_error ) && $rss_error ) {
					?>
						<input type="text" class="js-styling-error" name="js_rss_feed" id="js-rss-feed"  value="<?php echo $url; ?>" />
					<?php
					} else {
							// Else display blank text field.
					?>
						<input type="text" name="js_rss_feed" id="js-rss-feed" />
					<?php } ?>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input id="submit-rss" class="button button-primary" type="submit" value="Copy All Job From Salesforce" />
		</p>
	</form>

<?php
	// Create the Salesforce outbound endpoint.
	$outbound_token = get_option( 'js-outbound-token' );
	$outbound_url = site_url( '/wp-content/plugins/job-manager-jobscience/outbound/js-endpoint.php' );
?>
	<h3>Outbound Message Settings</h3>
	<p> Use below URL for the Salesforce Outbound Message.</p>
	<p><strong><?php echo $outbound_url . '?js-token=' . $outbound_token; ?></strong></p>
	<p>Please add below fields on the Outbound Message. All fileds are mendatory.</p>
	<ul id="jobscience-outbound-fields">
		<li><strong>Id</strong></li>
		<li><strong>Name</strong></li>
		<li><strong>ts2__Job_Description__c</strong></li>
		<li><strong>ts2__Post_Job__c</strong></li>
	<?php
		// Check the variable is array and not empty.
		if ( is_array( $rss_tag ) && ! empty( $rss_tag ) ) {
			// Run loop.
			foreach ( $rss_tag as $tag_value ) {
	?>
				<li><strong><?php echo $tag_value['tag']; ?></strong></li>
	<?php
			}
		}
	?>
	</ul>
<?php
}
//echo 'hi' . get_option( 'date_format' );
$datetime = DateTime::createFromFormat('c', '2013-02-13T08:35:34.195Z');
echo 'hi' . $datetime;
echo '<br>' . date( get_option( 'date_format' ), strtotime('10') );
echo '<br>' . date( get_option( 'date_format' ), strtotime('3') );
echo '<br>' . strtotime('3');
echo '<br>' . strtotime('2013-02-13T08:35:34.195Z');
echo '<br>' . $nombre_format_francais = number_format('100000.12', 2, '.', ',');
echo '<br>' . $nombre_format_francais = number_format('100008760.00', 2, '.', ',');

?>
</div>
