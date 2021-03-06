<?php
/**
 * Name			: jobscience-functions.php
 * Description	: This file contain all functions of this plugin.
 * Author		: JobScience
 * Date			: 04/29/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

// Prevent direct access.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Function to call the SOAP API to check the configuration.
 * @param string $username Username of salesforce.
 * @param string $password Password of salesforce.
 * @param string $security Security Token the salesforce.
 * @return Boolean true for success or string with error message for fail.
 */
function jobscience_salesforce_configuration( $username, $password, $security ) {
	require_once( JS_PLUGIN_DIR . '/soapclient/SforcePartnerClient.php' );

	// Exception handling.
	try {
		// Create a new connection.
		$mySforceConnection = new SforcePartnerClient();
		$mySforceConnection->createConnection( JS_PLUGIN_DIR . '/soapclient/partner.wsdl.xml' );
		$login = $mySforceConnection->login( $username, $password.$security );
		$organization_id = $login->userInfo->organizationId;
		update_option( 'js-organization', $organization_id, false );

		// Create the random token.
		$token = wp_generate_password( 10, false );
		update_option( 'js-outbound-token', $token, false );

		$result = true;
	} catch ( Exception $e ) {
		$result = $e->faultstring;
	}
	return $result;
}

/**
 * Hook Function to reset the feedcache time.
 * @param int $seconds Second.
 * @return int cache time.
 */
function jobscience_reset_feed_cache() {
	// Change the default feed cache recreation period.
	return 0;
}

/**
 * SOAP Call to get all active jobs from salesforce.
 * @param string $url URL for the RSS Feed.
 * @param int    $new_job_id job id.
 * @return string on fail for error message or array on success for total number of job.
 */
