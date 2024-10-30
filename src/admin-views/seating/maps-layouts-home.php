<?php
/**
 * The main Maps and Layouts Home template.
 *
 * @since 5.16.0
 *
 * @var Tab[] $the_tabs The set of tabs to display, in order.
 * @var Tab   $current  The current tab.
 */

use TEC\Tickets\Seating\Admin\Tabs\Tab;

?>

<div class="wrap">
	<h1>
		<?php
		echo esc_html_x( 'Seating', 'Seat Layouts home page title', 'event-tickets' );
		?>
	</h1>

	<?php
	if ( count( $the_tabs ) > 1 ) :
		?>
		<h2 id="tribe-settings-tabs" class="nav-tab-wrapper">

			<?php
			foreach ( $the_tabs as $the_tab ) :
				?>
				<a
					id="
					<?php
					echo esc_attr( $the_tab::get_id() );
					?>
					"
					class="nav-tab
					<?php
					echo esc_attr( $the_tab === $current ? 'nav-tab-active' : '' );
					?>
					"
					href="
					<?php
					echo esc_url( $the_tab->get_url() );
					?>
					"
				>
					<?php
					echo esc_html( $the_tab->get_title() );
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
