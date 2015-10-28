<?php
/**
 * Name			: jobscience-single-job-template.php
 * Description	: This file contain the functionality to create the template for Single job.
 * Author		: JobScience
 * Date			: 09/08/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 */

?>

<div id="jobscience-single-job-template" class="wrap">
	<h2>Create the Template for Single job</h2>
	<?php
	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-single-job-format.php' );

	// Verify the nonce field.
	if ( isset( $_POST['jobscience_job_format_nonce_name'] ) && wp_verify_nonce( sanitize_text_field( $_POST['jobscience_job_format_nonce_name'] ), 'jobscience_job_format_nonce' ) ) {
		$format = isset( $_POST['js_single_job_format'] ) ? $_POST['js_single_job_format'] : 1;
	} else if ( isset( $_POST['jobscience_job_template_nonce_name'] ) && wp_verify_nonce( sanitize_text_field( $_POST['jobscience_job_template_nonce_name'] ), 'jobscience_job_template_nonce' ) ) {
		// Code execute after submit the submit the template.
		$format = isset( $_POST['js_job_template_format'] ) ? $_POST['js_job_template_format'] : 1;
		$template_data = isset( $_POST['js_section'] ) ? $_POST['js_section'] : '';

		// Run a loop to check all template data.
		if ( is_array( $template_data ) ) {
			foreach ( $template_data as $key => $section ) {
				// Width validation check.
				$template_data[ $key ]['width'] = isset( $section['width'] ) && is_numeric( $section['width'] ) && $section['width'] <= 100 && $section['width'] >= 1 ? $section['width'] : 100;
			}
		}

		// Create the full template as array.
		$js_single_template = array(
				'format' => $format,
				'tempalte_data' => $template_data,
			);
		update_option( 'js-job-template', $js_single_template );
	}

	// Get the current template data.
	$current_template = get_option( 'js-job-template' );

	// If the $format is not set the set the current format.
	if ( ! isset( $format ) ) {
		$format = isset( $current_template['format'] ) ? $current_template['format'] : 1;
	}
	// Call the function to create the format array as per the $format.
	$format_array = jobscience_get_template_formate( $format );

	?>
	<div id="js-choose-format" >
		<form action='' method="POST" name="js_choose_format">
			<p><strong>Choose Format:</strong></p>
			<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_job_format_nonce', 'jobscience_job_format_nonce_name' );
			?>
			<div>
			<?php
			// Run a loop to display all format.
			for ( $i = 1;  $i <= 6 ;  $i++ ) {
				$image_url = plugins_url( '../images/formats', __FILE__ ) . '/format' . $i . '.png';
			?>
				<div class="js-job-formats">
					<div><input type="radio" name="js_single_job_format" class="js-single-job-format"  value="<?php echo $i; ?>" <?php if ( $format == $i ) { echo 'checked'; } ?> /></div>
					<div><img src="<?php echo $image_url; ?>"></div>
				</div>
			<?php
			}
			?>
			</div>
			<p id="js-single-job-format-submit"><input type="submit" name="js_single_job_format_submit" value="Change Format" class="button button-primary" /></p>
		</form>
	</div>
	<div id="js-create-template">
		<h3>Template Preview</h3>
		<form action='' method="POST" name="js_template_data">
			<input type="hidden" name="js_job_template_format" value="<?php echo $format; ?>" />
			<?php
			// Add the nonce field.
			wp_nonce_field( 'jobscience_job_template_nonce', 'jobscience_job_template_nonce_name' );

			// Create the SELECT field for "Add Field" to avoid the foreach loop multiple time.
			$add_field = '<select class="js-template-add-field">';
			$add_field .= '<option value="">Select</option>';
			$add_field .= '<option value="title">Job Title</option>';
			$add_field .= '<option value="description">Job Description</option>';

			// Get the rss tags name from option table.
			$rss_tag = get_option( 'js-rss-tag' );

			// Run loop.
			if ( is_array( $rss_tag ) ) {
				foreach ( $rss_tag  as $value ) {
					$add_field .= '<option value="' . $value['tag'] . '">' . $value['custom_name'] . '</option>';
				}
			}

			$add_field .= '</select>';
			// Run a loop if the $format_array is an array.
			if ( is_array( $format_array ) ) {
				$section_count = 1;
				foreach ( $format_array as $key => $value ) {
			?>
					<div id="js-template-row-<?php echo $key; ?>" class="js-template-row" >
					<?php
					if ( is_array( $value ) ) {
						foreach ( $value as $key1 => $value1 ) {
							// Save the current section data in a array.
							$current_section_data = isset( $current_template['tempalte_data'][ $section_count ] ) ? $current_template['tempalte_data'][ $section_count ] : array();
							// If the saved format and current format is same then display all value from database, else display default value.
							if ( $current_template['format'] == $format ) {
								// Get the width of current section from current template or from the template format array.
								$width = isset( $current_section_data['width'] ) ? $current_section_data['width'] : $value1;
							} else {
								$width = $value1;
							}

			?>
							<div class="js-template-col-<?php echo $key1; ?> js-template-column" style="width: <?php echo $width; ?>%;" >
								<div class="js_inside">
									<p><strong>Width (%):</strong> <input type="text" size="3" name="js_section[<?php echo $section_count; ?>][width]" class="js-section-width js-number-field" value="<?php echo $width; ?>" /></p>
									<div class="js-template-added-fields">
										<?php
										// If the saved format and current format is same then display all value from database, else display default value.
										if ( $current_template['format'] == $format ) {
											// Create Added Fields section for the current template part.
											$fields = isset( $current_section_data['fields'] ) ? $current_section_data['fields'] : array();

											if ( is_array( $fields ) ) {
												foreach ( $fields as $field_key => $field ) {
													if ( 'title' == $field ) {
														$custom_name = 'Job Title';
													} else if ( 'description' == $field ) {
														$custom_name = 'Job Description';
													} else {
														// Call the function to get the custom name from the RSS Tag name.
														$custom_name = jobscience_get_custom_name( $field );
													}

													if ( false !== $custom_name ) {
														// Get the font size for the current field.
														$font_size = isset( $current_section_data['font_size'][ $field_key ] ) ? $current_section_data['font_size'][ $field_key ] : '';
														$color = isset( $current_section_data['color'][ $field_key ] ) ? $current_section_data['color'][ $field_key ] : '';
														$text_format = isset( $current_section_data['text_format'][ $field_key ] ) ? $current_section_data['text_format'][ $field_key ] : '';
														// Create the HTML for each added field in the templete preview section.
													?>
														<div class="js-template-job-field">
															<div>
																<input type="hidden" name="js_section[<?php echo $section_count; ?>][fields][]" value="<?php echo $field; ?>" />
																<div class="js-template-field-title">
																	<strong><?php echo $custom_name; ?></strong>
																</div>
																<div class="js-template-field-delete">
																	<img src="<?php echo plugins_url( '../images/delete.png', __FILE__ ); ?>" class="js-template-field-delete-img" />
																</div>
															</div>
															<div class="js-template-field-style">
																<a class="js-templete-edit-style">Edit Style</a>
																<div class="js-template-style-section">
																	<div clas="js-template-field-font-size">
																		Font Size(px): <input type="text" size="2" class="js-number-field" name="js_section[<?php echo $section_count;?>][font_size][]" value="<?php echo $font_size; ?>" />
																	</div>
																	<div clas="js-template-field-color-section">
																		Color: <input type="text" size="2" class="js-template-field-color" name="js_section[<?php echo $section_count; ?>][color][]" value="<?php echo $color; ?>" />
																	</div>
																	<div clas="js-template-bold">
																		Text Format:
																		<select name="js_section[<?php echo $section_count; ?>][text_format][]" class="js-template-text-format">
																			<option value="" <?php echo '' == $text_format ? 'selected="selected"' : ''; ?> >Default</option>
																			<option value="bold" <?php echo 'bold' == $text_format ? 'selected="selected"' : ''; ?>>Bold</option>
																			<option value="italic" <?php echo 'italic' == $text_format ? 'selected="selected"' : ''; ?>>Italic</option>
																		</select>
																	</div>
																	<div class="clear"></div>
																</div>
															</div>
														</div>
													<?php
													}
												}
											}
										}
										?>
									</div>
									<p>
										<strong>Add Field: </strong><?php echo $add_field; ?>
									</p>
								</div>
							</div>
			<?php
							$section_count++;
						}
					}
			?>
					</div>
			<?php
				}
			}
			?>
			<p id="js-single-job-template-submit"><input type="submit" name="js_single_job_template_submit" value="Submit Template" class="button button-primary" /></p>
		</form>
	</div>
</div>
