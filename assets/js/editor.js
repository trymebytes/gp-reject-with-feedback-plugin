(function( $, $gp ) {
    
	$gp.editor.hooks.set_status_rejected = function() {
		button = $( this );
		var gp_rejection_feedback = prompt('Enter reason for rejection');
		//extract locale from page url
		path = location.pathname.substr(1).slice(0, -1).split('/');
		locale = path[path.length - 2];
		
		payload = {};
		payload.rejection_feedback = gp_rejection_feedback;
		payload.locale = locale;
		payload.original_id = $gp.editor.current.original_id;
		payload.translation_id = $gp.editor.current.translation_id;
		
		jQuery( document ).ready( function () {
			const data = {
				action: 'reject_with_feedback',
				data: payload,
				_ajax_nonce: gp_reject_with_feedback_js.nonce,
			};
			$.ajax( {
				type: 'POST',
				url: gp_reject_with_feedback_js.ajaxurl,
				data: data,
				success: function( data ) {
					button.prop( 'disabled', false );
					$gp.notices.success( 'Translation Rejected!' );
					$gp.editor.next();
				},
				error: function( xhr, msg ) {
					button.prop( 'disabled', false );
					msg = xhr.responseText ? 'Error: ' + xhr.responseText : 'Error setting the status!';
					$gp.notices.error( msg );
				}
			} );
		} );
		return false;
	};
}(jQuery, $gp)
);