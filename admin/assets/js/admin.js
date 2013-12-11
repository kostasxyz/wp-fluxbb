(function ( $ ) {

	$(function () {

		if ( $('#wpfluxbb_scan_config_file').length ) {
			$('#wpfluxbb_scan_config_file').click(function(e) {
				e.preventDefault();
				$.ajax({
					url: wp_ajax_.ajax_url,
					type: 'GET',
					data: {
						action: 'scan_folders'
					},
					success: function(response) {
						$r = $('#wpfluxbb_scan_results');
						$r.empty().append('<ol />');
						r = $.parseJSON(response);
						$.each(r, function() {
							if ( undefined != this.error_code )
								$r.find('ol').append('<li>'+this.error_message+'</li>');
						});
					}
				});
			});
		}

	});

}(jQuery));