function jobscience_get_salesforce_jobs_rss( $url, $new_job_id = null ) {
	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );
	// Check the variable is array and not empty.
	if ( ! is_array( $rss_tag ) || empty( $rss_tag ) ) {
		return 'tag_absent';
	}

	// Increase the memory limit and execution time for the specific script.
	ini_set( 'memory_limit','1024M' );
	ini_set( 'max_execution_time', -1 );

	// Exception handling.
	try {
		// Includes Feed file.
		include_once( ABSPATH . WPINC . '/feed.php' );

		// Add the filter hook to reset the feedcache time.
		add_filter( 'wp_feed_cache_transient_lifetime' , 'jobscience_reset_feed_cache' );

		// Get a SimplePie feed object from the specified feed source.
		$feed = fetch_feed( $url );

		// Remove the hook.
		remove_filter( 'wp_feed_cache_transient_lifetime' , 'jobscience_reset_feed_cache' );

		$maxitems = 0;
		// Checks that the object is created correctly.
		if ( ! is_wp_error( $feed ) ) {
			// Figure out how many total items there are.
			$maxitems = $feed->get_item_quantity();
			// Build an array of all the items, starting with element 0 (first element).
			$rss_items = $feed->get_items( 0, $maxitems );

			// Run a loop to get all job's details and insert them in a associative array.
			$jobs = array();
			if ( is_array( $rss_items ) && 0 !== $maxitems ) {
				foreach ( $rss_items as $item ) {
					$link = $item->get_permalink();
					// Get the job id from the job link.
					$link_parts = parse_url( $link, PHP_URL_QUERY );
					parse_str( $link_parts, $link_arg );
					$job_id = $link_arg['jobId'];

					// If the $new_job_id is null then copy all job else copy the job which job id is $new_job_id.
					if (  is_null( $new_job_id ) || $new_job_id == $job_id ) {
						// Create a temporary array with all data of any job.
						$temp = array();
						$temp['title'] = $item->get_title();
						$temp['title'] = htmlspecialchars_decode( $temp['title'], ENT_QUOTES );
						$temp['link'] = $link;
						$temp['id'] = $job_id;

						// Run a loop for the rss tag.
						foreach ( $rss_tag as $tag ) {
							$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
							// The ts2__Date_Posted__c and pubDate are same in RSS Feed.
							if ( 'ts2__Date_Posted__c' == $tag['tag'] ) {
								$tag_data = $item->get_item_tags( '', 'pubDate' );
							} else {
								$tag_data = $item->get_item_tags( '', $tag['tag'] );
							}

							$field_value = $tag_data[0]['data'];
							// Run a switch case.
							switch ( $tag['rss_field_type'] ) {
								case 'int':
									$field_value = ! empty( $field_value ) ? intval( $field_value ) : '';
									break;

								case 'salary':
									$field_value = is_numeric( $field_value ) && ! empty( $field_value ) ? number_format( $field_value, 2, '.', ',' ) : '';
									break;

								case 'date':
									$field_value = ! empty( $field_value ) && strtotime( $field_value ) ? date( get_option( 'date_format' ), strtotime( $field_value ) ) : '';
									break;
							}

							$temp[ $custom_name ] = $field_value;
						}

						$tag_description = $item->get_item_tags( '', 'description' );
						$content = $tag_description[0]['data'];
						$content = htmlspecialchars_decode( $content, ENT_QUOTES );
						// Create the patterns and replacement array.
						$patterns = array();
						$patterns[0] = '/class="(.*?)"/';
						$patterns[1] = '/id="(.*?)"/';
						$replacements = array();
						$replacements[0] = '';
						$replacements[1] = '';

						// Remove the inline css and the class from the description.
						$content = preg_replace( $patterns, $replacements, $content );
						$temp['description'] = $content;
						array_push( $jobs, $temp );
					}
				}
				// Update the option table.
				update_option( 'js-rss-feed-url', $url, false );
			}
		} else {
			return 'invalid';
		}

		global $wpdb;
		$wpdb->query( 'SET autocommit = 0;' );

		// If the $new_job_idis null, so the function is not calling from outbound messase.
		if ( is_null( $new_job_id ) ) {
			// Collect the post id for all exist post which are "jobscience_job" post type.
			$all_job_post_id = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'jobscience_job'" );
		}

		// Run a loop on the $queryResult and create new post.
		$post_count = 0;
		foreach ( $jobs as $record ) {
			// Check the ID is already present or not.
			$job_id = $record['id'];
			$old_job = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'js_job_id' AND meta_value = %s", $job_id ) );

			// If above query return null/empty, then that is new post, so create a new custom post type.
			if ( ! $old_job ) {
				// Create the default post array.
				$post = array(
					'post_title' => $record['title'],
					'post_name' => strtolower( str_replace( ' ', '-', $record['title'] ) ),
					'post_content' => $record['description'],
					'post_type' => 'jobscience_job',
					'comment_status' => 'closed',
					'ping_status' => 'closed',
					'post_parent'    => 0,
					'post_password'  => '',
					'menu_order'     => 0,
					'post_excerpt'   => '',
					'post_status' => 'publish',
				);

				// Create new custom post.
				$post_id = wp_insert_post( $post );
			} else {
				// If the job id present in meta table then no need to create new post,just set the $post_id with the existing job's post id and update the post.
				$post_id = $old_job;
				// Update post.
				$old_post = array(
					'ID'           => $post_id,
					'post_title'   => $record['title'],
					'post_name' => strtolower( str_replace( ' ', '-', $record['title'] ) ),
					'post_content' => $record['description'],
					'post_status' => 'publish',
				);

				// Update the post into the database.
				wp_update_post( $old_post );

				// Remove the Post ID from the array.
				if ( isset( $all_job_post_id ) && is_array( $all_job_post_id ) && ( $array_key = array_search( $post_id, $all_job_post_id ) ) !== false ) {
					unset( $all_job_post_id[ $array_key ] );
				}
			}

			// Run a loop for the rss tag.
			foreach ( $rss_tag as $tag ) {
				$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
				update_post_meta( $post_id, $custom_name, $record[ $custom_name ] );
			}
			// Insert all meta field.
			update_post_meta( $post_id, 'js_job_id', $record['id'] );
			update_post_meta( $post_id, 'js_job_siteURL', $record['link'] );
			// Increate the post count.
			$post_count++;
		}

		// Delete all post which are not present on the RSS Feed.
		if ( isset( $all_job_post_id ) && is_array( $all_job_post_id ) && ! empty( $all_job_post_id ) ) {
			foreach ( $all_job_post_id as $value ) {
				wp_delete_post( $value, true );
			}
		}

		$wpdb->query( 'COMMIT;' );
		// Return total jobs.
		return $post_count;
	} catch ( Exception $e ) {
		return $e;
	}
}

