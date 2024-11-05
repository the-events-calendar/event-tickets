<?php
/**
 * The main Maps and Layouts Home template used when there is an error.
 *
 * @since 5.16.0
 *
 * @var string      $page_title The title of the page.
 * @var string      $message    The message to display.
 * @var string|null $cta_label  The label of the button to click to connect to the service.
 * @var string|null $cta_url    The URL to click the button to connect to the service.
 */

?>

<div class="wrap">
	<h1>
		<?php
		echo esc_html_x( 'Seating', 'Seat Layouts home page title', 'event-tickets' );
		?>
	</h1>

	<?php
	if ( $cta_label ) :
		?>
		<p class="tec-tickets__admin__maps_layouts_home__error__message">
			<?php
			echo wp_kses_post( $message );
			?>
		</p>
		<p>
			<a
				class="button button-primary"
				href="
				<?php
				echo esc_url( $cta_url );
				?>
				"
			>
				<?php
				echo esc_html( $cta_label );
				?>
			</a>
		</p>
		<?php
	else :
		?>
		<p class="tec-tickets__admin__maps_layouts_home__error__message">
			<?php
			echo wp_kses_post( $message );
			?>
		</p>
		<?php
	endif;
	?>
</div>
