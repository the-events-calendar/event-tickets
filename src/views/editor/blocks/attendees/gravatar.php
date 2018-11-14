<?php
/**
 * This template renders the attendee gravatar
 *
 * @version TBD
 *
 */
echo get_avatar( $attendee['purchaser_email'], 60, '', $attendee['purchaser_name'] );