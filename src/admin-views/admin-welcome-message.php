<?php
/**
 * The template that displays the welcome message when the plugin is first activated.
 */
$video_url = 'https://vimeo.com/172163102';
?>

<p class="tribe-welcome-message">
	<?php printf( esc_html__( 'You are running Version %s and deserve a hug :-)', 'event-tickets' ), Tribe__Tickets__Main::VERSION ); ?>
</p>

<div class="tribe-welcome-video-wrapper">
	<?php echo wp_oembed_get( $video_url ); ?>
</div>

<div class="tribe-row">
	<div class="tribe-half-column">
		<h2><?php esc_html_e( 'We Need Your Help', 'event-tickets' ); ?></h2>
		<p><?php esc_html_e( "Your ratings help us bring The Events Calendar to more users. More happy users mean more support, more features, and more of everything you know and love about Event Tickets. We couldn't do this without your support.", 'event-tickets' ); ?></p>
		<p><strong><?php esc_html_e( 'Rate us today!', 'event-tickets' ); ?></strong> <a class="tribe-rating-link" href="http://wordpress.org/support/view/plugin-reviews/event-tickets?filter=5" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a></p>
		<a href="http://wordpress.org/support/view/plugin-reviews/event-tickets?filter=5" target="_blank" class="button-primary"><?php esc_html_e( 'Rate It', 'event-tickets' ); ?></a>
	</div>
	<div class="tribe-half-column">
		<h2><?php esc_html_e( 'Newsletter Signup', 'event-tickets' ); ?></h2>
		<p><?php esc_html_e( 'Stay in touch with Event Tickets Plus. We send out periodic updates, key developer notices, and even the occasional discount.', 'event-tickets' ); ?></p>
		<form action="http://moderntribe.createsend.com/t/r/s/athqh/" method="post">
			<p>
				<input id="dev-news-field" name="cm-ol-thkduyk" type="checkbox" />
				<label for="dev-news-field"><?php esc_html_e( 'Developer News', 'event-tickets' );?></label>
			</p>
			<p>
				<input id="news-announcements-field" name="cm-ol-athqh" checked type="checkbox" />
				<label for="news-announcements-field"><?php esc_html_e( 'News and Announcements', 'event-tickets' );?></label>
			</p>
			<p>
				<input id="fieldEmail" class="regular-text" name="cm-athqh-athqh" type="email" placeholder="<?php esc_attr_e( 'Email', 'event-tickets' ); ?>" required />
			</p>
			<button type="submit" class="button-primary"><?php esc_html_e( 'Sign Up', 'event-tickets' ); ?></button>
		</form>
	</div>
</div>

<hr/>

<div class="tribe-row tribe-welcome-links">
	<div class="tribe-half-column">
		<h4><?php esc_html_e( 'Getting Started', 'event-tickets' ); ?></h4>
		<p><a href="http://m.tri.be/1951" target="_blank"><?php esc_html_e( 'Check out the New User Primer &amp; Tutorials', 'event-tickets' ); ?></a></p>

		<h4><?php esc_html_e( 'Looking for More Features?', 'event-tickets' ); ?></h4>
		<p><a href="http://m.tri.be/1952" target="_blank"><?php esc_html_e( 'Addons for creating tickets, custom registration, events and more.', 'event-tickets' ); ?></a></p>

		<h4><?php esc_html_e( 'Support Resources', 'event-tickets' ); ?></h4>
		<p><a href="http://m.tri.be/1953" target="_blank"><?php esc_html_e( 'FAQs, Documentation, Tutorials and Forums', 'event-tickets' ); ?></a></p>
	</div>
	<div class="tribe-half-column">
		<h4><?php esc_html_e( 'Release Notes', 'event-tickets' ); ?></h4>
		<p><a href="http://m.tri.be/1954" target="_blank"><?php esc_html_e( 'Get the Skinny on the Latest Updates', 'event-tickets' ); ?></a></p>

		<h4><?php esc_html_e( 'News For Events Users', 'event-tickets' ); ?></h4>
		<p><a href="http://m.tri.be/1955" target="_blank"><?php esc_html_e( 'Product Releases, Tutorials and Community Activity', 'event-tickets' ); ?></a></p>
	</div>
</div>
