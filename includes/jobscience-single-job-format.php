<?php
/**
 * Name			: jobscience-single-job-format.php
 * Description	: This file contain all available format for Single job's template.
 * Author		: JobScience
 * Date			: 09/08/2015 (MM/DD/YYYY)
 * @package		: Job Manager JobScience plugin
 **/

/**
 * Return the format of the template.
 * @param int $format Number of format.
 */
function jobscience_get_template_formate( $format ) {
	switch ( $format ) {
		case 2:
			$format_array = array( 1 => array( 1 => 100 ), 2 => array( 1 => 100 ) );
			break;

		case 3:
			$format_array = array( 1 => array( 1 => 50, 2 => 50 ) );
			break;

		case 4:
			$format_array = array( 1 => array( 1 => 50, 2 => 50 ), 2 => array( 1 => 50, 2 => 50 ) );
			break;

		case 5:
			$format_array = array( 1 => array( 1 => 100 ), 2 => array( 1 => 50, 2 => 50 ) );
			break;

		case 6:
			$format_array = array( 1 => array( 1 => 100 ), 2 => array( 1 => 50, 2 => 50 ), 3 => array( 1 => 100 ) );
			break;

		default:
			// Default will work for "case 1" also.
			$format_array = array( 1 => array( 1 => 100 ) );
			break;
	}
	return $format_array;
}
