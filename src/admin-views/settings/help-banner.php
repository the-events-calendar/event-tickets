<?php
/**
 * Settings help banner
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
<div id="event-tickets-info">
	<h3><?php esc_html_e( 'Getting Started With Tickets', 'event-tickets' ); ?></h3>
	<p class="help-text"><?php esc_html_e( $help_text ); ?></p>

	<div class="help-links-wrapper">
		<div class="et-help-links">
			<h3><?php esc_html_e( 'Beginner Resources', 'event-tickets' )  ?> </h3>

			<ul class="kb-links">
				<?php
				foreach ( $et_resource_links as $link ) {
					printf( '<li><a href="%s" target="_blank" rel="nofollow">%s</a></li>', esc_attr( $link['href'] ), esc_html( $link['label'] ) );
				}
				?>
			</ul>
		</div>
		<div class="etp-help-links">
			<h3>
				<?php esc_html_e( 'Advanced Plus Features', 'event-tickets' ); ?>
				<?php
					if ( ! $etp_enabled ) {
						printf( ' - <a class="upgrade-link" href="%s" target="_blank" rel="nofollow">%s</a>', esc_attr( 'https://theeventscalendar.com/products/wordpress-event-tickets/' ) , esc_html__( 'Need To Upgrade?', 'event-tickets' ) );;
					}
				?>
			</h3>

			<ul class="kb-links">
				<?php
				foreach ( $etp_resource_links as $link ) {
					printf( '<li><a href="%s" target="_blank" rel="nofollow">%s</a></li>', esc_attr( $link['href'] ), esc_html( $link['label'] ) );
				}
				?>
			</ul>
		</div>
	</div>
</div>
<style>
	#event-tickets-info {
		background-color: #f9f9f9;
		border: 1px solid #ccc;
		border-radius: 4px;
		margin: 20px 0;
		padding: 8px 20px 12px;
		border-left: 5px solid #0073AA;
	}

	#event-tickets-info a {
		text-decoration: none;
	}

	p.help-text {
		max-width: 80%;
	}

	.help-links-wrapper {
		display: flex;
	}

	.help-links-wrapper div {
		min-width: 50%;
	}

	.et-help-links h3, .etp-help-links h3 {
		font-size: 14px;
	}
</style>