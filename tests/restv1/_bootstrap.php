<?php

/**
 * Remove the deprecated 'tribe_tickets_plugin_loaded' hook to prevent
 * deprecated warnings during REST API tests that interfere with response headers.
 * The replacement hook 'tec_tickets_fully_loaded' should be used instead.
 *
 * @since TBD
 */
remove_all_actions( 'tribe_tickets_plugin_loaded' );
