 jQuery( document ).ready( function() {

	jQuery(function() {

		// Handle exporting of settings to JSON.
		jQuery( 'body' ).on( 'click', '[data-export-wsal-settings]', function ( e ) {
			e.preventDefault();
			var ourButton  = jQuery( this );
			var nonce      = ourButton.attr( 'data-nonce' );
		
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				async: true,
				data: {
					action: 'wsal_export_settings',
					nonce: nonce,
				},
				success: function ( result ) {
					// Convert JSON Array to string.
					var json = JSON.stringify( result.data );
					var blob = new Blob([json]);
					var link = document.createElement('a');
					link.href = window.URL.createObjectURL(blob);
					link.download = "wsal_settings.json";
					link.click();
				}
			});
		});

		// Check and import settings.
		jQuery( 'body' ).on( 'click', '[data-import-wsal-settings]', function ( e ) {
			e.preventDefault();

			jQuery( '#wsal-settings-file-output li, #wsal-import-read' ).remove();

			// Check extension.
			var jsonFile = jQuery( '#wsal-settings-file' );
			var ext = jsonFile.val().split(".").pop().toLowerCase();
			
			// Alert if wrong file type.
			if( jQuery.inArray( ext, ["json"] ) === -1 ){
				alert( wsal_import_data.wrongFormat );
				return false;
			}

			build_file_info( 'false' );
		});

		// Proceed with import after checks.
		jQuery( 'body' ).on( 'click', '#proceed', function ( e ) {
			build_file_info( 'true' );
		});

		jQuery( 'body' ).on( 'click', '.import-settings-modal-close', function ( e ) {
			var modal = document.getElementById( "import-settings-modal" );
			modal.style.display = "none";
		});		

		/**
		 * Check settings to make sure roles/users exists.
		 */
		function checkSettingPreImport( option_name, option_value, do_import ) {

			// Show popup.
			var modal = document.getElementById("import-settings-modal");
			modal.style.display = "block";
			
			if ( do_import == 'true' ) {
				jQuery( '#wsal-modal-title' ).text( wsal_import_data.importingMessage );
				jQuery( '[data-wsal-option-name] > span' ).addClass( 'complete' );
			} else {
				jQuery( '#wsal-modal-title' ).text( wsal_import_data.checkingMessage );
			}

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				async: true,
				data: {
					action: 'wsal_check_setting_pre_import',
					setting_name : option_name,
					setting_value : option_value,
					process_import : do_import,
					nonce : wsal_import_data.wp_nonce,
				},
				//wsal_data.wp_nonce
				success: function ( result ) {
					var wasSuccess = false;
					if ( result.success ) {
						wasSuccess = true;
						if ( do_import == 'true' && typeof result.data['import_confirmation'] != 'undefined' ) {
							var markup = '<span style="color: green;"> ' + result.data['import_confirmation']  + '</span>';
						} else {
							var markup = '<span style="color: green;" class="dashicons dashicons-yes-alt"></span>';
						}
						jQuery( '[data-wsal-option-name="'+ option_name +'"]' ).append( markup )
					} else {
						if ( 'not_found' == result.data['failure_reason_type'] ) {
							var helpText = wsal_import_data.notFoundMessage;
						} else if ( 'not_supported' == result.data['failure_reason_type'] ) {
							var helpText = wsal_import_data.notSupportedMessage;
						}
						var helpLink = "<a href='" + wsal_import_data.helpPage + "'>"+ wsal_import_data.helpLinkText +"</a>";
						jQuery( '[data-wsal-option-name="'+ option_name +'"]' ).append( '<span style="color: red;" class="dashicons dashicons-info"> <span>' + result.data['failure_reason'] + '</span> <a href="#" class="toolip" data-help="' + result.data['failure_reason_type'] + '" data-help-text="' + helpText + ' ' + helpLink +'">' + wsal_import_data.helpMessage + '</a></span>' );
					}

					var countNeeded = jQuery( '[data-wsal-option-name]' ).length;
					var countDone   = jQuery( '[data-wsal-option-name] > span:not(.complete)' ).length;
					
					if ( countNeeded == countDone ) {						
						if ( do_import == 'true' ) { 
							jQuery( '#wsal-modal-title' ).text( wsal_import_data.importedMessage );
							jQuery( '#ready-text').text( wsal_import_data.proceedMessage );
							jQuery( '#proceed').remove();
							jQuery( '#wsal-import-read' ).removeClass( 'disabled' );
							jQuery( '#cancel').val( wsal_import_data.ok );
						} else {
							var errorCount = jQuery( '[data-wsal-option-name] .dashicons-info' ).length;
							if ( errorCount ) {
								jQuery( '#wsal-modal-title' ).text( wsal_import_data.checksFailedMessage );
								var errorText = 'Proceed and skip invalid settings';
							} else {
								jQuery( '#wsal-modal-title' ).text( wsal_import_data.checksPassedMessage );
								var errorText = 'Proceed';
							}
							jQuery( '#wsal-import-read' ).remove();
							jQuery( '#wsal-settings-file-output' ).append( '<div id="wsal-import-read" style="display: inline-block;"><p id="ready-text">'+ wsal_import_data.readyMessage +'</p><input type="button" id="cancel" class="button-secondary import-settings-modal-close" value="'+ wsal_import_data.cancelMessage +'"> <input style="margin-left: 10px;" type="button" id="proceed" class="button-primary" value="'+ errorText +'"></div>' );
						}
					}
				}
			});
		}

		// Turn JSON into string and process it.
		function build_file_info( do_import ) {

			if ( do_import == 'false' ) {
				jQuery( '#wsal-settings-file-output' ).parent().append( '<div id="wsal-import-read" style="display: inline-block;" class="disabled"><input type="button" id="cancel" class="button-secondary import-settings-modal-close" value="'+ wsal_import_data.cancelMessage +'"> <input style="margin-left: 10px;" type="button" id="proceed" class="button-primary" value="'+ wsal_import_data.proceed +'"></div>' );
			} else {
				jQuery( '#wsal-import-read' ).addClass( 'disabled' );
			}

			var fileInput = document.getElementById( "wsal-settings-file" );
			var reader = new FileReader();
			reader.readAsText( fileInput.files[0] );		

			reader.onload = function () {
				var result = JSON.parse(reader.result);
				var resultsObj = JSON.parse( result );
				for (var i = 0; i < resultsObj.length; i++) {  
					if ( resultsObj[i] != "" ) { 
						var row = '';

						var option_name = resultsObj[i].option_name; 
						var option_value = resultsObj[i].option_value;
						var cols = "<li data-wsal-option-name=" + resultsObj[i].option_name + "><div>" + resultsObj[i].option_name.replace( 'wsal_', '' ).replace( '_', ' ' ).replace( '-', ' ' ) + "</div></li>";  
						row += cols;

						if ( do_import == 'false' ) {
							document.getElementById('wsal-settings-file-output').innerHTML += row;
							checkSettingPreImport( option_name, option_value, 'false' );
						} else {
							checkSettingPreImport( option_name, option_value, 'true' );
						}

					} 
				}
			};
		}

		jQuery( 'body' ).on( 'mouseenter', '[data-help]', function ( e ) {
			var message = jQuery( this ).data( 'help-text' ); 
			jQuery( this ).append( '<div class="tooltip help-msg">'+ message +'</div>' );
		});

		jQuery( 'body' ).on( 'mouseout', '[data-help]', function ( e ) {
			if ( jQuery('.help-msg:hover').length != 0 ) {
				setTimeout( function() {
					jQuery( '.help-msg').fadeOut( 800 );
				}, 1000 );
			}
		});
	});
	
	
 });
  