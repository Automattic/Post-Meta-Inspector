var editPost = wp.data.select( 'core/edit-post' ), lastIsSaving = false;

wp.data.subscribe(
	function() {
		var isSaving = editPost.isSavingMetaBoxes();
		if ( isSaving !== lastIsSaving && ! isSaving ) {
			lastIsSaving = isSaving;
			// Gutenberg Post Saving has finished!
			var data = {
				'action': 'update_post_meta_inspector',
				'nonce': pmi_data.nonce,
				'post': pmi_data.post
			};

			jQuery.get(
				ajaxurl,
				data,
				function( response ) {
					jQuery( '#post_meta_inspector' ).html( response );
				}
			);
		}

		lastIsSaving = isSaving;
	}
);
