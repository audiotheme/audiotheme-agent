<p>
	<?php esc_html_e( "The first time you connect your site to AudioTheme.com, it's registered as an approved application, which allows it to access information related to your account. The information below details the status of your site's connection to help troubleshoot potential issues.", 'audiotheme-agent' ); ?>
</p>

<table class="widefat">
	<thead>
		<tr>
			<th colspan="2"><?php esc_html_e( 'Client Status', 'audiotheme-agent' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Registered', 'audiotheme-agent' ); ?></th>
			<td><?php echo $client->is_registered() ? esc_html__( 'Yes', 'audiotheme-agent' ) : esc_html__( 'No', 'audiotheme-agent' ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Client ID', 'audiotheme-agent' ); ?></th>
			<td><?php echo empty( $metadata['client_id'] ) ? esc_html__( 'Not Registered', 'audiotheme-agent' ) : esc_html( $metadata['client_id'] ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Authorized', 'audiotheme-agent' ); ?></th>
			<td><?php echo $client->is_authorized() ? esc_html__( 'Yes', 'audiotheme-agent' ) : esc_html__( 'No', 'audiotheme-agent' ); ?></td>
		</tr>
		<?php if ( $client->is_authorized() ) : ?>
			<tr>
				<th scope="row"><?php esc_html_e( 'Token Expiration', 'audiotheme-agent' ); ?></th>
				<td><?php echo date( 'Y-m-d H:i:s', $token['expires_at'] ); ?></td>
			</tr>
		<?php endif; ?>
		<tr>
			<th scope="row"><?php esc_html_e( 'Identity Crisis', 'audiotheme-agent' ); ?></th>
			<td><?php echo $client->has_identity_crisis() ? esc_html__( 'Yes', 'audiotheme-agent' ) : esc_html__( 'No', 'audiotheme-agent' ); ?></td>
		</tr>
	</tbody>
</table>

<?php if ( $client->is_registered() ) : ?>

	<h3 id="identity-crisis"><?php esc_html_e( 'Update Site Information', 'audiotheme-agent' ); ?></h3>

	<p>
		<?php
		wp_kses(
			__( 'If you change your <strong>Site Title</strong>, <strong>Site Address</strong>, or <strong>Site Icon</strong> the registered information may need to be updated. This usually happens when migrating from a development or staging server to a live server.', 'audiotheme-agent' ),
			array( 'strong' => array() )
		);
		?>
	</p>
	<p>
		<?php esc_html_e( 'Some of the registered details and their current values are displayed in the table below to help determine if anything has changed.', 'audiotheme-agent' ); ?>
	</p>

	<table class="audiotheme-agent-help-table widefat">
		<thead>
			<tr>
				<th></th>
				<th><?php esc_html_e( 'Registered Value', 'audiotheme-agent' ); ?></th>
				<th><?php esc_html_e( 'Current Value', 'audiotheme-agent' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Client Name', 'audiotheme-agent' ); ?></th>
				<td><?php echo isset( $metadata['client_name'] ) ? esc_html( $metadata['client_name'] ) : ''; ?></td>
				<td><?php echo esc_html( is_multisite() ? get_site_option( 'site_name' ) : get_bloginfo( 'name' ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Client URI', 'audiotheme-agent' ); ?></th>
				<td><?php echo isset( $metadata['client_uri'] ) ? esc_url( $metadata['client_uri'] ) : ''; ?></td>
				<td><?php echo esc_url( home_url() ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Logo URI', 'audiotheme-agent' ); ?></th>
				<td><?php echo isset( $metadata['logo_uri'] ) ? esc_url( $metadata['logo_uri'] ) : ''; ?></td>
				<td><?php echo esc_url( get_site_icon_url() ); ?></td>
			</tr>
		</tbody>
	</table>

	<p>
		<?php esc_html_e( 'If you recently imported your database from a testing server, you should only update the site details if this is the live server. If you have another WordPress installation using the same credentials, it may stop working unless you disconnect and resubscribe.', 'audiotheme-agent' ); ?>
	</p>
	<p>
		<a href="<?php echo esc_url( $update_url ); ?>" class="button"><?php esc_html_e( 'Update Now', 'audiotheme-agent' ); ?></a>
	</p>

<?php endif; ?>
