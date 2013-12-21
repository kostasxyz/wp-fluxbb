<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   WPFluxBB
 * @author    Charlie MERLAND <charlie.merland@gmail.com>
 * @license   GPL-3.0+
 * @link      http://charliemerland.me
 * @copyright 2013 Charlie MERLAND
 */
?>

<div class="wrap">

	<?php screen_icon(); ?>
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form name="form" method="post">

		<?php wp_nonce_field( 'wpfluxbb-settings' ); ?>

		<p><?php _e( '', $this->plugin_slug ); ?></p>

		<h3 class="title"><?php _e( 'WP-FluxBB Options', $this->plugin_slug ); ?></h3>
		<p><?php _e( '', $this->plugin_slug ); ?></p>

		<table class="form-table" style="max-width:64em">
			<tbody>
				<tr>
					<th><?php _e( 'Auto Insert Users', $this->plugin_slug ); ?></th>
					<td>
						<label><input name="wpfluxbb[auto_insert_user]" type="radio" value="1" <?php checked( $this->plugin->wpfluxbb_o('auto_insert_user'), 1 ); ?>> <?php _e( 'On', $this->plugin_slug ); ?></label>
						<label><input name="wpfluxbb[auto_insert_user]" type="radio" value="0" <?php checked( $this->plugin->wpfluxbb_o('auto_insert_user'), 0 ); ?>> <?php _e( 'Off', $this->plugin_slug ); ?></label>
						<p><em><?php _e( 'If the User trying to log in has a valid FluxBB account but no WordPress account, authentification will fail. If this option is set to "On" WPFluxBB will automatically create a WP Account and validate the authentification. Default if "Off".', $this->plugin_slug ); ?></em></p>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Hide WordPress Logo in Login Page', $this->plugin_slug ); ?></th>
					<td>
						<label><input name="wpfluxbb[remove_login_logo]" type="radio" value="1" <?php checked( $this->plugin->wpfluxbb_o('remove_login_logo'), 1 ); ?>> <?php _e( 'On', $this->plugin_slug ); ?></label>
						<label><input name="wpfluxbb[remove_login_logo]" type="radio" value="0" <?php checked( $this->plugin->wpfluxbb_o('remove_login_logo'), 0 ); ?>> <?php _e( 'Off', $this->plugin_slug ); ?></label>
						<p><em><?php _e( 'WPFluxBB uses WordPress\' Login Page to authenticate users and log them in both WordPress and FluxBB; the WordPress Logo in the Login Page can be misleading and confuse users and therefore is not displayed by default. Turn this to "On" to display the Logo.', $this->plugin_slug ); ?></em></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h3 class="title"><?php _e( 'FluxBB Connection', $this->plugin_slug ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th>
						<label for="fluxbb_config_file"><?php _e( 'FluxBB Config file path', $this->plugin_slug ); ?></label>
					</th>
					<td>
						<input name="wpfluxbb[fluxbb_config_file]" id="fluxbb_config_file" type="text" value="<?php echo $this->plugin->wpfluxbb_o('fluxbb_config_file'); ?>" size="42" />
<?php if ( '' != $this->plugin->wpfluxbb_o('fluxbb_config_file') ) { ?>
						<button id="wpfluxbb_test_config_file" class="button button-secondary button-small"><?php _e( 'Test Config File', $this->plugin_slug ); ?></button>
<?php } ?>
						<button id="wpfluxbb_scan_config_file" class="button button-secondary button-small"><?php _e( 'Scan folders', $this->plugin_slug ); ?></button>
						<p><em><?php _e( 'Absolute path to your forum config file, ex: <code>/home/www/public/fluxbb/config.php</code>.', $this->plugin_slug ) ?></em></p>
						<div id="wpfluxbb_scan_results"><pre></pre></div>
					</td>
				</tr>
				<tr>
					<th>
						<label for="fluxbb_base_url"><?php _e( 'FluxBB Base URL', $this->plugin_slug ); ?></label>
					</th>
					<td>
						<input name="wpfluxbb[fluxbb_base_url]" id="fluxbb_base_url" type="text" value="<?php echo $this->plugin->wpfluxbb_o('fluxbb_base_url'); ?>" size="42" />
						<p><em><?php _e( 'Full url of your forum, ex: <code>http://yourwebsite.com/forum</code> or <code>http://forum.yourwebsite.com</code>.', $this->plugin_slug ) ?></em></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h3 class="title"><?php _e( 'FluxBB User Import', $this->plugin_slug ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th>
						<label for="fluxbb_config_file"><?php _e( 'Synchronise WordPress and FluxBB Users', $this->plugin_slug ); ?></label>
					</th>
					<td>
<?php $users = $this->wpfluxbb_get_missing_users(); ?>
						<button id="wpfluxbb_user_sync" class="button button-secondary button-small" <?php if ( ! count( $users ) ) echo 'disabled'; ?>><?php _e( 'Synchronise Users', $this->plugin_slug ); ?></button><br />
						<label for="wpfluxbb_user_sync_notify"><input type="checkbox" id="wpfluxbb_user_sync_notify" value="" /> <?php _e( 'Send Users a notification', $this->plugin_slug ); ?></label>
						<div id="wpfluxbb_user_sync_errors"></div><div id="wpfluxbb_user_sync_results"></div>
						<p>
							<em><?php printf( __( 'Currently %s FluxBB Users are not synchronised with WordPress.', $this->plugin_slug ), '<strong>' . count( $users ) . '</strong>' ); ?>
							<a href="#" onclick="l=document.getElementById('missing_users_list');if(l.style.display!='none'){l.style.display='none';this.innerHTML='<?php _e( 'Show the list', $this->plugin_slug ); ?>';}else{l.style.display='block';this.innerHTML='<?php _e( 'Hide the list', $this->plugin_slug ); ?>';}return false;"><?php _e( 'Show the list', $this->plugin_slug ); ?></a></em>
						</p>
						<div id="missing_users_list" style="display:none"><?php
foreach ( $users as $i => $user ) {
	$users[ $i ] = sprintf( '<a href="profile.php?id=%d">%s</a>', $user->id, $user->username );
}
echo implode( ', ', $users );
?></div>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Enregistrer les modifications"></p>
	</form>

</div>
