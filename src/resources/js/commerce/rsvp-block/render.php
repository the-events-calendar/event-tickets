<?php
/**
 * RSVP Block Frontend Rendering
 *
 * @since TBD
 *
 * @param array    $attributes The array of attributes for this block.
 * @param string   $content    Rendered block output. ie. <InnerBlocks.Content />.
 * @param WP_Block $block      The instance of the WP_Block class that represents the block being rendered.
 */

// Exit early if no RSVP ID is set.
if ( empty( $attributes['rsvpId'] ) ) {
	return '';
}

$wrapper_attributes = get_block_wrapper_attributes( [
	'class' => 'tec-rsvp-block-frontend',
] );

// Get RSVP data.
$rsvp_id = $attributes['rsvpId'];
$limit = ! empty( $attributes['limit'] ) ? intval( $attributes['limit'] ) : null;
$going_count = isset( $attributes['goingCount'] ) ? intval( $attributes['goingCount'] ) : 0;
$not_going_count = isset( $attributes['notGoingCount'] ) ? intval( $attributes['notGoingCount'] ) : 0;
$show_not_going = ! empty( $attributes['showNotGoingOption'] );

// Calculate remaining spots.
$remaining = null;
if ( $limit !== null && $limit > 0 ) {
	$remaining = max( 0, $limit - $going_count );
}

// Check if RSVP window is open.
$now = current_time( 'timestamp' );
$is_open = true;

if ( ! empty( $attributes['openRsvpDate'] ) ) {
	$open_datetime = $attributes['openRsvpDate'] . ' ' . ( $attributes['openRsvpTime'] ?? '00:00:00' );
	$open_timestamp = strtotime( $open_datetime );
	if ( $open_timestamp > $now ) {
		$is_open = false;
	}
}

if ( ! empty( $attributes['closeRsvpDate'] ) ) {
	$close_datetime = $attributes['closeRsvpDate'] . ' ' . ( $attributes['closeRsvpTime'] ?? '00:00:00' );
	$close_timestamp = strtotime( $close_datetime );
	if ( $close_timestamp < $now ) {
		$is_open = false;
	}
}

// Check if RSVP is full.
$is_full = ( $limit !== null && $remaining === 0 );
?>

<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<div class="tec-rsvp-frontend">
		<div class="tec-rsvp-frontend__header">
			<h3 class="tec-rsvp-frontend__title">
				<?php esc_html_e( 'RSVP', 'event-tickets' ); ?>
			</h3>
			
			<div class="tec-rsvp-frontend__stats">
				<span class="tec-rsvp-frontend__stat tec-rsvp-frontend__stat--going">
					<span class="tec-rsvp-frontend__stat-label"><?php esc_html_e( 'Going:', 'event-tickets' ); ?></span>
					<span class="tec-rsvp-frontend__stat-value"><?php echo esc_html( $going_count ); ?></span>
				</span>
				
				<?php if ( $limit !== null && $limit > 0 ) : ?>
					<span class="tec-rsvp-frontend__stat tec-rsvp-frontend__stat--remaining">
						<span class="tec-rsvp-frontend__stat-label"><?php esc_html_e( 'Remaining:', 'event-tickets' ); ?></span>
						<span class="tec-rsvp-frontend__stat-value"><?php echo esc_html( $remaining ); ?></span>
					</span>
				<?php endif; ?>
				
				<?php if ( $show_not_going ) : ?>
					<span class="tec-rsvp-frontend__stat tec-rsvp-frontend__stat--not-going">
						<span class="tec-rsvp-frontend__stat-label"><?php esc_html_e( 'Not Going:', 'event-tickets' ); ?></span>
						<span class="tec-rsvp-frontend__stat-value"><?php echo esc_html( $not_going_count ); ?></span>
					</span>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="tec-rsvp-frontend__form">
			<?php if ( ! $is_open ) : ?>
				<p class="tec-rsvp-frontend__message tec-rsvp-frontend__message--closed">
					<?php esc_html_e( 'RSVPs are currently closed for this event.', 'event-tickets' ); ?>
				</p>
			<?php elseif ( $is_full ) : ?>
				<p class="tec-rsvp-frontend__message tec-rsvp-frontend__message--full">
					<?php esc_html_e( 'This RSVP is full.', 'event-tickets' ); ?>
				</p>
			<?php else : ?>
				<form class="tec-rsvp-frontend__rsvp-form" data-rsvp-id="<?php echo esc_attr( $rsvp_id ); ?>">
					<?php if ( is_user_logged_in() ) : ?>
						<p class="tec-rsvp-frontend__user-info">
							<?php
							$current_user = wp_get_current_user();
							printf(
								/* translators: %s: User display name */
								esc_html__( 'Logged in as %s', 'event-tickets' ),
								'<strong>' . esc_html( $current_user->display_name ) . '</strong>'
							);
							?>
						</p>
					<?php else : ?>
						<div class="tec-rsvp-frontend__field">
							<label for="rsvp-name"><?php esc_html_e( 'Name', 'event-tickets' ); ?> <span class="required">*</span></label>
							<input type="text" id="rsvp-name" name="name" required>
						</div>
						
						<div class="tec-rsvp-frontend__field">
							<label for="rsvp-email"><?php esc_html_e( 'Email', 'event-tickets' ); ?> <span class="required">*</span></label>
							<input type="email" id="rsvp-email" name="email" required>
						</div>
					<?php endif; ?>
					
					<div class="tec-rsvp-frontend__actions">
						<button type="submit" class="tec-rsvp-frontend__button tec-rsvp-frontend__button--going">
							<?php esc_html_e( 'Going', 'event-tickets' ); ?>
						</button>
						
						<?php if ( $show_not_going ) : ?>
							<button type="button" class="tec-rsvp-frontend__button tec-rsvp-frontend__button--not-going">
								<?php esc_html_e( "Can't Go", 'event-tickets' ); ?>
							</button>
						<?php endif; ?>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>