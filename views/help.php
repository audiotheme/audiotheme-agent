<table class="audiotheme-agent-help-table">
	<tbody>
		<tr>
			<th scope="row"><?php esc_html_e( 'Registered:', 'audiotheme-agent' ); ?></th>
			<td><?php echo $this->plugin->client->is_registered() ? esc_html( 'Yes' ) : esc_html__( 'No', 'audiotheme-agent' ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Authorized:', 'audiotheme-agent' ); ?></th>
			<td><?php echo $this->plugin->client->is_authorized() ? esc_html__( 'Yes', 'audiotheme-agent' ) : esc_html__( 'No', 'audiotheme-agent' ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Client ID:', 'audiotheme-agent' ); ?></th>
			<td><?php echo empty( $metadata['client_id'] ) ? esc_html__( 'Not Registered', 'audiotheme-agent' ) : esc_html( $metadata['client_id'] ); ?></td>
		</tr>

		<?php if ( $this->plugin->client->is_registered() ) : ?>

			<tr>
				<th scope="row"><?php esc_html_e( 'Registered URI:', 'audiotheme-agent' ); ?></th>
				<td><?php echo esc_url( $metadata['client_uri'] ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Current URI:', 'audiotheme-agent' ); ?></th>
				<td><?php echo esc_url( home_url() ); ?></td>
			</tr>

		<?php endif; ?>
	</tbody>
</table>

<?php if ( $this->plugin->client->is_registered() ) : ?>

	<h4><?php esc_html_e( 'Disconnect Site', 'audiotheme-agent' ); ?></h4>

	<p>
		<?php esc_html_e( 'In some cases, it may be necessary to re-register your site to fix a broken connection, especially when the "Registered URI" and "Current URI" are out of sync. Only disconnect your site as a last resort.', 'audiotheme-agent' ); ?>
	</p>
	<p>
		<a href="<?php echo esc_url( $disconnect_url ); ?>" class="button"><?php esc_html_e( 'Disconnect', 'audiotheme-agent' ); ?></a>
	</p>

<?php endif; ?>
