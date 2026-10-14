<?php

namespace Tribe\Tickets\Test\Traits;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Renders the classic ticket form, reads it, and serializes it the way `tickets.js` sends it.
 */
trait Classic_Ticket_Form {
	/**
	 * @param string $post_type The post type to sell tickets on.
	 */
	protected function enable_tickets_on( string $post_type ): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = $post_type;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @param int         $post_id     The ticketed post ID.
	 * @param int|null    $ticket_id   The ticket post ID, or `null` for a new ticket.
	 * @param string|null $ticket_type The ticket type, or `null` to read it from the ticket.
	 *
	 * @return string The HTML of the classic ticket edit panel.
	 */
	protected function render_ticket_panel( int $post_id, ?int $ticket_id = null, ?string $ticket_type = null ): string {
		return tribe( 'tickets.metabox' )->get_panels( $post_id, $ticket_id, $ticket_type )['ticket'];
	}

	/**
	 * @param int      $post_id   The ticketed post ID.
	 * @param int|null $ticket_id The ticket post ID, or `null` for a new ticket.
	 *
	 * @return DOMXPath The classic ticket edit panel, ready to query.
	 */
	protected function render_ticket_form( int $post_id, ?int $ticket_id = null ): DOMXPath {
		$document = new DOMDocument();
		// The panel is an HTML fragment, which libxml warns about.
		libxml_use_internal_errors( true );
		$document->loadHTML( '<?xml encoding="UTF-8">' . $this->render_ticket_panel( $post_id, $ticket_id ) );
		libxml_clear_errors();

		return new DOMXPath( $document );
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string   $id   The element id.
	 *
	 * @return DOMElement The element.
	 */
	protected function get_element( DOMXPath $form, string $id ): DOMElement {
		$element = $form->query( "//*[@id='{$id}']" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $element, "No element with the id {$id}." );

		return $element;
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string   $id   The select id.
	 *
	 * @return string The value of the selected option.
	 */
	protected function get_selected_value( DOMXPath $form, string $id ): string {
		$option = $form->query( "//select[@id='{$id}']/option[@selected]" )->item( 0 );
		$this->assertInstanceOf( DOMElement::class, $option, "No option selected in {$id}." );

		return $option->getAttribute( 'value' );
	}

	/**
	 * @param DOMXPath $form The ticket form.
	 * @param string   $id   The select id.
	 *
	 * @return string[] The labels of the select's options.
	 */
	protected function get_option_labels( DOMXPath $form, string $id ): array {
		$labels = [];

		foreach ( $form->query( "//select[@id='{$id}']/option" ) as $option ) {
			$labels[] = trim( $option->textContent );
		}

		return $labels;
	}

	/**
	 * @param DOMElement $element An element inside a dependent block.
	 *
	 * @return DOMElement The closest ancestor shown and hidden by `dependency.js`.
	 */
	protected function get_dependent( DOMElement $element ): DOMElement {
		for ( $node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode ) {
			if ( in_array( 'tribe-dependent', explode( ' ', $node->getAttribute( 'class' ) ), true ) ) {
				return $node;
			}
		}

		$this->fail( "{$element->getAttribute( 'id' )} is not inside a dependent block." );
	}

	/**
	 * Reads the form fields the way jQuery's `serialize()` does in `tickets.js`: named, enabled fields only.
	 *
	 * @param DOMXPath $form The ticket form.
	 *
	 * @return array<int,array{0: string, 1: string}> The submitted name and value pairs, in document order.
	 */
	protected function serialize_form( DOMXPath $form ): array {
		$fields = [];

		foreach ( $form->query( "//*[@id='tribe_panel_edit']//*[self::input or self::select or self::textarea][@name][not(@disabled)]" ) as $field ) {
			$type = strtolower( $field->getAttribute( 'type' ) );

			if ( in_array( $type, [ 'submit', 'button', 'reset', 'image', 'file' ], true ) ) {
				continue;
			}

			if ( in_array( $type, [ 'checkbox', 'radio' ], true ) ) {
				if ( $field->hasAttribute( 'checked' ) ) {
					// A browser sends `on` for a checked box without a value.
					$fields[] = [ $field->getAttribute( 'name' ), $field->hasAttribute( 'value' ) ? $field->getAttribute( 'value' ) : 'on' ];
				}
				continue;
			}

			if ( 'select' === $field->nodeName ) {
				$selected = $form->query( './option[@selected]', $field )->item( 0 ) ?? $form->query( './option', $field )->item( 0 );
				if ( $selected instanceof DOMElement ) {
					$fields[] = [ $field->getAttribute( 'name' ), $selected->getAttribute( 'value' ) ];
				}
				continue;
			}

			$fields[] = [ $field->getAttribute( 'name' ), 'textarea' === $field->nodeName ? $field->textContent : $field->getAttribute( 'value' ) ];
		}

		return $fields;
	}

	/**
	 * @param array<int,array{0: string, 1: string}> $fields The name and value pairs; a later pair of the same name wins, as in PHP.
	 *
	 * @return string The fields, URL-encoded as `serialize()` encodes them.
	 */
	protected function to_query_string( array $fields ): string {
		return implode( '&', array_map( static fn( array $field ): string => rawurlencode( $field[0] ) . '=' . rawurlencode( $field[1] ), $fields ) );
	}
}
