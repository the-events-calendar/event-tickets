<?php
/**
 * Attendee Registration
 *
 * @since 4.9
 *
 * @todo: replace this entire stinky miasma with a React powered block
 */
class Tribe__Tickets__Editor__Attendee_Registration {
	public $post;
	public $ticket_id;
	public $ticket;

	/**
	 * @since 4.9
	 *
	 * @return void
	 */
	public function hook() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Hooked to admin_menu to register the attendee registration page
	 *
	 * @since 4.9
	 *
	 * @return null
	 */
	public function admin_menu() {
		// Setup attendee registration
		add_submenu_page(
			null, // attach to null so it doesn't appear in sidebar
			'Attendee Registration',
			'Attendee Registration', // hidden
			'edit_posts',
			'attendee-registration',
			array( $this, 'render' )
		);
	}

	/**
	 * Hooked to admin_init to setup ticket and post data
	 *   also handles maybe handling form submission
	 *
	 * @since 4.9
	 *
	 * @return null
	 */
	public function admin_init() {
		if ( ! isset( $_GET['ticket_id'] ) ) {
			return;
		}
		$this->ticket_id = absint( $_GET['ticket_id'] );

		$this->ticket = tribe_tickets()->by( 'id', $this->ticket_id )->first();
		if ( empty( $this->ticket ) ) {
			return;
		}

		$this->post = tribe_events_get_ticket_event( $this->ticket_id );
		if ( empty( $this->post ) ) {
			return;
		}

		if ( isset( $_GET['success'] ) ) {
			tribe_notice(
				'attendee-information-success',
				array( $this, 'success_notice' ),
				array( 'type' => 'success' )
			);
		}

		$this->maybe_handle_submission();
	}

	/**
	 * Show a success notice after save!
	 *
	 * @since 4.9
	 *
	 * @return string success message
	 */
	public function success_notice() {
		$link    = '<a href="' . get_edit_post_link( $this->post->ID, 'raw' ) . '">' . __( 'return to the content editor', 'event-tickets' ) . '</a>';
		$notice  = '<div class="success"><p>';
		$notice .= sprintf( __( 'Attendee Registration fields saved. Make additional changes or %1$s', 'event-tickets' ), $link );
		$notice .= '</p></div>';

		return $notice;
	}

	/**
	 * output for attendee information metabox
	 *
	 * @since 4.9
	 *
	 * @param  $post_id post id for the event
	 *
	 */
	public function render() {
		if ( empty( $this->ticket ) || empty( $this->post ) ) {
			wp_die();
		}

		?>
		<style>
		.postbox {
			padding: 1rem;
		}

		.accordion-header.tribe_attendee_meta {
			display:none;
		}
		</style>

		<div id="poststuff"><div class="inside postbox">
			<a href="<?php echo get_edit_post_link( $this->post->ID, 'raw' );?>">&laquo; <?php echo __( 'Back to Editor', 'event-tickets' ) ?></a>
			<form id="event-tickets-attendee-information" action="<?php echo esc_url( $this->url() ); ?>" method="post">
				<input type="hidden" name="ticket_id" value="<?php echo absint( $this->ticket_id );?>" />
				<div id="tribetickets" class="event-tickets-plus-fieldset-table tribe-tickets-plus-fieldset-page">
					<?php
					$meta = Tribe__Tickets_Plus__Main::instance()->meta();
					$meta->accordion_content( $this->post->ID, $this->ticket_id );
					?>
				</div>
				<button class="button-primary" type="submit"><?php echo __( 'Save Changes', 'event-tickets' ) ?></button>
			</form>
		</div></div>

		<script>
			jQuery( function ( $ ) {
				$( '#poststuff' ).on( 'change', 'input, select, textarea', function () {
					if ( null !== window.onbeforeunload ) {
						return;
					}

					window.onbeforeunload = function() {
						return confirm( <?php esc_js( __( 'Are you sure you want to leave this page?', 'event-tickets' ) );?> );
					};
				} );

				$( '#poststuff' ).on( 'click', 'button.button-primary', function() {
					window.onbeforeunload = null;
				} );
			} );
		</script>
		<?php
	}

	/**
	 * handle the saving of attendee registration form
	 *
	 * @since 4.9
	 *
	 * @return null|die
	 */
	private function maybe_handle_submission() {
		if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
			return;
		}

		if ( empty( $this->ticket ) || empty( $this->post ) ) {
			return;
		}

		// mildly concerning.
		$data = $_POST;

		$meta = Tribe__Tickets_Plus__Main::instance()->meta();
		$meta->save_meta( $this->post->ID, $this->ticket, $data );

		wp_redirect( add_query_arg( 'success', 1, $this->url() ) );
		die;
	}

	/**
	 * URL to this standalone page
	 *
	 * @since 4.9
	 *
	 * @return string URL
	 */
	private function url() {
		return admin_url( 'edit.php?post_type=' . $this->post->post_type . '&page=attendee-registration&ticket_id=' . $this->ticket_id );
	}
}
