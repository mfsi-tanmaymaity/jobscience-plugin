/**
 * Name			: custom.js
 * Description	: This file is for all jQuery coding for this plugin. This file is only for Dashboard, not for front end.
 * Author		: JobScience
 * Date			: 04/29/2015 (MM/DD/YYYY)
 * package		: Job Manager JobScience plugin
 **/

jQuery( document ).ready( function() {
	// Shortcode Generation.
	jQuery( "select.js-shortcode-select-field", "#js-shortcode-generator" ).change( function() {
		var temp = jQuery( this ).find( "option:selected" ).map( function() { return jQuery( this ).val() } ).get().join( ' , ' );

		if ( temp.indexOf( 'all' ) >= 0 ) {
			temp = '';
		}
		jQuery( this ).next().val( temp );

		// Get the selected department and location value.
		var departments = jQuery( "#js-shortcode-department" ).val();
		var locations = jQuery( "#js-shortcode-location" ).val();
		var functions = jQuery( "#js-shortcode-function" ).val();
		var result = '[jobscience';

		if ( undefined !== departments && departments != '' ) {
			result += ' department="' + departments + '"';
		}

		if ( undefined !== locations && locations != '' ) {
			result += ' location="' + locations + '"';
		}

		if ( undefined !== functions && functions != '' ) {
			result += ' function="' + functions + '"';
		}

		result += ']';
		// Change the shortcode result value.
		jQuery( "#js-shortcode-result-field" ).val( result );
	});

	// Add or remove disabled attribute on order filed in Plugin Styling page.
	jQuery( ".js-styling-fields", "#js-plugin-styling-table" ).click(function(){
		var order = jQuery( this ).parents( "tr" ).find( ".js-styling-order,.js-field-heading" );

		if ( jQuery( this ).is( ":checked" ) ) {
			order.prop( "disabled", false );
			if ( jQuery( this ).val() == 'content' ) {
				jQuery( "#js-content-count" ).removeClass( "js-hidden" );
			}
		} else {
			order.val( '' ).prop( "disabled", true ).removeClass( 'js-styling-error' );
			if ( jQuery( this ).val() == 'content' ) {
				jQuery( "#js-content-count" ).addClass( "js-hidden" );
				jQuery( "#js-content-count-field" ).val( '' );
			}
		}
	});

	// On submit check the order field is empty or not, if the order field is empty and the check-box is checked, then show error.
	jQuery( "#js-styling-submit" ).click( function(e){
		var error = false;

		// Run a loop on all check-boxes.
		jQuery( ".js-styling-fields", "#js-plugin-styling-table" ).each(function(){
			if ( jQuery( this ).is( ':checked' ) ) {
				var order_obj = jQuery( this ).parents( "tr" ).find( ".js-styling-order" );
				var heading_obj = jQuery( this ).parents( "tr" ).find( ".js-field-heading" );

				// Check the order field in empty or not.
				if (order_obj.val() == '') {
					e.preventDefault();
					// Add the error class to change the css on the field.
					order_obj.addClass( 'js-styling-error' );
					error = true;
				} else {
					// Remove the error class.
					order_obj.removeClass( 'js-styling-error' );
				}

				// Check the Field Heading field is empty or not.
				if (heading_obj.val() == '') {
					e.preventDefault();
					// Add the error class to change the css on the field.
					heading_obj.addClass( 'js-styling-error' );
					error = true;
				} else {
					// Remove the error class.
					heading_obj.removeClass( 'js-styling-error' );
				}
			}
		});
		if ( error ) {
			alert( "Insert all order and heading values." )
		}
	});

	// Plugin Configuration page RSS Feed form validation check.
	jQuery( "#submit-rss" ).click( function( e ){
		var rss_field = jQuery( "#js-rss-feed" );

		if ( jQuery.trim( rss_field.val() ) == '' ) {
			e.preventDefault();
			rss_field.addClass( "js-styling-error" ).val( '' );
			alert( 'Please insert the RSS Feed URl.' );
		} else {
			rss_field.removeClass( "js-styling-error" );
		}
	});

	// Number field validation.
	jQuery( "body" ).on( 'keyup', ".js-number-field", function(){
		var value = jQuery( this ).val();
		value = value.replace( /[^0-9]+/g, '' );
		jQuery( this ).val( value );
	});

	// Prevent to enter more then 100 for Single Job Template width field.
	jQuery( "#js-create-template" ).on( 'keyup', ".js-section-width", function(){
		var value = jQuery( this ).val();
		if( value > 100 ) {
			value = 100;
		}
		jQuery( this ).val( value );
	});

	// Add new row in the RSS Feed Tag names table.
	jQuery( "#js-rss-new-row" ).click( function( e ) {
		e.preventDefault();
		jQuery( "table#js-rss-feed-tag tr:eq(1)" ).clone().find( "input" ).val( '' ).end().appendTo( "table#js-rss-feed-tag tbody" );

		var a = jQuery( "table#js-rss-feed-tag tr:nth-child(2)" ).clone().find( "input" ).val( '' );
	});

	// Validation for the Rss tag form.
	jQuery( "form#js-rss-fields-list" ).on( 'click', "#submit-rss-feed-tag", function( e ) {
		var rss_tag_error = false;
		var custom_name_error = false;
		var custom_name_array = [];
		jQuery( "table#js-rss-feed-tag tr" ).each( function() {
			var rss_tag = jQuery.trim( jQuery( this ).find( ".js-rss-tag" ).val() );
			var custom_name = jQuery.trim( jQuery( this ).find( ".js-custom-name" ).val() );

			// Check condition and add error class.
			if ( ( rss_tag != '' && custom_name == '' ) || ( rss_tag == '' && custom_name != '' ) ) {
				jQuery( this ).find( "input" ).addClass( "js-styling-error" );
				rss_tag_error = true;
			} else {
				// Check condition for the duplicate value in Custom Name.
				if ( '' === custom_name ) {
					jQuery( this ).find( "input" ).removeClass( "js-styling-error" );
				} else if ( -1 === jQuery.inArray( custom_name, custom_name_array ) ) {
					custom_name_array.push( custom_name );
					jQuery( this ).find( "input" ).removeClass( "js-styling-error" );
				} else {
					custom_name_error = true;
					jQuery( this ).find( "input.js-custom-name" ).addClass( "js-styling-error" );
				}
			}


		});

		// Check the error and alert the error message.
		if ( rss_tag_error && custom_name_error ) {
			e.preventDefault();
			alert( 'Please insert all fields value in each row.\n Please insert a unique Custom Name.' );
		} else if ( rss_tag_error ) {
			e.preventDefault();
			alert( 'Please insert all fields value in each row.' );
		} else if ( custom_name_error ) {
			e.preventDefault();
			alert( 'Please insert a unique Custom Name.' );
		}
	});

	// On blur auto resize the div on the Template.
	jQuery( ".js-section-width" ).blur( function() {
		var width = jQuery(this).val() + '%';
		jQuery(this).parents( ".js-template-column" ).css( 'width', width );
	});

	// Add field functionality on the single job template after select any field form the select box.
	jQuery( ".js-template-add-field", "#js-create-template" ).change( function() {
		if( '' !== jQuery(this).val() ) {
			// Create new <p> tag for the field.
			var new_field = '<div class="js-template-job-field"><div>';

			// Get the index of the section, add +1 because it start from 0.
			var index = jQuery(".js-template-add-field", "#js-create-template").index(jQuery(this)) + 1;
			// Add one hidden field with the selected field data.
			new_field += '<input type="hidden" name="js_section[' + index + '][fields][]" value="' + jQuery(this).val() + '" />';
			new_field += '<div class="js-template-field-title"><strong>' + jQuery("option:selected", jQuery(this)).text() + '</strong></div>';
			new_field += '<div class="js-template-field-delete"><img src="../wp-content/plugins/job-manager-jobscience/images/delete.png" class="js-template-field-delete-img" /></div></div>';
			new_field += '<div class="js-template-field-style"><a class="js-templete-edit-style">Edit Style</a>'
			new_field += '<div class="js-template-style-section">';
			new_field += '<div clas="js-template-field-font-size">Font Size(px): <input type="text" size="2" class="js-number-field" name="js_section[' + index + '][font_size][]" value="" /></div>';
			new_field += '<div clas="js-template-field-color-section">Color: <input type="text" size="2" class="js-template-field-color" name="js_section[' + index + '][color][]" /></div>';
			new_field += '<div clas="js-template-bold">Text Format:';
			new_field += '<select name="js_section[' + index + '][text_format][]" class="js-template-text-format">';
			new_field += '<option value="">Default</option>';
			new_field += '<option value="bold">Bold</option>';
			new_field += '<option value="italic">Italic</option>';
			new_field += '</select></div>';
			new_field += '<div class="clear"></div></div>';
			new_field += '</div>';
			jQuery(this).parent('p').prev("div.js-template-added-fields").append(new_field);
			jQuery(this).val('');
			jQuery('.js-template-field-color').wpColorPicker();
		}
	});

	// Delete fields from Single Page templete.
	jQuery("#js-create-template").on("click", ".js-template-field-delete-img", function(){
		jQuery(this).parents('.js-template-job-field').remove();
	});

	// Open/hide/add the "Edit Style" section.
	jQuery("#js-create-template").on('click', ".js-templete-edit-style", function(e){
		e.preventDefault();
		jQuery(this).next(".js-template-style-section").toggle();
		jQuery(this).toggleClass('div_show');
	});

	// Make the title and content field readonly on the job edit page.
	jQuery( 'input#title', '.post-type-jobscience_job' ).prop( 'readonly', 'readonly' );
	jQuery( 'textarea#content', '.post-type-jobscience_job' ).prop( 'readonly', 'readonly' );

	jQuery('.js-template-field-color').wpColorPicker();

	jQuery(".js-template-added-fields").sortable({
		item: ".js-template-job-field",
		opacity: 0.6,
		cursor: 'move',
		axis: 'y'
	});
});
