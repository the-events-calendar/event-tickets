<?php
	echo '<script type="text/html" id="tmpl-tribe-registration-- " . esc_attr($ticket[\'id\'])>';
		$ticket_qty = $ticket['qty'];
		$post           = get_post( $ticket['id'] );
		?>
		<h3 class="tribe-common-h5 tribe-common-h5--min-medium tribe-common-h--alt tribe-ticket__heading "><?php echo get_the_title( $post->ID ); ?></h3>
		<?php // go through each attendee ?>
		<?php while ( 0 < $ticket_qty ) : ?>
			<?php
				/**
				* @var Tribe__Tickets_Plus__Meta $meta
				*/
				$fields     = $meta->get_meta_fields_by_ticket( $post->ID );
				$saved_meta = $storage->get_meta_data_for( $post->ID );

				$args = array(
					'event_id'   => $event_id,
					'ticket'     => $post,
					'fields'     => $fields,
					'saved_meta' => $saved_meta,
				);


				$this->template( 'registration-js/attendees/fields', $args );
				$ticket_qty--;
			?>
		<?php endwhile; ?>
	</script>
