<h2><?php esc_html_e( 'Subscriptions', 'audiotheme-agent' ); ?></h2>

<table class="audiotheme-agent-subscriptions widefat striped fixed">
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
							__( 'Enter a connection token from your <a href="%s" target="_blank">dashboard on AudioTheme.com</a> to enable automatic updates for this site.', 'audiotheme-agent' ),
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

<script type="text/html" id="tmpl-audiotheme-agent-packages-table">
	<h2>{{ data.title }}</h2>

	<table class="audiotheme-agent-packages wp-list-table widefat fixed">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'audiotheme-agent' ); ?></th>
				<th><?php esc_html_e( 'Installed Version', 'audiotheme-agent' ); ?></th>
				<th><?php esc_html_e( 'Current Release', 'audiotheme-agent' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
</script>

<script type="text/html" id="tmpl-audiotheme-agent-packages-table-row">
	<th scope="row"><a href="{{ data.homepage }}" target="_blank">{{ data.name }}</a></th>
	<td>{{ data.installed_version }}</td>
	<td>{{ data.current_version }}</td>
	<td class="action">
		<span class="spinner" style="float: none"></span>
		{{{ data.action_button }}}
		<span class="response"></span>
	</td>
</script>

<script type="text/html" id="tmpl-audiotheme-agent-subscriptions-table-row">
	<th scope="row">{{ data.title }}</th>
	<td>{{ data.status }}</td>
	<td>{{ data.nextPaymentDate( data.next_payment ) }}</td>
	<td style="text-align: right">
		<span class="spinner" style="float: none"></span>
		<button type="button" class="button js-disconnect-subscription"><?php esc_html_e( 'Disconnect', 'audiotheme-agent' ); ?></button>
	</td>
</script>
