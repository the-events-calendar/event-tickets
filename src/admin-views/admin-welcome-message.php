<?php
/**
 * The template that displays the welcome message when the plugin is first activated.
 */
?>

<p class="tribe-welcome-version"><?php printf( '<strong>%1$s %2$s</strong>', esc_html__( 'Version', 'event-tickets' ), Tribe__Tickets__Main::VERSION ); ?></p>

<p class="tribe-welcome-message"><?php esc_html_e( 'Event Tickets is all about getting tickets into the hands of your attendees as efficiently as possible. No muss, no fuss&mdash;just one seamless transaction on your site.', 'event-tickets' ); ?></p>

<p class="tribe-welcome-message">
<?php
	printf(
		'%1$s <a href="%2$s" target="_blank">%3$s <em>%4$s</em> %5$s</a> %6$s',
		esc_html__( 'Check out the resources below for a comprehensive intro to the plugin, or head to', 'event-tickets' ),
		admin_url( 'edit.php?post_type=tribe_events&page=tribe-common' ),
		esc_html__( 'the', 'event-tickets' ),
		esc_html__( 'Events', 'event-tickets' ),
		esc_html__( 'section of the admin', 'event-tickets' ),
		esc_html__( 'to create your very first ticket!', 'event-tickets' )
	);
?>
</p>


<div class="tribe-row">

	<div class="tribe-half-column">
		<h4 data-tribe-icon="dashicons-welcome-learn-more"><?php esc_html_e( 'Getting Started', 'event-tickets' ); ?></h4>
		<p><?php esc_html_e( 'Start strong with these helpful resources.', 'event-tickets' ); ?></p>
		<ul>
			<li><a href="https://theeventscalendar.com/product/wordpress-event-tickets/" target="_blank"><?php esc_html_e( 'Key Features', 'event-tickets' ); ?></a></li>
			<li><a href="http://m.tri.be/1a9u" target="_blank"><?php esc_html_e( 'Settings Overview', 'event-tickets' ); ?></a></li>
			<li><a href="http://m.tri.be/1a9v" target="_blank"><?php esc_html_e( 'New User Primer', 'event-tickets' ); ?></a></li>
		</ul>
	</div>

	<div class="tribe-half-column">
		<h4 data-tribe-icon="dashicons-sos"><?php esc_html_e( 'Resources and Support', 'event-tickets' ); ?></h4>
		<p><?php esc_html_e( 'We’ve got your back every step of the way.', 'event-tickets' ); ?></p>
		<ul>
			<li><a href="http://m.tri.be/1a9w" target="_blank"><?php esc_html_e( 'Search the Knowledgebase', 'event-tickets' ); ?></a></li>
			<li><a href="http://m.tri.be/1a9x" target="_blank"><?php esc_html_e( 'Available Translations', 'event-tickets' ); ?></a></li>
			<li><a href="http://m.tri.be/1a9y" target="_blank"><?php esc_html_e( 'Submit a Help Desk Request', 'event-tickets' ); ?></a></li>
		</ul>
	</div>
</div>

<div class="tribe-row">

	<div class="tribe-half-column">
		<h4><?php esc_html_e( 'The Latest and Greatest', 'event-tickets' ); ?></h4>
		<p><?php esc_html_e( 'Frequent maintenance releases keep your ticket sales running smoothly.', 'event-tickets' ); ?> <a href="http://m.tri.be/1a9z" target="_blank"><?php esc_html_e( 'View the latest changelog', 'event-tickets' ); ?></a>.</p>
		<p><?php esc_html_e( 'Gearing up with Gutenberg?', 'event-tickets' ); ?> <a href="http://m.tri.be/1aa0" target="_blank"><?php esc_html_e( 'Get the latest block editor news', 'event-tickets' ); ?></a>.</p>
	</div>

	<div class="tribe-half-column">
		<h4 data-tribe-icon="dashicons-megaphone"><?php esc_html_e( "Don't Miss Out", 'event-tickets' ); ?></h4>
		<p><?php esc_html_e( 'Stay in touch with Event Tickets and our entire family of events management tools. We share news, occasional discounts, and hilarious gifs.', 'event-tickets' ); ?></p>

		<form action="https://support-api.tri.be/mailing-list/subscribe" method="post">
			<p><input id="fieldEmail" class="regular-text" name="email" type="email" placeholder="<?php esc_attr_e( 'Email', 'event-tickets' ); ?>" required /></p>
			<div>
				<input id="cm-privacy-consent" name="consent" required type="checkbox" role="checkbox" aria-checked="false" />
				<label for="cm-privacy-consent"><?php esc_html_e( 'Add me to the list', 'the-events-calendar' ); ?></label>
			</div>
			<p>
				<input type="hidden" name="list" value="tec-newsletter" />
				<input type="hidden" name="source" value="plugin:et" />
				<button type="submit" class="button-primary"><?php esc_html_e( 'Sign Up', 'the-events-calendar' ); ?></button>
			</p>
		</form>
	</div>
</div>

<div class="tribe-row">
	<div class="tribe-half-column">
		<h4 data-tribe-icon="dashicons-heart"><?php esc_html_e( 'We Need Your Help', 'event-tickets' ); ?></h4>
		<p><?php esc_html_e( 'Your ratings keep us focused on making our plugins as useful as possible so we can help other WordPress users just like you.', 'event-tickets' ); ?></p>
		<p><strong><?php esc_html_e( 'Rate us today!', 'event-tickets' ); ?></strong> <a class="tribe-rating-link" href="https://wordpress.org/support/plugin/event-tickets/reviews/?filter=5" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a></p>
		<a href="https://wordpress.org/support/plugin/event-tickets/reviews/?filter=5" target="_blank" class="button-primary"><?php esc_html_e( 'Rate It', 'event-tickets' ); ?></a>
	</div>

	<div class="tribe-half-column"></div>
</div>
