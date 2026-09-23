<?php
/**
 * Prints what the classic editor needs to stage ticket changes.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */

namespace TEC\Tickets\Deferred_Save\Classic;

use TEC\Common\Contracts\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Deferred_Save\Classic_Save;
use TEC\Tickets\Deferred_Save\Controller;

/**
 * Class Editor.
 *
 * At the end of the tickets metabox, and only for a post that uses deferred save, prints the nonce
 * the classic entry point requires, the container the staging module writes its hidden fields into
 * (a sibling of the panels, so a panel refresh does not wipe it), and the row templates. On a post
 * that does not use deferred save nothing is printed, so the metabox is byte-identical to today.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save\Classic
 */
class Editor extends Controller_Contract {
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
	 * @param Container    $container    The container.
	 * @param Row_Template $row_template The row templates.
	 */
	public function __construct( Container $container, Row_Template $row_template ) {
		parent::__construct( $container );
		$this->row_template = $row_template;
	}

	/**
	 * Hooks the end of the tickets metabox.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_tickets_metabox_end', [ $this, 'print_fields' ] );
	}

	/**
	 * Unhooks the metabox.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_tickets_metabox_end', [ $this, 'print_fields' ] );
	}

	/**
	 * Prints the nonce, the container and the templates when the post uses deferred save.
	 *
	 * @since TBD
	 *
	 * @param int|string $post_id The ID of the post the metabox is for.
	 *
	 * @return void
	 */
	public function print_fields( $post_id ): void {
		$post_id = (int) $post_id;

		if ( ! $post_id || ! $this->container->get( Controller::class )->uses_deferred_save( $post_id ) ) {
			return;
		}

		$html  = Classic_Save::nonce_field();
		$html .= sprintf(
			'<div id="tec-tickets-deferred-save" class="tec-tickets-deferred-save" data-post-id="%d" aria-live="polite"></div>',
			$post_id
		);
		$html .= $this->row_template->render();

		// phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped -- The nonce field comes from WordPress, the container is built from an integer, and the admin view escapes its own output.
		echo $html;
	}
}