/**
 * Function get the Custom name from the RSS Feed tag name.
 * @param string $rss_feed_tag RSS Feed Tag name.
 * @return boolean false on fail or string on success for the Custom name.
 */
function jobscience_get_custom_name( $rss_feed_tag ) {
	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );

	// Check that the $rss_Feed_tag present in the array.
	if ( is_array( $rss_tag ) ) {
		foreach ( $rss_tag as $value ) {
			if ( is_array( $value ) && in_array( $rss_feed_tag, $value ) ) {
				return $value['custom_name'];
			}
		}
	}
	return false;
}

/**
 * Get all departments/locations/job-functions from the postmeta table.
 * @param string $meta_key The Meta Key.
 * @return array all meta value of the meta key.
 */
function jobscience_get_dept_loc_function( $meta_key ) {
	global $wpdb;
	$sql = "SELECT DISTINCT
				meta_value
			FROM
				$wpdb->postmeta meta_table
			INNER JOIN
				$wpdb->posts post_table
				ON post_table.ID = meta_table.post_id
			WHERE
				post_table.post_status = 'publish'
			AND
				post_table.post_type = 'jobscience_job'
			AND
				meta_table.meta_key = '%s'
			AND
				meta_table.meta_value <> ''";
	$result = $wpdb->get_col( $wpdb->prepare( $sql, $meta_key ) );
	return $result;
}

/**
 * Get the total number of open position.
 * @return int Number of job.
 */
function jobscience_get_job_count() {
	global $wpdb;
	$count = $wpdb->get_var(
		"SELECT
			COUNT(ID)
		FROM
			$wpdb->posts
		WHERE
			post_type = 'jobscience_job'
			AND
			post_status = 'publish' "
	);
	return $count;
}

/**
 * Function Create the meta key name from the RSS Feed tag name.
 * @param string $rss_feed_tag RSS Feed Tag name.
 * @return boolean false on fail or string meta key on success.
 */
function jobscience_create_meta_key( $rss_feed_tag ) {
	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );

	// Check that the $rss_Feed_tag present in the array.
	if ( is_array( $rss_tag ) ) {
		foreach ( $rss_tag as $value ) {
			if ( is_array( $value ) && in_array( $rss_feed_tag, $value ) ) {
				$return_key = 'js_job_' . strtolower( str_replace( ' ', '_', $value['custom_name'] ) );
				return $return_key;
			}
		}
	}
	return false;
}

/**
 * Function for get the matching job or get the count of matching job.
 * @param array  $picklist_attribute All Picklist Value.
 * @param string $search Keyword for search.
 * @param string $offset Offset for the pagination.
 * @param int    $job_per_page job per page.
 * @param int    $match Number of matched jobs.
 * @return int matching job count if $match is true or array matching jobs if $match is false.
 */
