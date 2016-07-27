<div class="audiotheme-agent-screen">
	<div class="audiotheme-agent-screen-primary">

		<div id="audiotheme-agent-ticket-panel" class="audiotheme-agent-panel">
			<div class="audiotheme-agent-panel-header">
				<h2 class="audiotheme-agent-panel-title"><?php esc_html_e( 'Priority Support', 'audiotheme-agent' ); ?></h2>
			</div>

			<div class="audiotheme-agent-panel-body">
				<?php if ( $client->is_authorized() ) : ?>

					<iframe src="<?php echo esc_url( $iframe_url ); ?>" width="100%" height="500" frameBorder="0" class="gfiframe"></iframe>
					<script src="https://audiotheme.com/content/plugins/gravity-forms-iframe/assets/scripts/gfembed.min.js" type="text/javascript"></script>

				<?php else : ?>

					<p>
						<strong><?php esc_html_e( 'Support is only available with an active subscription.', 'audiotheme-agent' ); ?></strong>
					</p>
					<p>
						<?php
						echo wp_kses(
							__( 'Please <a href="https://audiotheme.com/support/audiotheme-agent/" target="_blank">connect your site</a> to receive automatic updates and support. After connecting, you can send priority support requests directly from this screen.', 'audiotheme-agent' ),
							array( 'a' => array( 'href' => true, 'target' => true ) )
						);
						?>
					</p>

				<?php endif; ?>

			</div>
		</div>

	</div>

	<div class="audiotheme-agent-screen-secondary">

		<div class="audiotheme-agent-panel">
			<div class="audiotheme-agent-panel-header">
				<h2 class="audiotheme-agent-panel-title"><?php esc_html_e( 'Knowledge Base', 'audiotheme-agent' ); ?></h2>
			</div>

			<div class="audiotheme-agent-panel-body">
				<p>
					<?php esc_html_e( 'The knowledge base includes helpful articles for getting started, theme setup guides, and answers to common questions.', 'audiotheme-agent' ); ?>
				</p>
				<p>
					<?php
					echo wp_kses(
						__( '<a href="https://audiotheme.com/support/" target="_blank">Browse the articles</a> now or search below:', 'audiotheme-agent' ),
						array( 'a' => array( 'href' => true, 'target' => true ) )
					);
					?>
				</p>
				<form action="https://audiotheme.com/support/search/" method="GET" target="_blank">
					<p>
						<input type="search" name="s" style="height: 28px">
						<button class="button"><?php esc_html_e( 'Search', 'audiotheme-agent' ); ?></button>
					</p>
				</form>
			</div>
		</div>

	</div>
</div>
