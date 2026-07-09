<?php

namespace Tribe\Tickets\Test\Traits;

/**
 * Replace dynamic post/ticket IDs with snapshot placeholders.
 */
trait With_Snapshot_Post_Id_Replacement {

	/**
	 * Replace dynamic post/ticket IDs with snapshot placeholders.
	 *
	 * Uses contextual replacements and word boundaries to avoid corrupting
	 * unrelated substrings (e.g. `a11y`, `event12`).
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
				'event[' . $id . ']'              => 'event[' . $placeholder . ']',
				'event' . $id                     => 'event' . $placeholder,
				'data-post-id="' . $id . '"'     => 'data-post-id="' . $placeholder . '"',
				'data-ticket-id="' . $id . '"'   => 'data-ticket-id="' . $placeholder . '"',
				'data-rsvp-id="' . $id . '"'      => 'data-rsvp-id="' . $placeholder . '"',
				'data-product-id="' . $id . '"'  => 'data-product-id="' . $placeholder . '"',
				'[' . $id . ']'                  => '[' . $placeholder . ']',
				'-' . $id                        => '-' . $placeholder,
				'_' . $id . '_'                  => '_' . $placeholder . '_',
				'for ' . $id                     => 'for ' . $placeholder,
				'post-' . $id                    => 'post-' . $placeholder,
				'quantity_' . $id                => 'quantity_' . $placeholder,
			];

			$html = str_replace( array_keys( $contextual ), array_values( $contextual ), $html );

			$html = preg_replace(
				'/\b' . preg_quote( $id, '/' ) . '\b/',
				$placeholder,
				$html
			);
		}

		return $html;
	}
}
