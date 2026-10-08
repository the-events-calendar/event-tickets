/**
 * Entry point of the Ticket block script: registers the rule store and the hooks that carry the rule through the
 * ticket REST requests.
 *
 * @since TBD
 */

// First, so every date the script resolves reads current zone data.
import './zone-data';
import './store';
import './filters';
