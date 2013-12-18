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

		<input type="hidden" id="_wpnonce" name="_wpnonce" value="c01e7d7e46"><input type="hidden" name="_wp_http_referer" value="/wp-admin/options-permalink.php">
		<p><?php _e( '', 'wp-fluxbb' ); ?></p>

		<h3 class="title"><?php _e( 'WP-FluxBB Options', 'wp-fluxbb' ); ?></h3>
		<p><?php _e( '', 'wp-fluxbb' ); ?></p>

		<table class="form-table" style="max-width:64em">
			<tbody>
				<tr>
					<th><?php _e( 'Auto Insert Users', 'wp-fluxbb' ); ?></th>
					<td>
						<label><input name="wpfluxbb[auto_insert_user]" type="radio" value="1" <?php checked( $this->plugin->wpfluxbb_o('auto_insert_user'), 1 ); ?>> <?php _e( 'On', 'wp-fluxbb' ); ?></label>
						<label><input name="wpfluxbb[auto_insert_user]" type="radio" value="0" <?php checked( $this->plugin->wpfluxbb_o('auto_insert_user'), 0 ); ?>> <?php _e( 'Off', 'wp-fluxbb' ); ?></label>
						<p><em><?php _e( 'If the User trying to log in has a valid FluxBB account but no WordPress account, authentification will fail. If this option is set to "On" WPFluxBB will automatically create a WP Account and validate the authentification. Default if "Off".', 'wp-fluxbb' ); ?></em></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h3 class="title"><?php _e( 'FluxBB Connection', 'wp-fluxbb' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="fluxbb_config_file"><?php _e( 'FluxBB Config file path', 'wp-fluxbb' ); ?></label></th>
					<td>
						<input name="wpfluxbb[fluxbb_config_file]" id="fluxbb_config_file" type="text" value="<?php echo $this->plugin->wpfluxbb_o('fluxbb_config_file'); ?>" size="42" />
<?php if ( '' != $this->plugin->wpfluxbb_o('fluxbb_config_file') ) { ?>
						<button id="wpfluxbb_test_config_file" class="button button-secondary button-small"><?php _e( 'Test Config File', 'wp-fluxbb' ); ?></button>
<?php } ?>
						<button id="wpfluxbb_scan_config_file" class="button button-secondary button-small"><?php _e( 'Scan folders', 'wp-fluxbb' ); ?></button>
						<div id="wpfluxbb_scan_results"><pre></pre></div>
					</td>
				</tr>
			</tbody>
		</table>

		<h3 class="title"><?php _e( 'FluxBB User Import', 'wp-fluxbb' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="fluxbb_config_file"><?php _e( 'Synchronise WordPress and FluxBB Users', 'wp-fluxbb' ); ?></label></th>
					<td>
<?php $users = $this->wpfluxbb_get_missing_users(); ?>
						<button id="wpfluxbb_user_sync" class="button button-secondary button-small" <?php if ( ! count( $users ) ) echo 'disabled'; ?>><?php _e( 'Synchronise Users', 'wp-fluxbb' ); ?></button>
						<div id="wpfluxbb_user_sync_results"></div>
						<p>
							<em><?php printf( __( 'Currently %s FluxBB Users are not synchronised with WordPress.', 'wp-fluxbb' ), '<strong>' . count( $users ) . '</strong>' ); ?>
							<a href="#" onclick="l=document.getElementById('missing_users_list');if(l.style.display!='none'){l.style.display='none';this.innerHTML='<?php _e( 'Show the list', 'wp-fluxbb' ); ?>';}else{l.style.display='block';this.innerHTML='<?php _e( 'Hide the list', 'wp-fluxbb' ); ?>';}return false;"><?php _e( 'Show the list', 'wp-fluxbb' ); ?></a></em>
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