function jobscience_get_matching_job( $picklist_attribute, $search, $offset, $job_per_page, $match ) {
	global $wpdb;
	$join = '';
	$where = " WHERE
					post_type = 'jobscience_job'
					AND
					post_status = 'publish' ";
	$merge_array = array();
	$search_array = array();


	if ( is_array( $picklist_attribute ) && !empty( $picklist_attribute ) ) {
		$loop_count = 1;
		foreach ( $picklist_attribute as $picklist_tag => $picklist_field ) {
			// Call the function to create the meta key.
			$picklist_meta_key = jobscience_create_meta_key( $picklist_tag );

			// Create the alias name of the meta table.
			$meta_alias = 'meta_table_' . $loop_count;
			$loop_count++;

			$picklist_value_array = array();
			if ( false != trim( $picklist_field ) ) {
				$picklist_value_array = explode( '  ,  ', $picklist_field );

				$join .= ' INNER JOIN
						wp_postmeta ' . $meta_alias . '
						ON post.ID = ' . $meta_alias . '.post_id ';

				$where .= " AND
						" . $meta_alias . ".meta_key = '" . $picklist_meta_key . "'
						AND
						" . $meta_alias . ".meta_value IN (" . implode( ', ', array_fill( 0, count( $picklist_value_array ), '%s' ) ) . ') ';

				$merge_array = array_merge( $merge_array, $picklist_value_array );
			}
		}
	}

	// If the $search is not empty then join meta table for the search.
	if ( ! empty( $search ) ) {
		// Escape $search for use in a LIKE statement.
		$search = $wpdb->esc_like( $search );
		$search = '%' . $search . '%';
		$search_array = array( $search );

		$where .= ' AND
					( post.post_title LIKE %s
					OR
					post.post_content LIKE %s )';
	}

	// Passing $search_array twice as we are searching same text for two field, 1 for job title and other for job content.
	$merge_array = array_merge( $merge_array, $search_array, $search_array );

	// Create the full SQL.
	// If $match is true then select the total jobs.
	if ( $match ) {
		$sql = 'SELECT
				COUNT( DISTINCT( post.ID ) )
			FROM
				wp_posts post ';
	} else {
		$sql = 'SELECT
					post.ID, post.post_title
				FROM
					wp_posts post ';
	}
	$sql .= $join;
	$sql .= $where;

	// If $search is not null and $match is false then add Group By in the SQL.
	if ( ! empty( $search ) && ! $match ) {
		$sql .= ' GROUP BY post.ID ';
	}
	// If $match is true then no need to order by and Limit.
	if ( ! $match ) {
		$sql .= ' ORDER BY
					post_date DESC
				LIMIT ' . $offset . ', ' . $job_per_page;
	}

	// Call $wpdb->prepare and pass the values of the array as separate arguments.
	if ( count( $merge_array ) > 0 ) {
		$query = call_user_func_array( array( $wpdb, 'prepare' ), array_merge( array( $sql ), $merge_array ) );
	} else {
		$query = $sql;
	}

	if ( $match ) {
		$results = $wpdb->get_var( $query );
	} else {
		$results = $wpdb->get_results( $query );
	}
	return $results;
}

/**
 * Function to get all meta key of the custom post type (job).
 * It will return all present meta keys for any job.
 * @return array All meta key of the custom post type.
 */
function jobscience_get_all_meta_key() {
	global $wpdb;

	// Create the SQL.
	$sql = "SELECT
				DISTINCT( meta.meta_key )
			FROM
				$wpdb->posts post
			INNER JOIN
				$wpdb->postmeta meta
				ON post.ID = meta.post_id
			WHERE
				post.post_type = 'jobscience_job'
			AND
				meta.meta_key LIKE 'js_job_%'";

	// Run the query.
	$result = $wpdb->get_col( $sql );
	return $result;
}

/**
 * Function to get all meta key of the custom post type (job).
 * It will return the mets keys which are set by Admin, from the RSS Tag Form in Plugin Configure page.
 * @return boolean false on fail or array all meta key set by admin on success.
 */
function jobscience_get_meta_key() {
	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );
	// Check the variable is array and not empty.
	if ( ! is_array( $rss_tag ) || empty( $rss_tag ) ) {
		return false;
	} else {
		$return_array = array();
		// Run a loop for the rss tag.
		foreach ( $rss_tag as $tag ) {
			$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
			array_push( $return_array, $custom_name );
		}
		return $return_array;
	}
}

/**
 * This function will return the rss tag details (rss tag, custom name, field type) of any field.
 * @param text $field_meta_key Meta Key of the field.
 * @return array rss tag detail.
 */
function jobscience_get_rss_tag_details( $field_meta_key ) {
	// Get the rss tags name from option table.
	$rss_tag = get_option( 'js-rss-tag' );

	if ( is_array( $rss_tag ) && ! empty( $rss_tag ) ) {
		foreach ( $rss_tag as $tag ) {
			$custom_name = 'js_job_' . strtolower( str_replace( ' ', '_', $tag['custom_name'] ) );
			if ( $custom_name == $field_meta_key ) {
				return $tag;
			}
		}
	}
	return $field_meta_key;
}

/**
 * Function to update the meta table.
 * @param int    $post_id Post Id.
 * @param string $meta_key The meta key.
 * @param string $meta_value The meta value.
 */
function jobscience_update_job_meta( $post_id, $meta_key, $meta_value ) {
	$meta_value = trim( $meta_value );
	// Update post meta if not empty.
	if ( ! empty( $meta_value ) ) {
		update_post_meta( $post_id, $meta_key, $meta_value );
	} else {
		// Delete post meta in empty.
		delete_post_meta( $post_id, $meta_key );
	}
}

