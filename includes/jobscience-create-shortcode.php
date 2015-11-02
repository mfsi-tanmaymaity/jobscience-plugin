<?php
/**
 * Name			: jobscience-create-shortcode.php
 * Description	: This file contains HTML for Plugin Create Shortcode page.
 * Author		: JobScience
 * Date			: 10/29/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// Include the function file.
require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

// Get the rss tags name from option table.
$rss_tag = get_option( 'js-rss-tag' );

// Get only the fields whose "field type" is "text".
$picklist_fields = array();
if ( is_array( $rss_tag ) && ! empty( $rss_tag ) ) {
	foreach ( $rss_tag as $value ) {
		// Check the field type of the current tag.
		if ( 'text' === $value['rss_field_type'] ) {
			// Insert the tag detail on array.
			array_push( $picklist_fields, $value );
		}
	}
}

// Get the selected fields if the form is submitted.
// Varify nonce.
if ( isset( $_POST['picklist_nonce_name'] ) && wp_verify_nonce( $_POST['picklist_nonce_name'], 'picklist_nonce_action' ) ) {
	// Remove slashes.
	$form_data = wp_unslash( $_POST );
	// Sanitize form data.
	if ( isset( $form_data['jobscience_selected_picklist'] ) && is_array( $form_data['jobscience_selected_picklist'] ) ) {
		$selected_picklists = array_map( 'sanitize_text_field', $form_data['jobscience_selected_picklist'] );
	}
}
?>
<div id="create-shortcode-content" class="wrap">
	<h2>Job Manager by JobScience</h2>
	<h2>Shortcode Generator</h2>
	<?php
	// Check the plugin is configure or not.
	$plugin_configure = get_option( 'js-organization' );
	if ( ! $plugin_configure ) {
		echo '<div class="error"><p>The Plugin is not configured. Please configure first.</p></div>';
	}

	// Valid Rss feed checking.
	$rss_check = get_option( 'js-rss-feed-url' );
	if ( ! $rss_check ) {
		echo '<div class="error"><p>No valid RSS Feed used. Go to Plugin Configure Page.</p></div>';
	}

	// Check the picklist field array is not empty.
	if ( ! is_array( $picklist_fields ) || empty( $picklist_fields ) ) {
	?>
		<div id="no-text-field-message"><h4>There is no Picklist field whose field type is Test.</h4></div>
	<?php
	} else {
		// Create the HTML for the picklist fields.
	?>
		<div id="jobscience-picklist-fields-section">
			<form action="" method="POST" name="jobscience_picklist_form">
			<?php
			// Add nonce field.
			wp_nonce_field( 'picklist_nonce_action', 'picklist_nonce_name' );
			// Run a loop to get each picklist field.
			foreach ( $picklist_fields as $picklist ) {
			?>
				<p class="jobscience-picklist-fields">
				<?php
				// If the form is submitted and the picklist field is selected then checked the respective picklist field.
				if ( isset( $selected_picklists ) && is_array( $selected_picklists ) && in_array( $picklist['tag'], $selected_picklists ) ) {
				?>
					<input type="checkbox" name="jobscience_selected_picklist[]" class="jobscience-select-picklist" value="<?php echo esc_attr( $picklist['tag'] ); ?>" checked />
				<?php
				} else {
				?>
					<input type="checkbox" name="jobscience_selected_picklist[]" class="jobscience-select-picklist" value="<?php echo esc_attr( $picklist['tag'] ); ?>" />
				<?php } ?>
					<span><?php echo esc_attr( $picklist['custom_name'] ); ?></span>
				</p>
			<?php
			}
			?>
				<input id="submit-picklist-form" class="button button-primary" type="submit" value="Create Shortcode" />
			</form>
		</div>
	<?php
	}

	// If the selected picklist field are available then create the shortcode generation section.
	if ( isset( $selected_picklists ) && is_array( $selected_picklists ) ) {
	?>
		<div id="js-shortcode-generator">
		<?php
		echo '<pre>';print_r($selected_picklists); echo '</pre>';
		// Run a loop toget all picklist field.
		foreach ( $selected_picklists as $selected_picklist ) {
			// Call the function to create the meta key.
			$meta_key = jobscience_create_meta_key( $selected_picklist );
			// Call the function to get the Custom name.
			$custom_name = jobscience_get_custom_name( $selected_picklist );
			// Call the function to get all meta value of the meta key.
			$meta_values = jobscience_get_dept_loc_function( $meta_key );
		?>
			<div class="js-shortcode-select">
				<h3>Select <?php echo ucwords( $custom_name ); ?></h3>
				<select class="js-shortcode-select-field" multiple="multiple" >
					<option value="all">All</option>
					<?php
					if ( is_array( $meta_values ) ) {
						foreach ( $meta_values as $meta_value ) {
					?>
							<option value="<?php echo $meta_value; ?>"><?php echo ucwords( $meta_value ); ?></option>
					<?php
						}
					}
					?>
				</select>
				<?php $hidden_id = strtolower( str_replace( array( 'ts2__Job_', 'ts2__', '__c', '_' ), array( '', '', '', '-' ), $selected_picklist ) ); ?>
				<input type="hidden" id="js-shortcode-<?php echo $hidden_id; ?>" />
			</div>
		<?php
		}
		?>
		</div>
	<?php
	}
	?>













	<div id="js-shortcode-generator">
	<?php
	// Check the variable is array and not empty.
	if ( is_array( $rss_tag ) || ! empty( $rss_tag ) ) {
		// Craete the array with the RSS Feed tag names.
		$shortcode_array = array( 'ts2__Location__c', 'ts2__Job_Function__c', 'ts2__Department__c' );

		// Run a loop for the rss tag.
		foreach ( $rss_tag as $tag ) {
			// Check  the tag is present in the $shortcode_array.
			if ( in_array( $tag['tag'], $shortcode_array ) ) {
				// Create the meta key.
				$meta_key = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );

	?>

	<?php
			}
		}
	}
	?>
	</div>
	<div id="js-shortcode-result">
		<input type="text" value="[jobscience]" id="js-shortcode-result-field" />
	</div>
</div>
