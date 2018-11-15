<?php
/**
 * This template renders the attendees block description
 *
 * @version TBD
 *
 */
$post_id         = $this->get( 'post_id' );
$attendees_total = count( $attendees );
$message         = _n( 'One person is attending %2$s', '%d people are attending %s', $attendees_total, 'events-gutenberg' );
?>
<p><?php echo esc_html( sprintf( $message, $attendees_total, get_the_title( $post_id ) ) ); ?></p>