/**
 * Get the post ID from the meta value.
 * @param string $meta_value The meta value.
 * @param string $meta_key The meta key.
 * @return int Post ID.
 */
function jobscience_get_post_id( $meta_value, $meta_key ) {
	global $wpdb;
	// Make the SQL Query.
	$sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s";
	$job_id = $wpdb->get_var( $wpdb->prepare( $sql, $meta_key, $meta_value ) );
	return $job_id;
}

/**
 * Returned response to SFDC and to stop resend.
 * @param boolean $returned_msg Send true for the outbound message.
 * @return string xlm message for outbound message.
 */
function jobscience_outbound_respond( $returned_msg ) {
	return '<?xml version="1.0" encoding="UTF-8"?>
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
         <soapenv:Body>
         <notifications xmlns="http://soap.sforce.com/2005/09/outbound">
         <Ack>' . $returned_msg . '</Ack>
         </notifications>
         </soapenv:Body>
         </soapenv:Envelope>';
}

/**
 * Function to check the positing integer number.
 * @param string $number String which need to check for number or not.
 * @param int    $return_value Return value if not number.
 * @return int number.
 */
function jobscience_check_number( $number, $return_value ) {
	// Check numeric or not.
	if ( ! is_numeric( $number ) ) {
		return $return_value;
	}

	// Check for positive number.
	if ( $number <= 0 ) {
		return $return_value;
	}

	// Else return the number.
	return intval( $number );
}

/**
 * Function to reset the pagination section.
 * @param int $matched matched job.
 * @param int $job_per_page Job per page.
 * @param int $current_page current pagination number.
 * @return string html for the pagination.
 */
function jobscience_pagination_create( $matched, $job_per_page, $current_page ) {
	// Calculate the total number number on page.
	if ( 0 !== $matched % $job_per_page ) {
		$pagination = intval( $matched / $job_per_page ) + 1;
	} else {
		$pagination = intval( $matched / $job_per_page );
	}

	// Start the internal buffer to save the html in the buffer.
	ob_start();

	// Create the First and Prev pagination.
	if ( 1 !== $current_page && 1 !== $pagination ) {
	?>
		<button value="1" class="js-page-1st js-pagination-item">&lt;&lt;First</button>
		<button value="<?php echo $current_page - 1; ?>" class="js-page-prev js-pagination-item">&lt;Prev</button>
	<?php
	}

	// Start pagination.
	if ( 1 === $pagination ) {
		// Craete only one pagination item and make it hidden.
	?>
		<button value="1" class="js-page-1 js-pagination-item js-current js-hidden">1</button>
	<?php
	} else {
		// Set the startpage and endpage number.
		if ( $pagination <= 10 ) {
			$start_page = 1;
			$end_page = $pagination;
		} else if ( $current_page <= 5 ) {
			$start_page = 1;
			$end_page = 10;
		} elseif ( ( $pagination - $current_page ) < 4 ) {
			$end_page = $pagination;
			$start_page = $pagination - 9; // $pagination - 10 + 1.
		} else {
			$start_page = $current_page - 5;
			$end_page = $current_page + 4;
		}

		// Add "..." before starting page number.
		if ( 1 !== $start_page ) {
		?>
			<button class="js-pagination-more">...</button>
		<?php
		}
		for ( $i = $start_page; $i <= $end_page ; $i++ ) {
		?>
			<button value="<?php echo $i; ?>" class="js-page-<?php echo $i; ?> js-pagination-item <?php if ( $current_page === $i ) { echo 'js-current'; }?>"><?php echo $i ?></button>
	<?php
		}

		// Add "..." after ending page number.
		if ( $pagination !== $end_page ) {
		?>
			<button class="js-pagination-more">...</button>
		<?php
		}
	}

	// Create the Last and Next pagination.
	if ( $current_page !== $pagination ) {
	?>
		<button value="<?php echo $current_page + 1; ?>" class="js-page-next js-pagination-item">Next&gt;</button>
		<button value="<?php echo $pagination; ?>" class="js-page-last js-pagination-item">Last&gt;&gt;</button>
	<?php
	}

	return ob_get_clean();
}
