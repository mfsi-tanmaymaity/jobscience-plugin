<?php
/**
 * Name			: jobscience-shortcode.php
 * Description	: This file contain shortcode functions of this plugin.
 * Author		: JobScience
 * Date			: 05/04/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// Prevent direct access.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Shortcode function.
 * @param array $attribute Shortcode attributes.
 */
function jobscience_jobscience_shortcode( $attribute ) {
	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

	// Check the plugin is configure or not.
	$plugin_configure = get_option( 'js-organization' );
	// Valid Rss Feed checking.
	$rss_check = get_option( 'js-rss-feed-url' );
	if ( ! $plugin_configure || ! $rss_check ) {
		return false;
	}

	// If the picklist fields attribute not present on the short code then return flase.
	if ( ! isset( $attribute['picklist'] ) ) {
		return '<strong>Invalid Shortcode, Picklist attribute not present.<strong>';
	}
	$picklist = trim( $attribute['picklist'] );
	$picklist_array = explode( '  ,  ', $picklist );

	// Create an associative array with the attribute value of all picklist.
	$picklist_attribute = array();
	if ( is_array( $picklist_array ) && ! empty( $picklist_array ) ) {
		foreach ( $picklist_array as $key => $value ) {
			// By default shortcode key will be replace by all lower case.
			$shortcode_key = strtolower( $value );
			$all_picklist_attribute = isset( $attribute[ $shortcode_key ] ) ? $attribute[ $shortcode_key ] : '';
			$picklist_attribute[ $value ] = $all_picklist_attribute;
		}
	}

	// Set the page per job variable.
	$job_per_page = get_option( 'js_total_number', 10 );

	// Set the offset for LIMIT, Initially it will be 0.
	$offset = 0;

	// Call the function to get all matching job.
	$results = jobscience_get_matching_job( $picklist_attribute, '', $offset, $job_per_page, false );
	// Call the function to get the total number of matching job,
	// Pass offset and job per page parameters as false
	// Pass $match parameter as true so that it return total matching job
	// Passing empty string for search.
	$matched = jobscience_get_matching_job( $picklist_attribute, '', false, false, true );

	// Start the internal buffer to save the html in the buffer.
	ob_start();
?>
	<div id="jobscience-shortcode" class="jobscience-shortcode-body">
		<div class="shortcode-attribute">
		<?php
			$field_position = get_option( 'js_field_position' );
			$position = ! empty( $field_position ) ? $field_position : 'horizontally';
		?>
			<input type="hidden" value="<?php echo esc_attr( $position ); ?>" class="js-filed-postition" />
			<input type="hidden" value="<?php echo absint( $job_per_page ); ?>" class="js-post-per-page" />
			<input type="hidden" value="<?php echo esc_attr( $picklist ); ?>" class="js-picklists-filter" />
			<input type="hidden" id="jobscience-search-nonce" value="<?php echo wp_create_nonce( 'jobscience_search_nonce' ) ?>" />
		</div>
		<div id="js-all-job">
			<a href="" ><button>All Jobs</button></a>
		</div>
		<div class="jobscience-search">
			<form onsubmit="return false" autocomplete="off">
				<div class="js-search-text">
					<input type="text" value="" class="js-search-text-field" />
				</div>

				<?php
				// Run a loop to get all picklist fields from the shortcode.
				if ( is_array( $picklist_array ) && ! empty( $picklist_array ) ) {
					foreach ( $picklist_array as $picklist_field ) {
						// By default shortcode key will be replace by all lower case.
						$shortcode_key = strtolower( $picklist_field );
					?>
						<div id="js-search-<?php echo esc_attr( $picklist_field ); ?>" class="jobscience-picklist-filter">
							<select>
								<?php
								// If the attribute is empty, then call the function to get all value.
								if ( ! isset( $attribute[ $shortcode_key ] ) ) {
									// Call the function to create the meta key.
									$meta_key = jobscience_create_meta_key( $picklist_field );
									$search = jobscience_get_dept_loc_function( $meta_key );
									// Set $search_count, add +1 for the extra option which will select all location/department/Job Function.
									$search_count = count( $search ) + 1;
								} else {
									$search = explode( '  ,  ', $attribute[ $shortcode_key ] );
									$search_count = count( $search );
								}

								// If the search count is not 1 then display 1st option.
								if ( 1 !== $search_count ) {
									// Call the function to get the Custom name.
									$custom_name = jobscience_get_custom_name( $picklist_field );
									$all_picklist_value = isset( $attribute[ $shortcode_key ] ) ? $attribute[ $shortcode_key ] : '';
								?>
									<option value="<?php echo esc_attr( $all_picklist_value ); ?>">Pick <?php echo esc_attr( ucwords( $custom_name ) ); ?></option>
								<?php
								}

								// Create search select field.
								if ( is_array( $search ) ) {
									// Run a loop to print all option.
									foreach ( $search as $key => $value ) {
								?>
										<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $value ); ?></option>
								<?php
									}
								}
							?>
							</select>
						</div>
					<?php
					}
				}
				?>
				<div class="js-search-submit">
					<button class="js-search-submit-button" value="submit">Search</button>
				</div>
			</form>
		</div>
		<!-- Pagination block -->
		<div class="jobscience-pagination-section">
			<div class="js-total-job">
				<p><?php echo absint( $matched ); ?>&nbsp;Open positions&nbsp;</p>
				<p class="js-matching-job-count"></p>
			</div>
			<div class="js-pagination">
				<?php
				if ( $matched > 0 ) {
					echo jobscience_pagination_create( $matched, $job_per_page, 1 );
				}
			?>
			</div>
		</div>
		<?php
		// Get display fields name from the option table.
		$display_fields = get_option( 'js_display_fields' );
		// Get display fields heading from the option table.
		$fields_heading = get_option( 'js_fields_heading' );
		// Get the job Title Heading.
		$title_heading = get_option( 'js_title_heading' );
		// Check the matched jobs is not 0.
		if ( $matched > 0 ) {
		?>
		<div class='jobscience-result-heading <?php echo esc_attr( $position ); ?>'>
			<div class="js-job-row-0 js-job">
				<div id="js-job-col-1" class="js-job-detail <?php echo esc_attr( $position ); ?>">
					<strong><?php echo esc_attr( $title_heading ); ?></strong>
				</div>
				<?php
				if ( is_array( $fields_heading ) ) {
					foreach ( $fields_heading as $col => $heading ) {
				?>
					<div id="js-job-col-<?php echo esc_attr( $col + 2 ); ?>" class="js-job-detail <?php echo esc_attr( $position ); ?>" >
						<strong><?php echo esc_attr( $heading ); ?></strong>
					</div>
				<?php
					}
				}
				?>
				<div class="js-job-clear-float"></div>
			</div>
		</div>
		<div class="jobscience-result">
			<?php
			// Print all jobs.
			if ( is_array( $results ) ) {
				// Run loop on the $results array to get all job ID and Title.
				foreach ( $results as $key => $job ) {
					// Save ID and title in variable.
					$id = $job->ID;
					$title = $job->post_title;

					// Get job id and siteurl meta value from the postmeta table and create the apply link.
					$apply_link = get_post_meta( $id, 'js_job_siteURL', true );
					// Replace the ts2__jobdetails with ts2__Register to link the Apply button with the Application form.
					$apply_link = str_replace( 'ts2__jobdetails?', 'ts2__Register?', $apply_link );
			?>
					<div class="js-job-row-<?php echo esc_attr( $key + 1 ); ?> js-job <?php echo esc_attr( $position ); ?>">
						<div id="js-job-col-1" class="js-job-detail <?php echo esc_attr( $position ); ?>">
							<?php if ( ! empty( $title ) ) { ?>
								<p class="js-job-title">
									<a href="<?php echo esc_url( $apply_link ); ?>">
										<?php echo esc_attr( ucwords( $title ) ); ?>
									</a>
								</p>
								<input type="hidden" value="<?php echo absint( $id ); ?>" class="js-post-id" />
							<?php } ?>
						</div>
					<?php
					if ( is_array( $display_fields ) ) {
						foreach ( $display_fields as $col => $meta_key ) {
							if ( 'content' !== $meta_key ) {
								// Get the meta value from the postmeta table.
								$meta_value = get_post_meta( $id, $meta_key, true );
								// If the field type is date then check the current date format and change the vaue on the current format.
								$rss_tag_detail = jobscience_get_rss_tag_details( $meta_key );
								$meta_value = 'date' == $rss_tag_detail['rss_field_type'] && ! empty( $meta_value ) && strtotime( $meta_value ) ? date( get_option( 'date_format' ), strtotime( $meta_value ) ) : $meta_value;
							} else {
								$word_count = get_option( 'js_content_count', 100 );
								// Get the post content.
								$meta_value = substr( strip_tags( get_post_field( 'post_content', $id ) ), 0, $word_count );
							}
					?>
						<div id="js-job-col-<?php echo esc_attr( $col + 2 ); ?>" class="js-job-detail <?php echo esc_attr( $position ); ?>" >
							<p><?php echo esc_attr( $meta_value ); ?></p>
						</div>
					<?php
						}
					}
					?>
						<div id="js-job-col-last" class="js-job-detail <?php echo esc_attr( $position ); ?>">
							<a href="<?php echo esc_url( $apply_link ); ?>" target="_blank" ><button>Apply</button></a>
						</div>
						<div class="js-job-clear-float"></div>
					</div>
			<?php
				}
			}
			?>
		</div>
	<?php
		} else {
	?>
			<div class="jobscience-result">
			<p>No Job Found.</p>
			</div>
		<?php } ?>
	</div>
<?php

	return ob_get_clean();
}

add_shortcode( 'jobscience', 'jobscience_jobscience_shortcode' );
