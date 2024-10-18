<?php
/**
 * Fee Edit Screen for Order Modifiers.
 *
 * This file handles the HTML form rendering for editing or creating a Fee.
 * The form includes fields for Fee name, code, discount type, amount, status, and Fee limit.
 * It also includes a nonce field for security.
 *
 * @since TBD
 *
 * @var string $order_modifier_display_name The Fee name (display name).
 * @var string $order_modifier_slug The Fee code (slug).
 * @var string $order_modifier_sub_type The discount type (percentage/flat).
 * @var int    $order_modifier_fee_amount_cents The amount (in cents).
 * @var string $order_modifier_status The status of the Fee (active, inactive, draft).
 * @var int    $order_modifier_fee_limit The Fee limit.
 * @var string $order_modifier_apply_to What the fee is applied to (All, Per, Organizer, Venue)
 *
 * @package TEC\Tickets\Order_Modifiers
 */

?>
<div class="wrap">
	<div class="form-wrap">
		<h1> <?php esc_html_e( 'New Fee', 'event-tickets' ); ?> </h1>
		<form method="post" action="" id="tec-settings-form">
			<div class="tribe-settings-form-wrap">

				<?php wp_nonce_field( 'order_modifier_save_action', 'order_modifier_save_action' ); ?>

				<div class="form-field form-required">
					<label for="order_modifier_fee_name"><?php esc_html_e( 'Fee Name', 'event-tickets' ); ?></label>
					<input type="text" name="order_modifier_fee_name" id="order_modifier_fee_name" class="tribe-field"
						   value="<?php echo esc_attr( $order_modifier_display_name ?? '' ); ?>">
				</div>


				<input type="hidden" name="order_modifier_slug" id="order_modifier_slug" class="tribe-field"
					   value="<?php echo esc_attr( $order_modifier_slug ?? '' ); ?>">
				<div class="form-field form-required">
					<label for="order_modifier_sub_type"><?php esc_html_e( 'Fee Type', 'event-tickets' ); ?></label>
					<select name="order_modifier_sub_type" id="order_modifier_sub_type">
						<option
							value="percent" <?php selected( $order_modifier_sub_type ?? '', 'percent' ); ?>><?php esc_html_e( 'Percent', 'event-tickets' ); ?></option>
						<option
							value="flat" <?php selected( $order_modifier_sub_type ?? '', 'flat' ); ?>><?php esc_html_e( 'Flat', 'event-tickets' ); ?></option>
					</select>
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_amount"><?php esc_html_e( 'Amount', 'event-tickets' ); ?></label>
					<input type="text" name="order_modifier_amount" id="order_modifier_amount" class="tribe-field"
						   value="<?php echo esc_attr( $order_modifier_fee_amount_cents ); ?>">
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_status"><?php esc_html_e( 'Status', 'event-tickets' ); ?></label>
					<select name="order_modifier_status" id="order_modifier_status">
						<option
							value="active" <?php selected( $order_modifier_status ?? '', 'active' ); ?>><?php esc_html_e( 'Active', 'event-tickets' ); ?></option>
						<option
							value="inactive" <?php selected( $order_modifier_status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'event-tickets' ); ?></option>
						<option
							value="draft" <?php selected( $order_modifier_status ?? '', 'draft' ); ?>><?php esc_html_e( 'Draft', 'event-tickets' ); ?></option>
					</select>
				</div>

				<div class="form-field form-required">
					<label
						for="order_modifier_fee_limit"><?php esc_html_e( 'Apply fee to', 'event-tickets' ); ?></label>
					<select name="order_modifier_apply_to" id="order_modifier_apply_to">
						<option value="per" <?php selected( $order_modifier_apply_to, 'per' ); ?>>
							<?php esc_html_e( 'Set per ticket', 'event-tickets' ); ?>
						</option>
						<option value="all" <?php selected( $order_modifier_apply_to, 'all' ); ?>>
							<?php esc_html_e( 'All tickets', 'event-tickets' ); ?>
						</option>
						<option value="venue" <?php selected( $order_modifier_apply_to, 'venue' ); ?>>
							<?php esc_html_e( 'Venue', 'event-tickets' ); ?>
						</option>
						<option value="organizer" <?php selected( $order_modifier_apply_to, 'organizer' ); ?>>
							<?php esc_html_e( 'Organizer', 'event-tickets' ); ?>
						</option>
					</select>
					<p>Select a group to apply this fee to tickets automatically. This can be overridden on a per ticket
						basis during ticket creation.</p>
				</div>

				<?php
				// @todo redscar - This needs to be refactored.
				$posts = get_posts(
					[
						'post_type'      => 'tribe_organizer',  // Or 'tribe_venue' for venues.
						'orderby'        => 'title',
						'order'          => 'ASC',
						'posts_per_page' => -1,
					]
				);
				?>

				<select name="organizer_list">
					<option value=""><?php esc_html_e( 'Select an organizer', 'event-tickets' ); ?></option>
					<?php foreach ( $posts as $post ) : ?>
						<option value="<?php echo esc_attr( $post->ID ); ?>">
							<?php echo esc_html( $post->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<?php
				// @todo redscar - This needs to be refactored.
				$venues = get_posts(
					[
						'post_type'      => 'tribe_venue',
						'orderby'        => 'title',
						'order'          => 'ASC',
						'posts_per_page' => -1,
					]
				);
				?>

				<select name="venue_list">
					<option value=""><?php esc_html_e( 'Select a venue', 'event-tickets' ); ?></option>
					<?php foreach ( $venues as $venue ) : ?>
						<option value="<?php echo esc_attr( $venue->ID ); ?>">
							<?php echo esc_html( $venue->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>



				<p class="submit">
					<input
						type="submit"
						id="order_modifier_form_save"
						class="button-primary"
						name="order_modifier_form_save"
						value="<?php echo esc_attr__( 'Save Fee', 'event-tickets' ); ?>"
					/>
				</p>
			</div>
		</form>
	</div>
</div>
