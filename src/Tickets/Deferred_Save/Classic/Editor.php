<?php
/**
 * Prints what the classic editor needs to stage ticket changes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use TEC\Tickets\Deferred_Save\Classic_Save;

/**
 * Class Editor.
 *
 * Hooked by the feature Controller at the end of the tickets metabox, prints the nonce the classic
 * entry point requires, the container the staging module writes its hidden fields into (a sibling of
 * the panels, so a panel refresh does not wipe it), and the row templates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
final class Editor {
	/**
	 * The row templates.
	 *
	 * @since TBD
	 *
	 * @var Row_Template
	 */
	private Row_Template $row_template;

	/**
	 * Editor constructor.
	 *
	 * @since TBD
	 *
	 * @param Row_Template $row_template The row templates.
	 */
	public function __construct( Row_Template $row_template ) {
		$this->row_template = $row_template;
	}

	/**
	 * Prints the nonce, the container and the templates.
	 *
	 * @since TBD
	 *
	 * @param int|string $post_id The ID of the post the metabox is for.
	 *
	 * @return void
	 */
	public function print_fields( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! $post_id ) {
			return;
		}

		$html  = Classic_Save::nonce_field();
		$html .= sprintf(
			'<div id="tec-tickets-deferred-save" class="tec-tickets-deferred-save" data-post-id="%d" aria-live="polite"></div>',
			$post_id
		);
		$html .= $this->row_template->render( $post_id );

		// phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped -- The nonce field comes from WordPress, the container is built from an integer, and the admin view escapes its own output.
		echo $html;
	}
}
