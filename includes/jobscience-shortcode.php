<?php
/**
 * Name			: jobscience-shortcode.php
 * Description	: This file contain shortcode functions of this plugin.
 * Author		: JobScience
 * Date			: 05/04/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

/**
 * Shortcode function.
 * @param array $atts Shortcode attributes.
 */
function jobscience_jobscience_shortcode( $atts ) {
	// Save all attribute of the shortcode.
	$attribute = shortcode_atts( array(
		'department'	=> '',
		'location'		=> '',
		'function'		=> '',
	), $atts );

	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

	// Check the plugin is configure or not.
	$plugin_configure = get_option( 'js-organization' );
	// Valid Rss Feed checking.
	$rss_check = get_option( 'js-rss-feed-url' );
	if ( ! $plugin_configure || ! $rss_check ) {
		return false;
	}

	// Set the page per job variable.
	$job_per_page = get_option( 'js_total_number', 10 );

	// Get the total open job count.
	$count = jobscience_get_job_count();

	// Set the offset for LIMIT, Initially it will be 0.
	$offset = 0;

	// Call the function to get all matching job.
	$results = jobscience_get_matching_job( $attribute['department'], $attribute['location'], $attribute['function'], '', $offset, $job_per_page, false );
	// Call the function to get the total number of matching job,
	// Pass offset and job per page parameters as false
	// Pass $match parameter as true so that it return total matching job
	// Passing empty string for search.
	$matched = jobscience_get_matching_job( $attribute['department'], $attribute['location'], $attribute['function'], '', false, false, true );

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
				// Get the rss tags name from option table.
				$rss_tag = get_option( 'js-rss-tag' );
				// Check the variable is array and not empty.
				if ( is_array( $rss_tag ) || ! empty( $rss_tag ) ) {
					// Craete the array with the RSS Feed tag names.
					$search_array = array( 'ts2__Location__c', 'ts2__Job_Function__c', 'ts2__Department__c' );

					// Run a loop for the rss tag.
					foreach ( $rss_tag as $tag ) {
						// Check  the tag is present in the $search_array.
						if ( in_array( $tag['tag'], $search_array ) ) {
							$search_key = strtolower( str_replace( array( 'ts2__Job_', 'ts2__', '__c', '_' ), array( '', '', '', '-' ), $tag['tag'] ) );
				?>
							<div class="js-search-<?php echo esc_attr( $search_key ); ?>">
								<select>
									<?php
									// If the attribute is empty, then call the function to get all value.
									if ( '' === $attribute[ $search_key ] ) {
										// Create the meta key.
										$meta_key = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
										$search = jobscience_get_dept_loc_function( $meta_key );
										// Set $search-count, add +1 for the extra option which will select all location/department/Job Function.
										$search_count = count( $search ) + 1;
									} else {
										$search = explode( ' , ', $attribute[ $search_key ] );
										$search_count = count( $search );
									}

									// If the search count is not 1 then display 1st option.
									if ( 1 !== $search_count ) {
									?>
										<option value="<?php echo esc_attr( $attribute[ $search_key ] ); ?>">Pick <?php echo esc_attr( ucwords( $tag['custom_name'] ) ); ?></option>
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
