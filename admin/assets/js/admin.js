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
						response = $.parseJSON(response);
						$r = $('#wpfluxbb_scan_results pre');
						$r.empty();

						$.each(response, function() {
							$r.append(wp_ajax_.file_exists.replace('{file}',this.file)+'\n');
							if ( this.errors.length ) {
								var errors = this.errors;
								$.each(errors, function() {
									$r.append('  > '+this.error_message+'\n');
								});
							}
						});
					}
				});
			});
		}

		if ( $('#wpfluxbb_test_config_file').length ) {
			$('#wpfluxbb_test_config_file').click(function(e) {
				e.preventDefault();
				$.ajax({
					url: wp_ajax_.ajax_url,
					type: 'GET',
					data: {
						action: 'test_config_file',
						config_file: $('#fluxbb_config_file').val()
					},
					success: function(response) {
						$r = $('#wpfluxbb_scan_results pre');
						$r.empty();

						if ( 'string' == typeof response ) {
							$r.text(response);
						}
						else {
							response = $.parseJSON(response);
							if ( undefined != response.errors ) {
								$.each(response.errors, function() {
									$r.append(this.error_message+'\n');
								});
							}
						}
					}
				});
			});
		}

	});

}(jQuery));