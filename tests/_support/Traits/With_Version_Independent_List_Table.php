<?php

namespace Tribe\Tickets\Test\Traits;

use DOMDocument;
use DOMXPath;

/**
 * Removes, from a list table's HTML, the navigation WordPress draws itself.
 *
 * WordPress changes that markup between versions: 6.7 named the bulk action buttons, 6.9 stopped drawing the
 * pagination and the bottom navigation of an empty table. CI runs several WordPress versions, so a snapshot that
 * holds it passes on one version only. The plugin's own navigation items stay in the snapshot.
 */
trait With_Version_Independent_List_Table {
	/**
	 * Returns the HTML without the pagination, the bottom navigation and the bulk action buttons' name.
	 *
	 * @param string $html The list table's HTML.
	 *
	 * @return string The HTML.
	 */
	protected function remove_core_list_table_navigation( string $html ): string {
		$document = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="utf-8"?><div id="snapshot-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$xpath = new DOMXPath( $document );
		$nodes = $xpath->query(
			'//div[contains(concat(" ", normalize-space(@class), " "), " tablenav-pages ")]'
			. ' | //div[contains(concat(" ", normalize-space(@class), " "), " tablenav ") and contains(concat(" ", normalize-space(@class), " "), " bottom ")]'
		);
		foreach ( iterator_to_array( $nodes ) as $node ) {
			$node->parentNode->removeChild( $node );
		}

		foreach ( $xpath->query( '//input[@id="doaction" or @id="doaction2"]' ) as $button ) {
			$button->removeAttribute( 'name' );
		}

		$html = '';
		foreach ( $document->getElementById( 'snapshot-root' )->childNodes as $child ) {
			$html .= $document->saveHTML( $child );
		}

		return $html;
	}
}
