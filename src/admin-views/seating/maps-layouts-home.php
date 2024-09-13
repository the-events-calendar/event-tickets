<?php
/**
 * The main Maps and Layouts Home template.
 *
 * @since TBD
 *
 * @var Tab[] $tabs    The set of tabs to display, in order.
 * @var Tab   $current The current tab.
 */

use TEC\Tickets\Seating\Admin\Tabs\Tab;

?>

<div class="wrap">
	<h1>
		<?php
		echo esc_html_x( 'Seat Layouts', 'Seat Layouts home page title', 'event-tickets' );
		?>
	</h1>

	<?php
	if ( count( $tabs ) > 1 ) :
		?>
		<h2 id="tribe-settings-tabs" class="nav-tab-wrapper">

			<?php
			foreach ( $tabs as $tab ) :
				?>
				<a
					id="
					<?php
					echo esc_attr( $tab::get_id() );
					?>
					"
					class="nav-tab
					<?php
					echo esc_attr( $tab === $current ? 'nav-tab-active' : '' );
					?>
					"
					href="
					<?php
					echo esc_url( $tab->get_url() );
					?>
					"
				>
					<?php
					echo esc_html( $tab->get_title() );
					?>
				</a>
				<?php
			endforeach;
			?>
		</h2>
		<?php
	endif;
	?>

	<?php
	$current->render();
	?>
</div>
