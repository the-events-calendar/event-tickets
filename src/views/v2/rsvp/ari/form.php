<?php
/**
 * Block: RSVP
 * ARI Form
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/rsvp/ari/form.php
 *
 * See more documentation about our Blocks Editor templating system.
 *
 * @link {INSERT_ARTICLE_LINK_HERE}
 *
 * @var Tribe__Tickets__Ticket_Object $rsvp The rsvp ticket object.
 * @var WP_Post|int $post_id The post object or ID.
 *
 * @since TBD
 *
 * @version TBD
 */

if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
	return;
}

$meta   = Tribe__Tickets_Plus__Main::instance()->meta();
$fields = $meta->get_meta_fields_by_ticket( $rsvp->ID );

?>
<div class="tribe-tickets__rsvp-ar-form">

	<?php $this->template( 'v2/rsvp/ari/form/title', [ 'rsvp' => $rsvp ] ); ?>

	<div class="tribe-tickets__form">
		<?php foreach ( $fields as $field ) : ?>
			<?php
				$args = [
					'post_id'    => $post_id,
					'ticket'     => $rsvp,
					'field'      => $field,
					'value'      => null,
					'saved_meta' => [],
				];

				$this->template( 'v2/components/fields/' . $field->type, $args );
				?>
		<?php endforeach; ?>
	</div>

	<?php $this->template( 'v2/rsvp/ari/form/buttons', [ 'rsvp' => $rsvp ] ); ?>

</div>
