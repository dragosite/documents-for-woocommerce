/*global woocommerce_admin_meta_boxes */
jQuery( function( $ ) {

    // Uploading files.
	var documents_file_frame;
	var file_path_field;

	$( document.body ).on( 'click', '.add_doc_button', function( event ) {
        event.preventDefault();
        var $button = $(this);
        var $tbody = $button.prev('table').find('tbody');
        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: "html",
            data: {
                action: "add_document"
            },
            beforeSend: function(){
                $button.html('Adding...');
            },
            success: function(response) {
                $tbody.append('<tr>'+response+'</tr>');
                $button.html('Add document');
            }
        });
    });

    $( document.body ).on( 'click', '.remove_doc_button', function( event ) {
        $(this).closest( 'tr' ).remove();
    });

	$( document.body ).on( 'click', '.upload_doc_button', function( event ) {
		var $el = $( this );

		file_path_field = $el.closest( 'tr' ).find( 'td.file_url input' );

		event.preventDefault();

		// If the media frame already exists, reopen it.
		if ( documents_file_frame ) {
			documents_file_frame.open();
			return;
		}

		var documents_file_states = [
			// Main states.
			new wp.media.controller.Library({
				library:   wp.media.query(),
				multiple:  true,
				title:     $el.data('choose'),
				priority:  20,
				filterable: 'uploaded'
			})
		];

		// Create the media frame.
		documents_file_frame = wp.media.frames.documents_file = wp.media({
			// Set the title of the modal.
			title: $el.data('choose'),
			library: {
				type: ''
			},
			button: {
				text: $el.data('update')
			},
			multiple: true,
			states: documents_file_states
		});

		// When an image is selected, run a callback.
		documents_file_frame.on( 'select', function() {
			var file_path = '';
			var selection = documents_file_frame.state().get( 'selection' );

			selection.map( function( attachment ) {
				attachment = attachment.toJSON();
				if ( attachment.url ) {
					file_path = attachment.url;
				}
			});

			file_path_field.val( file_path ).trigger( 'change' );
		});

		// Set post to 0 and set our custom type.
		documents_file_frame.on( 'ready', function() {
			documents_file_frame.uploader.options.uploader.params = {
				type: 'documents_product'
			};
		});

		// Finally, open the modal.
		documents_file_frame.open();
	});

	// Download ordering.
	$( '.woocommerce_documents tbody' ).sortable({
		items: 'tr',
		cursor: 'move',
		axis: 'y',
		handle: 'td.doc-sort',
		scrollSensitivity: 40,
		forcePlaceholderSize: true,
		helper: 'clone',
		opacity: 0.65
    });
});