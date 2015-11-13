/**
 * Name			: jobscience-ajax.js
 * Description	: This file is for all jQuery coding for the ajax call for this plugin.
 * Author		: JobScience
 * Date			: 05/08/2015 (MM/DD/YYYY)
 * package		: Job Manager JobScience plugin
 **/

jQuery( document ).ready( function() {
	// On page load call the function to calculate the number of column and set the width of the columns.
	jobscience_set_width();
	// Call the function to replace the image links from Job Description.
	jobscience_image_link_replace();

	// Pagination Ajax call.
	jQuery( ".js-pagination" ).on( 'click', "button.js-pagination-item", function(e){
		e.preventDefault();

		// Check the pagination is current or not.
		if ( ! jQuery( this ).hasClass( "js-current" ) ) {
			// Get the page no from the id.
			var page_no = jQuery( this ).val();

			var obj = jQuery( this ).parents( ".jobscience-shortcode-body" );
			// Call  the function, Pass 0 for pagination call.
			jobscience_pagination_callback( page_no, obj, 0 );
		}
	});

	// Search Ajax call.
	jQuery( ".js-search-submit-button", ".jobscience-shortcode-body" ).click( function(e){
		e.preventDefault();
		// Store the current shortcode object.
		var obj = jQuery( this ).parents( ".jobscience-shortcode-body" );

		// Call the function for the Ajax call. Pass 1st 1 for page number and last 1 for the search call.
		jobscience_pagination_callback( 1, obj, 1 );
	});

	// Ajax call to get all detail of any job.
	jQuery( "#jobscience-shortcode" ).on( 'click', ".js-job-title a", function( e ){
		e.preventDefault();
		var obj = jQuery( this ).parents( ".js-job-title" );
		// Get the post id of the job.
		var post_id = obj.parents( ".js-job-detail" ).find( ".js-post-id" ).val();

		// Create the data array for the ajax call.
		var data = {
			action: 'get_single_job',
			post_id: post_id
		}

		// Call the ajax loader.
		jQuery( '.js-page-loader' ).show();

		// Call the ajax.
		jQuery.post( the_ajax_script.ajaxurl, data, function( response ){
			jQuery( ".jobscience-result" ).html( response );
			// Hide the pagination section.
			jQuery( ".jobscience-pagination-section, .jobscience-result-heading" ).hide();

			// Call the function to replace the image links from Job Description.
			jobscience_image_link_replace();
			// Remove the ajax loader.
			jQuery( '.js-page-loader' ).hide();
		} );
	});

	// Back to Job Page functionality.
	jQuery( "#jobscience-shortcode" ).on( 'click', "#js-back-job-button", function( e ){
		e.preventDefault();

		var obj = jQuery( this ).parents( ".jobscience-shortcode-body" );
		// Get the pagination no.
		var page_no = jQuery( ".js-current", ".js-pagination", obj ).val();
		if ( ! page_no ) {
			page_no = 1;
		}
		// Show the pagination and Heading section.
		jQuery( ".jobscience-pagination-section, .jobscience-result-heading" ).show();
		// Scroll to top of the page.
		jQuery( "html, body" ).animate( { scrollTop: 0 }, "slow" );
		// Call the function, pass 0 so that it is same as pagination.
		jobscience_pagination_callback( page_no, obj, 0 );
	});

	// Add the page loader div in the starting of body.
	jQuery( '<div class="js-page-loader"></div>' ).insertBefore( 'body' );
});

// Calculate the total column in the job listing page and set the width of each column.
function jobscience_set_width(){
	var count = jQuery( ".horizontally.js-job-detail", ".js-job-row-1" ).length;
	var width = Math.floor( 100 / count );
	jQuery( ".horizontally.js-job-detail", ".js-job" ).css( "width", width + '%' );
}

// If anyone use the upload image functionality to add any images with any job desccription on Salesforce, then the images will not display from WordPress.
// To solve the problem we need to replace the domain from the /servlet/rtaImage image link with the domain of the job apply link.
function jobscience_image_link_replace() {
	if( jQuery(".job-single-description").find('img').length > 0 ) {
		jQuery(".job-single-description").find('img').each(function(){
			var src = jQuery(this).attr('src');
			var index = src.indexOf('/servlet/rtaImage');
			if(index > 0) {
				src = src.substr(index);
				var href = jQuery('#js-single-page-apply a').attr('href');
				href = href.substr(0, href.indexOf('ts2__Register?jobId') - 1);
				jQuery(this).attr('src',(href + src))
			} else if ( 0 == src.indexOf('image/') && src.indexOf( 'base64') > 0 ) {
				src = 'data:' + src;
				jQuery(this).attr('src', src);
			};
    	});
	}
}

// Pagination Ajax call function.
function jobscience_pagination_callback( page_no, obj, call_type ) {
	// Collect the department and location of the shortcode.
	var js_post_per_page = obj.find( ".js-post-per-page" ).val();
	var offset = ( page_no - 1 ) * js_post_per_page;
	var search = obj.find( ".js-search-text-field" ).val();
	var position = obj.find( ".js-filed-postition" ).val();
	var search_nonce = jQuery( "#jobscience-search-nonce" ).val();
	var picklist_attribute = {};

	// // Create an array with the attribute value of all picklist.
	jQuery( ".jobscience-picklist-filter", obj ).each( function() {
		var picklist_id = jQuery( this ).attr( 'id' );
		// Remove 'js-search-' from the starting. length of 'js-search-' is 10.
		picklist_id = picklist_id.substring( 10 );
		var picklist_value= jQuery( this ).children('select').val();
		picklist_attribute[picklist_id] = picklist_value;
	});

	// Create the data variable to pass all value in the Ajax.
	var data = {
		action: 'shortcode_pagination_search',
		search_nonce: search_nonce,
		search: search,
		js_post_per_page: js_post_per_page,
		offset: offset,
		position: position,
		picklist_attribute: picklist_attribute,
	}

	// Call the ajax loader.
	jQuery( '.js-page-loader' ).show();

	// Call the Ajax.
	jQuery.post( the_ajax_script.ajaxurl, data, function( response ) {
		obj.find( ".jobscience-result" ).html( response.html_data );

		// Call the function to calculate the number of column and set the width of the columns.
		jobscience_set_width();
		// Call the function to replace the image links from Job Description.
		jobscience_image_link_replace();
		obj.find( ".js-pagination" ).html( response.pagination );
		if ( 1 === call_type ) {
			jQuery( ".js-matching-job-count" ).text( ' | ' + response.match + '  Matches' );
			// Show the pagination and Heading section.
			jQuery( ".jobscience-pagination-section, .jobscience-result-heading" ).show();
		}
		// Remove the ajax loader.
		jQuery( '.js-page-loader' ).hide();
	}, "json" );
}
