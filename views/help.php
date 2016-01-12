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
	</tbody>
</table>
