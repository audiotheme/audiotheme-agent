<?php
if ( $client->has_identity_crisis() ) {
	printf(
		'<div class="notice notice-error audiotheme-agent-client-notice"><p>%s <a href="#identity-crisis">%s</a></p></div>',
		esc_html__( 'Outdated client information has been detected.', 'audiotheme-agent' ),
		esc_html__( 'Learn more.', 'audiotheme-agent' )
	);
}
?>

<h2><?php esc_html_e( 'Subscriptions', 'audiotheme-agent' ); ?></h2>

<table class="audiotheme-agent-subscriptions audiotheme-agent-table widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Subscription', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Status', 'audiotheme-agent' ); ?></th>
			<th><?php esc_html_e( 'Renewal Date', 'audiotheme-agent' ); ?></th>
			<th class="column-action">&nbsp;</th>
		</tr>
	</thead>
	<tbody><?php
		if ( $client->is_registered() && ! $client->is_authorized() ) : ?>
				<tr>
					<td colspan="4" style="background-color: #fbeaea">
						<?php esc_html_e( 'Your site has been disconnected. Please reconnect to continue receiving updates and support.', 'audiotheme-agent' ); ?>
					</td>
				</tr>
			<?php
		endif;
	?></tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<?php if ( $client->is_registered() ) : ?>
					<!-- @todo Consider showing a message about connecting additional subscriptions. -->
					<p>
						<a href="<?php echo esc_url( $client->get_authorization_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Connect to AudioTheme.com', 'audiotheme-agent' ); ?></a>
					</p>
				<?php else : ?>
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
				<?php endif; ?>
			</td>
		</tr>
	</tfoot>
</table>

<script type="text/html" id="tmpl-audiotheme-agent-packages-table">
	<h2>{{ data.title }}</h2>

	<table class="audiotheme-agent-packages audiotheme-agent-table widefat">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'audiotheme-agent' ); ?></th>
				<th class="column-installed-version"><?php esc_html_e( 'Installed Version', 'audiotheme-agent' ); ?></th>
				<th class="column-current-version"><?php esc_html_e( 'Current Release', 'audiotheme-agent' ); ?></th>
				<th class="column-action"></th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
</script>

<script type="text/html" id="tmpl-audiotheme-agent-packages-table-row">
	<th scope="row"><a href="{{ data.homepage }}" target="_blank">{{ data.name }}</a></th>
	<td class="column-installed-version">{{ data.installed_version }}</td>
	<td class="column-current-version">{{ data.current_version }}</td>
	<td class="column-action">
		{{{ data.action_button }}}
		<span class="response"></span>
	</td>
</script>

<script type="text/html" id="tmpl-audiotheme-agent-subscriptions-table-row">
	<th scope="row">{{ data.title }}</th>
	<td>{{ data.status }}</td>
	<td>{{ data.nextPaymentDate( data.next_payment ) }}</td>
	<td class="column-action">
		<button type="button" class="button js-disconnect-subscription"><?php esc_html_e( 'Disconnect', 'audiotheme-agent' ); ?></button>
	</td>
</script>
