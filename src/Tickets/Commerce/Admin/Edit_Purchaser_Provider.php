<?php
/**
 * Edit Purchaser modal.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */

namespace TEC\Tickets\Commerce\Admin;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Commerce\Flag_Actions\Send_Email_Purchase_Receipt;
use Tribe__Template;

/**
 * Class Edit_Purchaser_Provider
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Edit_Purchaser_Provider extends Service_Provider {
	/**
	 * @inheritdoc
	 */
	public function register() {
		if ( is_admin() ) {
			$this->register_hooks();
		}
	}

	/**
	 * Adds filters for edit purchaser.
	 *
	 * @since TBD
	 */
	public function register_hooks() {
		add_filter(
			'tribe_template_before_include_html:tickets/admin-views/commerce/orders/single/order-details-metabox',
			[ $this, 'render_modal' ],
			10,
			5
		);
		add_action( 'wp_ajax_tec_commerce_purchaser_edit', [ $this, 'ajax_handle_request' ] );
	}

	/**
	 * Will apply field updates to the order.
	 *
	 * @since TBD
	 *
	 * @param numeric $post_id The order post ID.
	 * @param array   $fields The fields to apply to the purchaser.
	 *
	 * @return bool
	 */
	public function update_purchaser( $post_id, array $fields ): bool {
		if ( empty( $post_id ) ) {
			return false;
		}

		$update       = [];
		$allowed_keys = [
			'purchaser_email',
			'purchaser_first_name',
			'purchaser_last_name',
		];
		foreach ( $allowed_keys as $key ) {
			if ( ! empty( $fields[ $key ] ) ) {
				$update[ $key ] = $fields[ $key ];
			}
		}

		if ( empty( $update ) ) {
			return false;
		}

		try {
			return (bool) tec_tc_orders()->by_args(
				[
					'status' => 'any',
					'id'     => $post_id,
				]
			)->set_args( $update )->save();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Handles the ajax callback for `wp_ajax_tec_commerce_purchaser_edit`.
	 *
	 * @since TBD
	 */
	public function ajax_handle_request() {
		// Validate nonce.
		check_ajax_referer( 'tec_commerce_purchaser_edit', '_nonce' );

		$method = sanitize_text_field( $_SERVER['REQUEST_METHOD'] ?? '' );

		switch ( $method ) {
			case 'POST':
				// Deal with "full name" field into pieces.
				$name       = trim( sanitize_text_field( $_POST['name'] ?? '' ) );
				$parts      = explode( ' ', $name );
				$first_name = $parts[0] ?? '';
				$last_name  = $parts[1] ?? '';

				// Sanitize other fields.
				$email      = sanitize_email( $_POST['email'] ?? '' );
				$post_id    = sanitize_text_field( $_POST['ID'] ?? '' );
				$send_email = ! empty( $_POST['send_email'] );

				if ( ! is_email( $email ) ) {
					wp_send_json_error(
						_x(
							'Invalid email address',
							'When the provided purchaser email address is invalid.',
							'event-tickets'
						)
					);
					return;
				}

				if ( empty( $name ) ) {
					wp_send_json_error(
						_x(
							'Invalid name',
							'When the provided purchaser name is missing.',
							'event-tickets'
						)
					);
					return;
				}

				// Local database update.
				$updated = $this->update_purchaser(
					$post_id,
					[
						'purchaser_first_name' => $first_name,
						'purchaser_last_name'  => $last_name,
						'purchaser_email'      => $email,
					]
				);

				if ( $updated && $send_email ) {
					$order = tec_tc_get_order( $post_id );
					if ( ! $order ) {
						wp_send_json_error(
							_x(
								'There was an error retrieving details for the email. Please try again later.',
								'When the purchaser get order for the email fails for an unknown reason.',
								'event-tickets'
							)
						);
						return;
					}

					$sent = tribe( Send_Email_Purchase_Receipt::class )->send_for_order( $order );
					if ( ! $sent ) {
						wp_send_json_error(
							_x(
								'There was an error sending the email receipt. Please ensure WordPress is configured for email delivery.',
								'When the purchaser email receipt fails for an unknown reason.',
								'event-tickets'
							)
						);
						return;
					}
				}

				if ( $updated ) {
					wp_send_json_success(
						[
							'name'  => $first_name . ' ' . $last_name,
							'email' => $email,
						]
					);
				} else {
					wp_send_json_error(
						_x(
							'There was an unknown error while updating the purchaser. Please try again later.',
							'When the purchaser update fails for an unknown reason.',
							'event-tickets'
						)
					);
				}
				break;
			case 'GET':
				// Fetch the order.
				$post  = get_post( sanitize_text_field( $_GET['ID'] ?? null ) );
				$order = tec_tc_get_order( $post );

				if ( ! $order ) {
					wp_send_json_error(
						_x(
							'There was an unknown error while retrieving the purchaser. Please try again later.',
							'When the purchaser GET request fails for an unknown reason.',
							'event-tickets'
						)
					);
					return;
				}

				wp_send_json_success( $order->purchaser );
				break;
		}
	}

	/**
	 * Callback that renders the modal with context applied.
	 *
	 * @since TBD
	 *
	 * @param mixed           $html The html param from the filter.
	 * @param string          $file The file path.
	 * @param string          $name The name of the file.
	 * @param Tribe__Template $template The template object.
	 *
	 * @return mixed The passed html.
	 */
	public function render_modal( $html, $file, $name, Tribe__Template $template ) {
		$dialog_view    = tribe( 'dialog.view' );
		$title          = esc_html_x( 'Edit purchaser', 'Edit purchaser modal title.', 'event-tickets' );
		$admin_template = tribe( 'tickets.admin.views' );

		ob_start();
		$dialog_view->render_modal(
			$admin_template->template(
				'src/admin-views/commerce/orders/single/edit-purchaser-modal',
				$template->get_local_values(),
				false
			),
			[
				'id'             => 'edit-purchaser-modal',
				'append_target'  => '#edit-purchaser-modal-container',
				'button_display' => false,
				'title'          => $title,
				'close_event'    => 'tecTicketsCommerceClosePurchaserModal',
				'show_event'     => 'tecTicketsCommerceOpenPurchaserModal',
			],
			'edit-purchaser-modal'
		);

		$modal_content = ob_get_clean();
		$modal         = '<div class="tribe-common"><div id="edit-purchaser-modal-container"></div></div><div>';
		$modal        .= $modal_content;
		$modal        .= '</div>';

		// Appending our modal.
		return $modal . $html;
	}
}
