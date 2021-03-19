<?php
/**
 * Getting started banner section.
 *
 * @since TBD
 *
 * @var bool $etp_enabled         Event Tickets Plus enabled or not.
 * @var array $et_resource_links  Knowledgebase links for Event Tickets.
 * @var array $etp_resource_links Knowledgebase links for Event Tickets.
 */

$help_text = $etp_enabled
	? __( 'Thank you for using Event Tickets and Event Tickets Plus! We recommend looking through the settings below so that you can fine tune your specific ticketing needs. Here are some resources that can help.', 'event-tickets' )
	: __( 'Thank you for using Event Tickets! We recommend looking through the settings below so that you can fine tune your specific ticketing needs. Here are some resources that can help.', 'event-tickets' );

?>
<div id="event-tickets__admin-banner">
	<h3><?php echo esc_html__( 'Getting Started With Tickets', 'event-tickets' ); ?></h3>
	<p class="event-tickets__admin-banner-help-text"><?php echo esc_html__( $help_text ); ?></p>

	<div class="event-tickets__admin-banner-help-links">
		<div class="event-tickets__admin-banner-et-links">
			<h3><?php echo esc_html__( 'Beginner Resources', 'event-tickets' )  ?> </h3>

			<ul class="event-tickets__admin-banner-kb-list">
				<?php
				foreach ( $et_resource_links as $link ) {
					printf( '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>', esc_url( $link['href'] ), esc_html( $link['label'] ) );
				}
				?>
			</ul>
		</div>
		<div class="event-tickets__admin-banner-etp-links">
			<h3>
				<?php echo esc_html__( 'Advanced Plus Features', 'event-tickets' ); ?>
				<?php
					if ( ! $etp_enabled ) {
						printf( ' - <a class="upgrade-link" href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_url( 'https://theeventscalendar.com/products/wordpress-event-tickets/' ), esc_html__( 'Need To Upgrade?', 'event-tickets' ) );
					}
				?>
			</h3>

			<ul class="event-tickets__admin-banner-kb-list">
				<?php
				foreach ( $etp_resource_links as $link ) {
					printf( '<li><a href="%s" target="_blank" rel="nofollow">%s</a></li>', esc_url( $link['href'] ), esc_html( $link['label'] ) );
				}
				?>
			</ul>
		</div>
	</div>
</div>
<style>
	#event-tickets__admin-banner {
		background-color: #f9f9f9;
		border: 1px solid #ccc;
		border-radius: 4px;
		margin: 20px 0;
		padding: 8px 20px 12px;
		border-left: 5px solid #0073AA;
	}

	#event-tickets__admin-banner a {
		text-decoration: none;
	}

	p.event-tickets__admin-banner-help-text {
		max-width: 80%;
	}

	.event-tickets__admin-banner-help-links {
		display: flex;
	}

	.event-tickets__admin-banner-help-links div {
		min-width: 50%;
	}

	.event-tickets__admin-banner-et-links h3,
	.event-tickets__admin-banner-etp-links h3 {
		font-size: 14px;
	}
</style>
