<h2><?php esc_html_e( 'Subscriptions', 'audiotheme-agent' ); ?></h2>

<table class="audiotheme-agent-subscriptions widefat fixed">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Subscription', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Status', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Renewal Date', 'audiotheme-agent' ); ?></th>
			<th class="column-action">&nbsp;</th>
		</tr>
	</thead>
	<tbody></tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<p>
					<?php
					printf(
						wp_kses(
							__( 'Enter a subscription access token from your <a href="%s" target="_blank">dashboard on AudioTheme.com</a> to activate automatic updates for this site.', 'audiotheme-agent' ),
							array( 'a' => array( 'href' => array(), 'target' => array() ) )
						),
						'https://audiotheme.com/account/'
					);
					?>
				</p>
				<p class="audiotheme-agent-subscription-token-group">
					<input type="text" class="regular-text">
					<button class="button"><?php esc_html_e( 'Connect', 'audiotheme-agent' ); ?></button>
					<span class="audiotheme-agent-subscription-token-group-feedback"></span>
				</p>
			</td>
		</tr>
	</tfoot>
</table>

<h2><?php esc_html_e( 'Products', 'audiotheme-agent' ); ?></h2>

<table class="wp-list-table widefat fixed striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Product', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Version', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Type', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Current Version', 'audiotheme-agent' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $installed_packages as $slug => $package ) : ?>
			<tr>
				<th><?php echo $package['name']; ?></th>
				<td><?php echo $package['version']; ?></td>
				<td><?php echo $package['type']; ?></td>
				<td><?php echo isset( $packages[ $slug ] ) ? $packages[ $slug ]->version : ''; ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<script type="text/html" id="tmpl-audiotheme-agent-subscriptions-table-row">
	<th scope="row">{{ data.title }}</th>
	<td>{{ data.status }}</td>
	<td>{{ data.nextPaymentDate( data.next_payment ) }}</td>
	<td><a href="#" class="js-disconnect-subscription"><?php esc_html_e( 'Disconnect', 'audiotheme-agent' ); ?></a></td>
</script>
