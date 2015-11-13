<?php
/**
 * Name			: jobscience-plugin-styling.php
 * Description	: This file contains HTML for Plugin Styling page. From this page Admin can setup which field will be display in Job Listing page.
 * Author		: JobScience
 * Date			: 05/25/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// Prevent direct access.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Include the function file.
require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

// Check the nonce field is present or not.
if ( isset( $_POST['jobscience_styling_nonce_name'] ) ) {
	// Verify he nonce field.
	if ( wp_verify_nonce( sanitize_text_field( $_POST['jobscience_styling_nonce_name'] ), 'jobscience_styling_nonce' ) ) {
		$post_array = filter_input_array( INPUT_POST );
		$post_array = wp_unslash( $post_array );

		$fields = isset( $post_array['js_display_field'] ) ? $post_array['js_display_field'] : '';
		$order = isset( $post_array['js_display_field_order'] ) ? $post_array['js_display_field_order'] : '';
		$heading = isset( $post_array['js_field_heading'] ) ? $post_array['js_field_heading'] : '';
		$position = isset( $post_array['js_field_position'] ) ? sanitize_text_field( $post_array['js_field_position'] ) : 'horizontally';

		update_option( 'js_field_position', $position );

		if ( '' !== $fields && '' !== $order ) {
			$order_array = array();
			$heading_array = array();
			if ( is_array( $order ) ) {
				asort( $order );
				foreach ( $order as $key => $value ) {
					$temp = array();
					if ( isset( $fields[ $key ] ) ) {
						array_push( $order_array, $fields[ $key ] );
					}
					if ( isset( $heading[ $key ] ) ) {
						array_push( $heading_array, esc_attr( $heading[ $key ] ) );
					}
				}
			}

			update_option( 'js_display_fields', $order_array );
			update_option( 'js_fields_heading', $heading_array );
		}
		// Insert job title heading in different option.
		$title_heading = isset( $post_array['js_title_heading'][0] ) && ! empty( $post_array['js_title_heading'][0] ) ? $post_array['js_title_heading'][0] : 'Job Title';
		update_option( 'js_title_heading', esc_attr( $title_heading ) );

		// Update the option to store the number of words to display form content.
		if ( isset( $post_array['js_content_count'] ) ) {
			$word_count = jobscience_check_number( $post_array['js_content_count'], 100 );
			update_option( 'js_content_count', $word_count );
		}

		// Update the total number of jobs for the job listing page.
		if ( isset( $post_array['js_total_jobs'] ) ) {
			$total_jobs = jobscience_check_number( $post_array['js_total_jobs'], 10 );
			update_option( 'js_total_number', $total_jobs );
		}
	}
} else if ( isset( $_POST['jobscience_custom_css_nonce_name'] ) ) {
	// Verify he nonce field.
	if ( wp_verify_nonce( sanitize_text_field( $_POST['jobscience_custom_css_nonce_name'] ), 'jobscience_custom_css_nonce' ) ) {
		// Save the custom css on option table.
		update_option( 'js_custom_css', $_POST['js_custom_css_field'] );
	}
}
?>

<div id="jobscience-plugin-configure" class="wrap">
	<h2>JobScience Plugin Styling</h2>
	<h3>Arrange the fields position on Job Listing Page.</h3>
		<?php
		// Check the plugin is configure or not.
		$plugin_configure = get_option( 'js-organization' );
		if ( ! $plugin_configure ) {
		?>
			<div class="error"><p>The Plugin is not configured. Please configure first.</p></div>
		<?php
		}

		// Check the rss field is present or not.
		$rss_check = get_option( 'js-rss-feed-url' );
		if ( ! $rss_check ) {
		?>
			<div class="error"><p>No valid RSS Feed used. Go to Plugin Configure Page.</p></div>
		<?php
		}
		?>
	<form action="" method="POST" name="js_plugin_styling" autocomplete="off">
	<?php
		$meta_keys = jobscience_get_meta_key();

		// Get the option value.
		$display_fields = get_option( 'js_display_fields' );
		$fields_heading = get_option( 'js_fields_heading' );

		// Add the nonce field.
		wp_nonce_field( 'jobscience_styling_nonce', 'jobscience_styling_nonce_name' );
	?>
		<table class="wp-list-table widefat fixed striped" id="js-plugin-styling-table">
			<tbody>
				<tr>
					<th scope="col" class="first-col"></th>
					<th scope="col" class="second-col"><strong>Field Name</strong></th>
					<th scope="col" class="third-col"><strong>Field Order</strong></th>
					<th scope="col" class="forth-col"><strong>Heading</strong></th>
				</tr>
				<tr>
					<td class="first-col"></td>
					<td class="second-col"><strong>Job Title</strong><br />Title will be display always.</td>
					<td class="third-col"><input type="text"  size="2" disabled="disabled"value="0" /></td>
					<td class="forth-col"><input type="text" class="js-title-heading" name="js_title_heading[]" value="<?php echo esc_attr( get_option( 'js_title_heading' ) ); ?>" /></td>
				</tr>
				<tr>
					<?php
					$count = get_option( 'js_content_count' );
					$count = ! empty( $count ) || 0 !== $count ? $count : 100;

					// Check that content/description is already set as display field or not.
					if ( is_array( $display_fields ) && in_array( 'content', $display_fields ) ) {
						// Save the index of the current meta key from $display_fields array, Add +1 because the indexing start from 0.
						$set = array_search( 'content', $display_fields ) + 1;
					?>
						<td class="first-col"><input type="checkbox" name="js_display_field[]" value="content" class="js-styling-fields" checked="checked"/></td>
						<td class="second-col">Job Description
							<div id="js-content-count" >
								Words Count <input type="text" name="js_content_count" id="js-content-count-field" class="js-number-field" value="<?php echo absint( $count ); ?>" size="3" />
							</div>
						</td>
						<td class="third-col"><input type="text" name="js_display_field_order[]" size="2" class="js-styling-order js-number-field" value="<?php echo absint( $set ); ?>"/></td>
						<td class="forth-col"><input type="text" class="js-field-heading" name="js_field_heading[]" value="<?php echo esc_attr( $fields_heading[ $set - 1 ] ); ?>" /></td>
				<?php
					} else {
				?>
						<td class="first-col"><input type="checkbox" name="js_display_field[]" value="content" class="js-styling-fields" /></td>
						<td class="second-col">Job Description
						<div id="js-content-count" class="js-hidden" >
							Words Count <input type="text" name="js_content_count" id="js-content-count-field" class="js-number-field" value="<?php echo absint( $count ); ?>" size="3" />
						</div>
					</td>
					<td class="third-col"><input type="text" name="js_display_field_order[]" size="2" class="js-styling-order js-number-field" disabled="disabled" /></td>
					<td class="forth-col"><input type="text" class="js-field-heading" name="js_field_heading[]" disabled="disabled" /></td>
				<?php
					}
				?>
				</tr>
	<?php
	// For all meta field.
	if ( is_array( $meta_keys ) ) {
		foreach ( $meta_keys as $meta_key ) {
			// Skip the job id and siteURL.
			if ( 'js_job_id' !== $meta_key && 'js_job_siteURL' !== $meta_key ) {
				// Check that the meta_key is already set as display field or not.
				if ( is_array( $display_fields ) && in_array( $meta_key, $display_fields ) ) {
					// Save the index of the current meta key from $display_fields array, Add +1 because the indexing start from 0.
					$set = array_search( $meta_key, $display_fields ) + 1;
	?>
					<tr>
						<td class="first-col"><input type="checkbox" name="js_display_field[]" value="<?php echo esc_attr( $meta_key ); ?>" class="js-styling-fields" checked="checked"/></td>
						<td class="second-col"><?php echo esc_attr( ucwords( str_replace( array( 'js_job_', '_' ), array( '', ' ' ), $meta_key ) ) ); ?></td>
						<td class="third-col"><input type="text" name="js_display_field_order[]" size="2" class="js-styling-order js-number-field" value="<?php echo absint( $set ); ?>" /></td>
						<td class="forth-col"><input type="text"  class="js-field-heading" name="js_field_heading[]" value="<?php echo esc_attr( $fields_heading[ $set - 1 ] ); ?>" /></td>
					</tr>
	<?php
				} else {
	?>
					<tr>
						<td class="first-col"><input type="checkbox" name="js_display_field[]" value="<?php echo esc_attr( $meta_key ); ?>" class="js-styling-fields" /></td>
						<td class="second-col"><?php echo esc_attr( ucwords( str_replace( array( 'js_job_', '_' ), array( '', ' ' ), $meta_key ) ) ); ?></td>
						<td class="third-col"><input type="text" name="js_display_field_order[]" size="2" class="js-styling-order js-number-field" disabled="disabled" /></td>
						<td class="forth-col"><input type="text"  class="js-field-heading" name="js_field_heading[]" disabled="disabled" /></td>
					</tr>
	<?php
				}
			}
		}
	}
	?>
			</tbody>
		</table>
		<br />

		<!-- Table for Field position setting -->
		<table class="form-table">
			<tbody>
				<tr>
					<td><input type="radio" name="js_field_position" value="horizontally" <?php if ( get_option( 'js_field_position' ) !== 'vertically' ) { echo 'checked="checked"'; } ?>/>Display Fields Horizontally.</td>
				</tr>
				<tr>
					<td><input type="radio" name="js_field_position" value="vertically" <?php if ( get_option( 'js_field_position' ) === 'vertically' ) { echo 'checked="checked"'; } ?>/>Display Fields Vertically.</td>
				</tr>
			</tbody>
		</table>
		<br />

		<!-- Table for Total number of jobs setting -->
		<table class="form-table">
			<tbody>
				<tr>
				<?php
					// Get total number jobs.
					$total_job = get_option( 'js_total_number' );
				?>
					<th><label for="js_total_jobs">Total number of jobs in Job listing page</label></th>
					<td><input type="text" name="js_total_jobs" class="js-number-field" value="<?php echo absint( $total_job ); ?>" size="2"/></td>
				</tr>
			</tbody>
		</table>
		<p><input type="submit" name="js_styling_submit" value="Submit" id="js-styling-submit" class="button button-primary" /></p>
	</form>
	<!-- Custom Css Form -->
	<form action="" method="POST" name="js_custom_css" id="js-custom-css">
	<?php
		// Add the nonce field.
		wp_nonce_field( 'jobscience_custom_css_nonce', 'jobscience_custom_css_nonce_name' );
		// Get the existing css from option table.
		$custom_css = get_option( 'js_custom_css' );
	?>
		<h3>Add Custom CSS</h3>
		<textarea name="js_custom_css_field" id="js-custom-css-field" rows="10" cols="30" ><?php echo esc_textarea( $custom_css ); ?></textarea>
		<p><input type="submit" name="js_custom_css_submit" value="Submit" id="js-custom-css-submit" class="button button-primary" /></p>
	</form>
</div>
