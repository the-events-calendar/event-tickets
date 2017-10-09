<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View {

	/**
	 * @var int
	 */
	protected $post_id;

	/**
	 * Tribe__Tickets__Commerce__PayPal__Orders__Tabbed_View constructor.
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	public function render() {
		echo 'Hello there!';
	}
}