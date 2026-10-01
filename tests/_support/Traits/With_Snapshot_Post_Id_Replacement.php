<?php

namespace Tribe\Tickets\Test\Traits;

/**
 * Replace dynamic post/ticket IDs with snapshot placeholders.
 */
trait With_Snapshot_Post_Id_Replacement {

	/**
	 * Replace dynamic post/ticket IDs with snapshot placeholders.
	 *
	 * Every replacement is anchored to a static prefix/suffix taken from the
	 * actual markup (e.g. `data-rsvp-id="`, `tribe_tickets[`,
	 * `quantity_`) so a small, common ID (e.g. `1` or `2`) cannot match
	 * unrelated numbers elsewhere in the snapshot (quantities, array
	 * indices, counters, etc).
	 *
	 * There is intentionally no generic catch-all replacement. If a new
	 * template introduces the ID in a context not listed here, the
	 * snapshot assertion will fail — add the new exact pattern rather than
	 * widening one of the existing ones.
	 *
	 * @param string                   $html
	 * @param array<int|string,string> $placeholders Map of ID => placeholder token.
	 *
	 * @return string
	 */
	protected function replace_snapshot_post_ids( string $html, array $placeholders ): string {
		foreach ( $placeholders as $id => $placeholder ) {
			$id = (string) $id;

			$contextual = [
				'event[' . $id . ']'               => 'event[' . $placeholder . ']',
				'event' . $id                       => 'event' . $placeholder,
				'data-post-id="' . $id . '"'        => 'data-post-id="' . $placeholder . '"',
				'data-ticket-id="' . $id . '"'      => 'data-ticket-id="' . $placeholder . '"',
				'data-rsvp-id="' . $id . '"'        => 'data-rsvp-id="' . $placeholder . '"',
				'data-product-id="' . $id . '"'     => 'data-product-id="' . $placeholder . '"',
				'tribe_tickets[' . $id . ']'        => 'tribe_tickets[' . $placeholder . ']',
				'[ticket_id]" value="' . $id . '"'  => '[ticket_id]" value="' . $placeholder . '"',
				'_' . $id . '_'                     => '_' . $placeholder . '_',
				'for ' . $id                        => 'for ' . $placeholder,
				'post-' . $id                       => 'post-' . $placeholder,
				'quantity_' . $id                   => 'quantity_' . $placeholder,
			];

			$html = str_replace( array_keys( $contextual ), array_values( $contextual ), $html );

			$html = preg_replace(
				'/-' . preg_quote( $id, '/' ) . '(?!\d)/',
				'-' . $placeholder,
				$html
			);
		}

		return $html;
	}
}
