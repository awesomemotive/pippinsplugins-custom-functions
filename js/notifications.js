jQuery(document).ready( function($) {

	$(".remove-notice").click( function() {
		var notice_id = $(this).attr('rel');
		var data = {
			action: 'mark_as_read',
			notice_read: notice_id
		};
		$.post(notices_ajax_script.ajaxurl, data, function(response) {
			$('#notification-area').fadeOut();
		});
		return false;
	});
	
});