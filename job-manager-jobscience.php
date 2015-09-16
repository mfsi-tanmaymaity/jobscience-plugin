<?php
/**
 * Plugin Name: Job Manager Jobscience
 * Description: This is a Job Portal plugin by Jobscience which will pull jobs from the Salesforce database. It needs your Salesforce credential to connect the plugin to your Salesforce account. It will pull all jobs using the RSS Feed URL. Using Shortcode you can display a job list on any page and control/filter through the Department, Location and Job Function.
 * Author: JobScience
 * Version: 1.0
 * Text Domain: job-manager-jobscience
 * Licence: GPL2
 * @package: Job Manager JobScience plugin
 **/

// Define the plugin path.
define( 'JS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Add shortcode file.
require_once( JS_PLUGIN_DIR . '/includes/jobscience-shortcode.php' );

/**
 * Function for Enqueue all js and css file.
 */
function jobscience_add_js_css_file() {
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker');
	// Include the js file.
	wp_enqueue_script( 'jobscience-js', plugins_url( '/js/custom.js', __FILE__ ), array( 'jquery' ) );
	// Include css file.
	wp_enqueue_style( 'jobscience-main-css', plugins_url( '/css/jobscience.css', __FILE__ ) );
	wp_enqueue_style( 'jobscience-custom-css', plugins_url( '/css/jobscience-custom.css', __FILE__ ) );

	// Include the js file for ajax and localize the js file so that we can use PHP value the js file and call the ajax.
	wp_enqueue_script( 'shortcode-pagination', plugins_url( '/js/jobscience-ajax.js', __FILE__ ), array( 'jquery' ) );
	wp_localize_script( 'shortcode-pagination', 'the_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

// Enqueue all js and css file.
add_action( 'init', 'jobscience_add_js_css_file' );

// Register the custom post type for the jobs.
add_action( 'init', 'register_jobscience_job' );

/**
 * Function for the register custom post type.
 */
function register_jobscience_job() {
	// Define the label array.
	$labels = array(
		'name'					=> __( 'Jobs', 'job-manager-jobscience' ),
		'singular_name'			=> __( 'Job', 'job-manager-jobscience' ),
		'menu_name'				=> __( 'Jobs', 'job-manager-jobscience' ),
		'all_items'				=> __( 'All Jobs', 'job-manager-jobscience' ),
		'add_new'				=> __( 'Add New', 'job-manager-jobscience' ),
		'add_new_item'			=> __( 'Add New Job', 'job-manager-jobscience' ),
		'edit_item'				=> __( 'Edit Job', 'job-manager-jobscience' ),
		'new_item'				=> __( 'New Job', 'job-manager-jobscience' ),
		'view_item'				=> __( 'View Job', 'job-manager-jobscience' ),
		'search_item'			=> __( 'Search Job', 'job-manager-jobscience' ),
		'not_found'				=> __( 'No Question found', 'job-manager-jobscience' ),
		'not_found_in_trash'	=> __( 'No Question found in trash', 'job-manager-jobscience' ),
	);

	// Define the arg array.
	$args = array(
		'labels'			=> $labels,
		'description'		=> __( 'This post type will save all job', 'job-manager-jobscience' ),
		'public'			=> true,
		'show_ui'			=> true,
		'show_in_menu'		=> true,
		'show_in_nav_menu'	=> true,
		'menu_position'		=> 10,
		'hierarchical'		=> false,
		'supports'			=> array( 'title', 'editor' ),
		'query_var'			=> true,
		'rewrite'			=> array(
								'slug' => 'job',
								),
	);

	// Register the custom post type.
	register_post_type( 'jobscience_job', $args );
}

/**
 * Add meta box.
 * @param Object $post Post object.
 */
function function_add_custom_meta_box( $post ) {
	add_meta_box( 'jobscience_job_meta_id', __( 'Add Meta field', 'job-manager-jobscience' ), 'jobscience_meta_section_html', 'jobscience_job' );
}

add_action( 'add_meta_boxes_jobscience_job', 'function_add_custom_meta_box' );

/**
 * Function to create the meta fields.
 * @param Object $post Post object.
 */
function jobscience_meta_section_html( $post ) {
	wp_nonce_field( 'jobscience_job_nonce', 'jobscience_job_nonce_name' );

	// Create the array for job id and job siteurl meta field.
	$meta_field_array = array(
		'id',
		'siteURL',
	);

	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );

	// Check the variable is array and not empty.
	if ( is_array( $rss_tag ) || ! empty( $rss_tag ) ) {
		// Run a loop on the rss tag array.
		foreach ( $rss_tag as $tag ) {
			// Create the meta key.
			$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
			$value = get_post_meta( $post->ID, $custom_name, true );
			// HTML for the meta box.
?>
			<div id="js-job-meta-<?php echo $tag['custom_name']; ?>" class="js-job-meta">
				<label for="js_job_<?php echo $tag['custom_name']; ?>"><b>Job <?php echo ucwords( $tag['custom_name'] ); ?>:</b></label>
				<input type="text" name="js_job_<?php echo strtolower( $tag['custom_name'] ); ?>" id="js-job-<?php echo $tag['custom_name']; ?>" value="<?php echo $value; ?>"/>
			</div>
<?php
		}

		// Run loop to create the job id and job siteURL meta box.
		foreach ( $meta_field_array as $field ) {
			// Create the meta key.
			$meta_key = 'js_job_' . $field;
			$value = get_post_meta( $post->ID, $meta_key, true );
			// HTML for the meta box.
?>
			<div id="js-job-meta-<?php echo $field; ?>" class="js-job-meta">
				<label for="js_job_<?php echo $field; ?>"><b>Job <?php echo ucwords( $field ); ?>:</b></label>
				<input type="text" name="js_job_<?php echo $field; ?>" id="js-job-<?php echo $field; ?>" value="<?php echo $value; ?>"/>
			</div>
<?php
		}
	}
}

/**
 * Function for the save post action.
 * @param int $post_id Post ID.
 */
function jobscience_save_post( $post_id ) {
	// Check the nonce field is present.
	if ( ! isset( $_POST['jobscience_job_nonce_name'] ) ) {
		return;
	}

	// Check the nonce field is coming from current form.
	if ( ! wp_verify_nonce( $_POST['jobscience_job_nonce_name'], 'jobscience_job_nonce' ) ) {
		return;
	}

	// Do nothing for auto save.
	if ( define( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );
	// Check the variable is array and not empty.
	if ( is_array( $rss_tag ) || ! empty( $rss_tag ) ) {
		// Include the function file.
		require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

		// Run lood on the rss tag array.
		foreach ( $rss_tag as $tag ) {
			// Create the meta key and update the meta key.
			$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
			jobscience_update_job_meta( $post_id, $custom_name, $_POST[ $custom_name ] );
		}
	}
}

add_action( 'save_post', 'jobscience_save_post' );

/**
 * Add the sub-menu for create short-code page.
 */
function jobscience_add_submenu() {
	// Create a array for all submenu.
	$all_menu_array = array( 'Create Shortcode', 'Plugin Configure', 'Styling', 'Job Template' );

	// Run a loop to create all submenu.
	foreach ( $all_menu_array as $value ) {
		$js_menu_slug = 'jobscience_' . str_replace( ' ', '_', strtolower( $value ) ) . '_menu';
		// Don't forget to create the callback function. The callback function name will be same as $js_menu_slug.
		// Create the menu.
		add_submenu_page(
			'edit.php?post_type=jobscience_job',
			__( $value, 'job-manager-jobscience' ),
			__( $value, 'job-manager-jobscience' ),
			'manage_options',
			$js_menu_slug,
			$js_menu_slug
		);
	}
}

add_action( 'admin_menu', 'jobscience_add_submenu' );

/**
 * This function is for the html of the create shortcode page.
 */
function jobscience_create_shortcode_menu() {
	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );
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
		?>

		<div id="js-shortcode-generator">
		<?php
		// Get the rss tags name from option table.
		$rss_tag = get_option( 'js-rss-tag' );
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
					// Call the function to get all meta value of the meta key.
					$meta_values = jobscience_get_dept_loc_function( $meta_key );
		?>
					<div class="js-shortcode-select">
						<h3>Select <?php echo ucwords( $tag['custom_name'] ); ?></h3>
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
						<?php $hidden_id = strtolower( str_replace( array( 'ts2__Job_', 'ts2__', '__c', '_'), array( "", "", "", '-' ), $tag['tag'] ) ); ?>
						<input type="hidden" id="js-shortcode-<?php echo $hidden_id; ?>" />
					</div>
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
<?php
}

/**
 * Call back function for the Plugin Configure page.
 */
function jobscience_plugin_configure_menu() {
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-plugin-configure.php' );
}

/**
 * Call back function for the Plugin Styling page.
 */
function jobscience_styling_menu() {
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-plugin-styling.php' );
}
/**
 * Call back function for the Plugin Styling page.
 */
function jobscience_job_template_menu() {
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-single-job-template.php' );
}

/**
 * Ajax function for the shortcode pagination.
 */
function jobscience_pagination_search_callback() {
	// Collect all data from the $_POST.
	$departments = isset( $_POST['department'] ) ? $_POST['department'] : '';
	$locations = isset( $_POST['location'] ) ? $_POST['location'] : '';
	$function = isset( $_POST['job_function'] ) ? $_POST['job_function'] : '';
	$search = isset( $_POST['search'] ) ? $_POST['search'] : '';
	$js_post_per_page = isset( $_POST['js_post_per_page'] ) ? $_POST['js_post_per_page'] : 10;
	$offset = isset( $_POST['offset'] ) ? $_POST['offset'] : 0;
	$position = isset( $_POST['position'] ) ? $_POST['position'] : 'horizontally';

	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

	// Call the function get all matching jobs.
	$results = jobscience_get_matching_job( $departments, $locations, $function, $search, $offset, $js_post_per_page, false );

	// Start the jog listing page HTML creation.
	$output = '';

	// Get display fields name from the option table.
	$display_fields = get_option( 'js_display_fields' );

	// Run loop on the $results array to get all job ID and Title.
	if ( is_array( $results ) ) {
		// Run loop.
		foreach ( $results as $key => $job ) {
			// Save ID and title in variable.
			$id = $job->ID;
			$title = $job->post_title;

			// Get job id and siteurl meta value from the postmeta table and create the apply link.
			$apply_link = get_post_meta( $id, 'js_job_siteURL', true );

			$row = $key + 1;
			$output .= '<div class="js-job-row-' . $row . ' js-job ' . $position . '">';
			$output .= '<div id="js-job-col-1" class="js-job-detail ' . $position . '">';
			// If title is not empty then add the title.
			if ( ! empty( $title ) ) {
				$output .= '<p class="js-job-title">';
					$output .= '<a href="' . $apply_link . '">';
						$output .= ucwords( $title );
					$output .= '</a></p>';
					$output .= '<input type="hidden" value="' . $id . '" class="js-post-id" />';
			}
			$output .= '</div>';

			// Add all selected filed with the html.
			if ( is_array( $display_fields ) ) {
				// Run a loop for all selected field.
				foreach ( $display_fields as $col => $meta_key ) {
					if ( 'content' !== $meta_key ) {
						// Get the meta value from the postmeta table.
						$meta_value = get_post_meta( $id, $meta_key, true );
					} else {
						$word_count = get_option( 'js_content_count', 100 );
						// Get the post content.
						$meta_value = substr( strip_tags( get_post_field( 'post_content', $id ) ), 0, $word_count );
					}

					$column = $col + 2;
					$output .= '<div id="js-job-col-' . $column . '" class="js-job-detail ' . $position . '" >';
						$output .= '<p>' . $meta_value . '</p>';
					$output .= '</div>';
				}
			}

			$output .= '<div id="js-job-col-last" class="js-job-detail ' . $position . '">';
				$output .= '<a href="' . $apply_link . '" target="_blank" ><button>Apply</button></a>';
			$output .= '</div>';
			$output .= '<div class="js-job-clear-float"></div>';
			$output .= '</div>';
		}
	}

	$return = array();

	// Call the function to get the count of matching job, pass offset and job per page parameters as false and $match parameter as true so that it return total matching job.
	$matched = jobscience_get_matching_job( $departments, $locations, $function, $search, false, false, true );

	// Set the pagination html.
	$page_number = ( $offset / $js_post_per_page ) + 1;
	$pagination = '';
	if ( $matched > 0 ) {
		$pagination = jobscience_pagination_create( $matched, $js_post_per_page, $page_number );
	}

	// Create the return array.
	$return = array(
		'match' => $matched,
		'pagination' => $pagination,
	);

	$return['html_data'] = $output;
	echo json_encode( $return );
	wp_die();
}

add_action( 'wp_ajax_shortcode_pagination_search', 'jobscience_pagination_search_callback' );
add_action( 'wp_ajax_nopriv_shortcode_pagination_search', 'jobscience_pagination_search_callback' );

/**
 * Function to get the single job detail.
 */
function jobscience_get_single_job() {
	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-single-job-format.php' );

	// Collect post id from the $_POST.
	$job_post_id = isset( $_POST['post_id'] ) ? $_POST['post_id'] : '';

	// Create the HTML for the single job display.
	$output = '';

	// Check the post id is pesect on the Ajax call.
	if ( '' === $job_post_id ) {
		$output .= '<div class="js-no-job"><strong>No Job found</strong></div>';
		$output .= '<p id="js-back-job-page"><button id="js-back-job-button" value="">Back to Jobs</button></p>';
		echo $output;
		wp_die();
	}

	$post_type = get_post_type( $job_post_id );

	// Check the post type and display error.
	if ( 'jobscience_job' !== $post_type ) {
		$output .= '<div class="js-no-job"><strong>No Job found</strong></div>';
		$output .= '<p id="js-back-job-page"><button id="js-back-job-button" value="">Back to Jobs</button></p>';
		echo $output;
		wp_die();
	}

	// Get the current template data.
	$current_template = get_option( 'js-job-template' );
	// Check the template data is present in option table.
	if ( ! $current_template || ! isset( $current_template['format'] ) ) {
		$output .= '<div class="js-no-template"><strong>' . get_the_title( $job_post_id ) . '</strong></div>';
		$output .= '<p id="js-back-job-page"><button id="js-back-job-button" value="">Back to Jobs</button></p>';
		echo $output;
		wp_die();
	}

	// Get the template format.
	$format = isset( $current_template['format'] ) ? $current_template['format'] : 1;
	// Call the function to create the format array as per the $format.
	$format_array = jobscience_get_template_formate( $format );
	if ( is_array( $format_array ) ) {
		$section_count = 1;
		ob_start();
?>
		<div id="js-single-job">
		<?php
			foreach ( $format_array as $key => $value ) {
		?>
				<div id="js-single-job-row-<?php echo $key; ?>" class="js-single-job-row" >
				<?php
					if ( is_array( $value ) ) {
						foreach ( $value as $key1 => $value1 ) {
							// Get the width of current section from current template or from the template format array.
							$width = isset( $current_template['tempalte_data'][$section_count]['width']) ? $current_template['tempalte_data'][$section_count]['width'] : $value1;
							// Create Added Fields section for the current template part.
							$fields = isset( $current_template['tempalte_data'][$section_count]['fields'] ) ? $current_template['tempalte_data'][$section_count]['fields'] : array();
				?>
							<div class="js-single-job-col-<?php echo $key1; ?> js-single-job-column" style="width: <?php echo $width; ?>%;" >
								<div class="js_inside">
								<?php
									if ( is_array( $fields ) ) {
										foreach ( $fields as $key2 => $field ) {
											if ( 'title' == $field ) {
												echo '<p class="js-single-job-field' . $key2 . '" >' . get_the_title( $job_post_id ) . '</p>';
											} else if ( 'description' == $field ) {
												$meta_value = get_post_field( 'post_content', $job_post_id );
												echo '<div class="js-single-job-field' . $key2 . '" >' . $meta_value . '</div>';
											} else {
												// Get the meta key.
												$job_meta_key = jobscience_create_meta_key( $field );
												$meta_value = get_post_meta( $job_post_id, $job_meta_key, true );
												echo '<p class="js-single-job-field' . $key2 . '" >' . $meta_value . '</p>';
											}
										}
									}
								?>
								</div>
							</div>
				<?php
							$section_count++;
						}
					}
				?>
				<div class="clear"></div>
				</div>
		<?php
			}
		?>
			<div id="js-single-job-footer">
				<p id="js-back-job-page"><button id="js-back-job-button" value="">Back to Jobs</button></p>
				<?php $apply_link = get_post_meta( $job_post_id, 'js_job_siteURL', true ); ?>
				<p id="js-single-page-apply"><a href="<?php echo $apply_link ?>" target="_blank" ><button>Apply</button></a></p>
			</div>
			<div class="js-job-clear-float"></div>
		</div>

<?php
		$output1 = ob_get_clean();
	}

	// If the post type is jobscience_job, then create the html.
	$output .= '<div id="js-single-job"><div class="js-single-job-heading"><div class="js-single-job-title">';
	$output .= '<p><strong>' . get_the_title( $job_post_id ) . '</strong></p>';
	$output .= '</div><div class="js-single-job-location">';

	$location_meta = jobscience_create_meta_key( 'ts2__Location__c' );
	$location = get_post_meta( $job_post_id, $location_meta, true );

	// Check the location is not empty.
	if ( $location_meta && ! empty( $location ) ) {
		$output .= '<p>' . $location . '</p>';
	}
	$output .= '</div><div class="js-job-clear-float"></div></div>';
	$output .= '<div class="js-single-job-body"><div class="js-single-job-left">';

	// Get the job function and posted date from meta table.
	$function_meta = jobscience_create_meta_key( 'ts2__Job_Function__c' );
	$function = get_post_meta( $job_post_id, $function_meta, true );
	$posted_meta = jobscience_create_meta_key( 'pubDate' );
	$posted_on = get_post_meta( $job_post_id, $posted_meta, true );

	// Check the function is not empty.
	if ( $function_meta && ! empty( $function ) ) {
		$output .= '<div class="js-single-job-function">';
			$output .= '<p><strong>Function</strong></p>';
			$output .= '<p>' . $function . '</p>';
		$output .= '</div>';
	}

	// Check the posted_on is not empty.
	if ( $posted_meta && ! empty( $posted_on ) ) {
		$output .= '<div class="js-single-job-posted">';
			$output .= '<p><strong>Posted On</strong></p>';
			$output .= '<p>' . $posted_on . '</p>';
		$output .= '</div>';
	}
	$output .= '</div><div class="js-single-job-right">';
	$output .= '<p><strong>Description</strong></p>';

	// Add the job description.
	$content = get_post_field( 'post_content', $job_post_id );
	$output .= '<p>' . $content . '</p>';
	$output .= '</div></div>';
	$output .= '<div id="js-single-job-footer">';
		$output .= '<p id="js-back-job-page"><button id="js-back-job-button" value="">Back to Jobs</button></p>';
		$apply_link = get_post_meta( $job_post_id, 'js_job_siteURL', true );
		$output .= '<p id="js-single-page-apply"><a href="' . $apply_link . '" target="_blank" ><button>Apply</button></a></p>';
	$output .= '</div><div class="js-job-clear-float"></div></div>';

	echo $output1;
	wp_die();
}

add_action( 'wp_ajax_get_single_job', 'jobscience_get_single_job' );
add_action( 'wp_ajax_nopriv_get_single_job', 'jobscience_get_single_job' );

/**
 * Shortcode Ajax call function for the reset pagination section.
 */
function jobscience_pagination_reset_callback() {
	// Collect all data from the $_POST.
	$departments = isset( $_POST['department'] ) ? $_POST['department'] : '';
	$locations = isset( $_POST['location'] ) ? $_POST['location'] : '';
	$function = isset( $_POST['job_function'] ) ? $_POST['job_function'] : '';
	$search = isset( $_POST['search'] ) ? trim( $_POST['search'] ) : '';
	$js_post_per_page = isset( $_POST['js_post_per_page'] ) ? $_POST['js_post_per_page'] : 10;

	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

	// Call the function to get the count of matching job, pass offset and job per page parameters as false and $match parameter as true so that it return total matching job.
	$matched = jobscience_get_matching_job( $departments, $locations, $function, $search, false, false, true );

	// Set the total number of page.
	if ( 0 !== $matched % $js_post_per_page ) {
		$pagination = intval( $matched / $js_post_per_page ) + 1;
	} else {
		$pagination = intval( $matched / $js_post_per_page );
	}

	// Create the return array.
	$return = array(
		'match' => $matched,
		'pagination' => $pagination,
	);
	echo json_encode( $return );
	wp_die();
}

add_action( 'wp_ajax_pagination_reset', 'jobscience_pagination_reset_callback' );
add_action( 'wp_ajax_nopriv_pagination_reset', 'jobscience_pagination_reset_callback' );

// Add plugin activation hook.
register_activation_hook( __FILE__, 'jobscience_activation' );

/**
 * Plugin activation function.
 */
function jobscience_activation() {
	if ( ! wp_next_scheduled( 'jobscience_hourly_soap_call' ) ) {
		// On activation, set a time, frequency and name of an action hook to be scheduled.
		wp_schedule_event( time(), 'hourly', 'jobscience_hourly_soap_call' );
	}
}

add_action( 'jobscience_hourly_soap_call', 'jobscience_get_salesforce_jobs' );

/**
 * On the scheduled action hook, Update all jobs and remove the the unwanted meta value.
 */
function jobscience_get_salesforce_jobs() {
	// Include the function file.
	require_once( JS_PLUGIN_DIR . '/includes/jobscience-functions.php' );

	// Check the plugin is configure or not.
	$plugin_configure = get_option( 'js-organization' );

	$url = get_option( 'js-rss-feed-url' );

	// Check the plugin is configured and the $url in not empty.
	if ( $plugin_configure && $url ) {
		// Call the function to get all jobs from salesforce.
		$js_temp = jobscience_get_salesforce_jobs_rss( $url );
	}
}

// Add plugin deactivation hook.
register_deactivation_hook( __FILE__, 'jobscience_deactivation' );

/**
 * Plugin deactivation function.
 */
function jobscience_deactivation() {
	// On deactivation, remove all functions from the scheduled action hook.
	wp_clear_scheduled_hook( 'jobscience_hourly_soap_call' );

	// Remove all configure data from option table.
	delete_option( 'js-organization' );
}
