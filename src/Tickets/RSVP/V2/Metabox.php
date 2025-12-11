<?php
/**
 * Handles RSVP V2 metabox registration and rendering.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

/**
 * Class Metabox.
 *
 * Registers and renders the RSVP metabox on post edit screens.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Metabox {

	/**
	 * The metabox ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const METABOX_ID = 'tec_tickets_rsvp_v2_metabox';

	/**
	 * The nonce action.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'tec_tickets_rsvp_v2_save';

	/**
	 * The nonce field name.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_FIELD = 'tec_tickets_rsvp_v2_nonce';

	/**
	 * Whether the metabox is currently saving or not.
	 * This flag property is used to avoid infinite loop when saving the metabox.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $saving = false;

	/**
	 * Registers the RSVP metabox.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_metabox(): void {
		$post_types = $this->get_supported_post_types();

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				self::METABOX_ID,
				__( 'RSVP', 'event-tickets' ),
				[ $this, 'render_metabox' ],
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Renders the metabox content.
	 *
	 * @since TBD
	 *
	 * @param \WP_Post $post The current post.
	 *
	 * @return void
	 */
	public function render_metabox( \WP_Post $post ): void {
		$post_id = $post->ID;
		$ticket  = tribe( Ticket::class );

		// Get existing RSVP ticket for this post.
		$ticket_ids     = $ticket->get_tickets_for_post( $post_id );
		$has_rsvp       = ! empty( $ticket_ids );
		$rsvp_ticket_id = $has_rsvp ? reset( $ticket_ids ) : 0;

		// Get ticket data if exists.
		$rsvp_data = $this->get_rsvp_data( $rsvp_ticket_id );

		// Nonce field.
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		/**
		 * Fires at the start of the RSVP metabox form.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id        The post ID.
		 * @param int   $rsvp_ticket_id The RSVP ticket ID (0 if none).
		 * @param array $rsvp_data      The RSVP ticket data.
		 */
		do_action( 'tec_event_tickets_rsvp_form__start', $post_id, $rsvp_ticket_id, $rsvp_data );

		$this->render_metabox_content( $post_id, $rsvp_ticket_id, $rsvp_data );

		/**
		 * Fires at the bottom of the RSVP metabox.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id        The post ID.
		 * @param int   $rsvp_ticket_id The RSVP ticket ID (0 if none).
		 * @param array $rsvp_data      The RSVP ticket data.
		 */
		do_action( 'tec_event_tickets_rsvp_bottom', $post_id, $rsvp_ticket_id, $rsvp_data );
	}

	/**
	 * Renders the main metabox content.
	 *
	 * @since TBD
	 *
	 * @param int   $post_id        The post ID.
	 * @param int   $rsvp_ticket_id The RSVP ticket ID.
	 * @param array $rsvp_data      The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_metabox_content( int $post_id, int $rsvp_ticket_id, array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-metabox">
			<input type="hidden" name="tec_tickets_rsvp_v2_post_id" value="<?php echo esc_attr( $post_id ); ?>" />
			<input type="hidden" name="tec_tickets_rsvp_v2_ticket_id" value="<?php echo esc_attr( $rsvp_ticket_id ); ?>" />

		<?php $this->render_enable_toggle( $rsvp_data ); ?>

			<div class="tribe-tickets-rsvp-v2-settings" <?php echo $rsvp_data['enabled'] ? '' : 'style="display:none;"'; ?>>
		<?php
		$this->render_name_field( $rsvp_data );
		$this->render_description_field( $rsvp_data );
		$this->render_capacity_field( $rsvp_data );

		/**
		 * Fires in the main edit section of the RSVP metabox.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id        The post ID.
		 * @param int   $rsvp_ticket_id The RSVP ticket ID.
		 * @param array $rsvp_data      The RSVP ticket data.
		 */
		do_action( 'tec_event_tickets_rsvp_metabox_edit_main', $post_id, $rsvp_ticket_id, $rsvp_data );

		$this->render_show_not_going_field( $rsvp_data );

		/**
		 * Fires after the options section of the RSVP metabox.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id   The post ID.
		 * @param array $rsvp_data The RSVP ticket data.
		 */
		do_action( 'tec_event_tickets_rsvp_post_options', $post_id, $rsvp_data );
		?>
			</div>

			<div class="tribe-tickets-rsvp-v2-bottom-right">
		<?php
		/**
		 * Fires at the bottom right of the RSVP metabox.
		 *
		 * @since TBD
		 *
		 * @param int   $post_id        The post ID.
		 * @param int   $rsvp_ticket_id The RSVP ticket ID.
		 * @param array $rsvp_data      The RSVP ticket data.
		 */
		do_action( 'tec_event_tickets_rsvp_bottom_right', $post_id, $rsvp_ticket_id, $rsvp_data );
		?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the enable/disable toggle.
	 *
	 * @since TBD
	 *
	 * @param array $rsvp_data The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_enable_toggle( array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-enable">
			<label for="tec_tickets_rsvp_v2_enabled">
				<input
					type="checkbox"
					id="tec_tickets_rsvp_v2_enabled"
					name="tec_tickets_rsvp_v2_enabled"
					value="1"
		<?php checked( $rsvp_data['enabled'] ); ?>
				/>
		<?php esc_html_e( 'Enable RSVP', 'event-tickets' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Renders the name field.
	 *
	 * @since TBD
	 *
	 * @param array $rsvp_data The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_name_field( array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-field tribe-tickets-rsvp-v2-field--name">
			<label for="tec_tickets_rsvp_v2_name">
		<?php esc_html_e( 'RSVP Name', 'event-tickets' ); ?>
			</label>
			<input
				type="text"
				id="tec_tickets_rsvp_v2_name"
				name="tec_tickets_rsvp_v2_name"
				value="<?php echo esc_attr( $rsvp_data['name'] ); ?>"
				class="regular-text"
			/>
		</div>
		<?php
	}

	/**
	 * Renders the description field.
	 *
	 * @since TBD
	 *
	 * @param array $rsvp_data The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_description_field( array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-field tribe-tickets-rsvp-v2-field--description">
			<label for="tec_tickets_rsvp_v2_description">
		<?php esc_html_e( 'Description', 'event-tickets' ); ?>
			</label>
			<textarea
				id="tec_tickets_rsvp_v2_description"
				name="tec_tickets_rsvp_v2_description"
				rows="3"
				class="large-text"
			><?php echo esc_textarea( $rsvp_data['description'] ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * Renders the capacity field.
	 *
	 * @since TBD
	 *
	 * @param array $rsvp_data The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_capacity_field( array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-field tribe-tickets-rsvp-v2-field--capacity">
			<label for="tec_tickets_rsvp_v2_capacity">
		<?php esc_html_e( 'Capacity', 'event-tickets' ); ?>
			</label>
			<input
				type="number"
				id="tec_tickets_rsvp_v2_capacity"
				name="tec_tickets_rsvp_v2_capacity"
				value="<?php echo esc_attr( $rsvp_data['capacity'] > 0 ? $rsvp_data['capacity'] : '' ); ?>"
				min="0"
				placeholder="<?php esc_attr_e( 'Unlimited', 'event-tickets' ); ?>"
			/>
			<p class="description">
		<?php esc_html_e( 'Leave blank for unlimited capacity.', 'event-tickets' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Renders the show not going option field.
	 *
	 * @since TBD
	 *
	 * @param array $rsvp_data The RSVP ticket data.
	 *
	 * @return void
	 */
	protected function render_show_not_going_field( array $rsvp_data ): void {
		?>
		<div class="tribe-tickets-rsvp-v2-field tribe-tickets-rsvp-v2-field--show-not-going">
			<label for="tec_tickets_rsvp_v2_show_not_going">
				<input
					type="checkbox"
					id="tec_tickets_rsvp_v2_show_not_going"
					name="tec_tickets_rsvp_v2_show_not_going"
					value="1"
		<?php checked( $rsvp_data['show_not_going'] ); ?>
				/>
		<?php esc_html_e( 'Show "Not Going" option', 'event-tickets' ); ?>
			</label>
			<p class="description">
		<?php esc_html_e( 'Allow guests to indicate they cannot attend.', 'event-tickets' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Handles saving the metabox data.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function save_metabox( int $post_id ): void {
		if ( $this->saving ) {
			// Avoid infinite loops.
			return;
		}

		// Raise the flag indicating there is a save operation going on.
		$this->saving = true;

		try {
			// Verify nonce.
			if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
				return;
			}
			if ( ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ),
				self::NONCE_ACTION 
			) ) {
				return;
			}// Check autosave.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}// Check permissions.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			$ticket    = tribe( Ticket::class );
			$enabled   = isset( $_POST['tec_tickets_rsvp_v2_enabled'] ) && '1' === $_POST['tec_tickets_rsvp_v2_enabled'];
			$ticket_id = isset( $_POST['tec_tickets_rsvp_v2_ticket_id'] ) ? absint( $_POST['tec_tickets_rsvp_v2_ticket_id'] ) : 0;
			if ( $enabled ) {
				$args = [
					'name'           => isset( $_POST['tec_tickets_rsvp_v2_name'] )
						? sanitize_text_field( wp_unslash( $_POST['tec_tickets_rsvp_v2_name'] ) )
						: __( 'RSVP', 'event-tickets' ),
					'description'    => isset( $_POST['tec_tickets_rsvp_v2_description'] )
						? sanitize_textarea_field( wp_unslash( $_POST['tec_tickets_rsvp_v2_description'] ) )
						: '',
					'capacity'       => isset( $_POST['tec_tickets_rsvp_v2_capacity'] ) && '' !== $_POST['tec_tickets_rsvp_v2_capacity']
						? absint( $_POST['tec_tickets_rsvp_v2_capacity'] )
						: - 1,
					'show_not_going' => isset( $_POST['tec_tickets_rsvp_v2_show_not_going'] ),
				];

				if ( $ticket_id > 0 ) {
					// Update existing ticket.
					$ticket->update( $ticket_id, $args );
				} else {
					// Create new ticket.
					$ticket_id = $ticket->create( $post_id, $args );
				}

				if ( ! is_wp_error( $ticket_id ) && $ticket_id > 0 ) {
					/**
					 * Fires after an RSVP V2 ticket is saved.
					 *
					 * @since TBD
					 *
					 * @param int   $ticket_id The ticket ID.
					 * @param int   $post_id   The post ID.
					 * @param array $args      The ticket arguments.
					 * @param bool  $is_new    Whether this is a new ticket.
					 */
					do_action(
						'tec_tickets_rsvp_v2_saved',
						$ticket_id,
						$post_id,
						$args,
						0 === absint( $_POST['tec_tickets_rsvp_v2_ticket_id'] ) 
					);
				}
			} elseif ( $ticket_id > 0 ) {
				// Disabled - delete the ticket.
				$ticket->delete( $ticket_id );
			}
		} finally {
			/*
			 * Code in this block will be executed before the function returns or throws.
			 * In any case, we're not saving anymore and we can lower the flag.
			 */
			$this->saving = false;
		}
	}

	/**
	 * Gets RSVP data for a ticket.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array The RSVP data.
	 */
	protected function get_rsvp_data( int $ticket_id ): array {
		$defaults = [
			'enabled'        => false,
			'name'           => __( 'RSVP', 'event-tickets' ),
			'description'    => '',
			'capacity'       => -1,
			'show_not_going' => true,
		];

		if ( 0 === $ticket_id ) {
			return $defaults;
		}

		$ticket_post = get_post( $ticket_id );

		if ( ! $ticket_post ) {
			return $defaults;
		}

		$meta = tribe( Meta::class );

		return [
			'enabled'        => true,
			'name'           => $ticket_post->post_title,
			'description'    => $ticket_post->post_excerpt,
			'capacity'       => tribe( Ticket::class )->get_available( $ticket_id ),
			'show_not_going' => $meta->get_show_not_going( $ticket_id ),
		];
	}

	/**
	 * Gets the post types that support RSVP.
	 *
	 * @since TBD
	 *
	 * @return string[] Array of post type slugs.
	 */
	public function get_supported_post_types(): array {
		/**
		 * Filters the post types that support RSVP V2.
		 *
		 * @since TBD
		 *
		 * @param string[] $post_types The post types.
		 */
		return apply_filters( 'tec_tickets_rsvp_v2_supported_post_types', tribe_get_option( 'ticket-enabled-post-types', [] ) );
	}
}
