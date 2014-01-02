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
					beforeSend: function() {
						$('#wpfluxbb_scan_config_file').addClass('loading');
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
					},
					complete:function(){
						$('#wpfluxbb_scan_config_file').removeClass('loading');
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
					beforeSend: function() {
						$('#wpfluxbb_test_config_file').addClass('loading');
					},
					success: function(response) {
						$r = $('#wpfluxbb_scan_results pre');
						$r.empty();
						response = $.parseJSON(response);

						if ( undefined != response.errors && response.errors.length ) {
							$.each(response.errors, function() {
								$r.append(this.error_message+'\n');
							});
						}
						else if ( undefined != response.success && response.success.length ) {
							$r.text(response.success);
						}
					},
					complete:function(){
						$('#wpfluxbb_test_config_file').removeClass('loading');
					}
				});
			});
		}

		if ( $('#wpfluxbb_user_sync').length ) {
			$('#wpfluxbb_user_sync').click(function(e) {
				e.preventDefault();
				$.ajax({
					url: wp_ajax_.ajax_url,
					type: 'GET',
					data: {
						action: 'user_sync',
						notify: $('#wpfluxbb_user_sync_notify').is(':checked')
					},
					beforeSend: function() {
						$('#wpfluxbb_user_sync').addClass('loading');
					},
					success: function(response) {
						response = $.parseJSON(response);
						$r = $('#wpfluxbb_user_sync_results');
						$errors = $('#wpfluxbb_user_sync_errors');
						$r.empty();
						$errors.empty();

						if ( undefined != response.errors ) {
							$.each(response.errors, function() {
								$errors.append(this+'<br />');
							});
						}

						if ( undefined != response.users && response.users.length ) {
							$r.append(wp_ajax_.n_users_added.replace('{n}',response.users.length)+'<br />');
							var _users = [];
							$.each(response.users, function() {
								_users.push('<a href="user-edit.php?user_id='+this.id+'">'+this.name+'</a>');
							});
							_users = _users.join(", ");
							$r.append(_users);
						}
					},
					complete:function(){
						$('#wpfluxbb_user_sync').removeClass('loading');
					}
				});
			});
		}

	});

}(jQuery));