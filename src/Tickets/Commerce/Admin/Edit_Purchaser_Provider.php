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
use Tribe__Tickets__Main;

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

	public function register_hooks() {
		add_filter(
			'tribe_template_pre_html:tickets/admin-views/commerce/orders/single/order-details-metabox',
			[ $this, 'render_modal' ],
			10,
			5
		);
		add_action( 'wp_ajax_tec_commerce_purchaser_edit', [ $this, 'ajax_handle_request' ] );
	}

	public function ajax_handle_request() {
		check_ajax_referer('tec_commerce_purchaser_edit', '_nonce' );

		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				// Deal with "full name" field into pieces.
				list( $first_name, $last_name ) = explode( ' ', sanitize_text_field( $_POST['name'] ) );
				$email   					    = sanitize_email( $_POST['email'] );
				$post_id 						= (int) $_POST['ID'];
				$send_email                     = ! empty( $_POST['send_email'] );

				// Clean up vars.
				$first_name ??= '';
				$last_name  ??= '';

				if ( ! is_email( $email ) ) {
					wp_send_json_error(
						_x(
							'Invalid email address',
							'When the provided purchaser email address is invalid.',
							'event-tickets'
						)
					);
					die();
				}

				// Local database update.
				$updated = tec_tc_orders()->by_args(
						[
							'status' => 'any',
							'id'     => $post_id,
						]
					)->set_args( [
						'purchaser_email'      => $email,
						'purchaser_first_name' => $first_name,
						'purchaser_last_name'  => $last_name,
					] )->save();

				if ( $updated && $send_email ) {
					$order = tec_tc_get_order( $post_id );
					if( ! $order ) {
						wp_send_json_error(
							_x(
								'There was an error retrieving details for the email. Please try again later.',
								'When the purchaser get order for the email fails for an unknown reason.',
								'event-tickets'
							)
						);
						die();
					}

					$sent = tribe( Send_Email_Purchase_Receipt::class )->send_for_order( $order );
					if( ! $sent ) {
						wp_send_json_error(
							_x(
								'There was an error sending the email receipt. Please ensure Wordpress is configured for email delivery.',
								'When the purchaser email receipt fails for an unknown reason.',
								'event-tickets'
							)
						);
						die();
					}
				}
				if( $updated ) {
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
				$post  = get_post( $_GET['ID'] );
				$order = tec_tc_get_order( $post );

				wp_send_json_success( $order->purchaser );
				break;
		}

		die();
	}
	public function render_modal($html, $file, $name, $template, $context) {
		$dialog_view = tribe( 'dialog.view' );

		ob_start();
		$dialog_view->render_modal(
			$this->template('src/admin-views/commerce/orders/single/edit-purchaser-modal', $context),
			[
				'id' => 'edit-purchaser-modal',
				'append_target' => '#edit-purchaser-modal-container',
				'button_display'          => false,
				'title' => esc_html_x( 'Edit purchaser', 'Edit purchaser modal title.', 'event-tickets'),
				'close_event'             => 'tecTicketsCommerceClosePurchaserModal',
				'show_event'              => 'tecTicketsCommerceOpenPurchaserModal',
			],
			'edit-purchaser-modal'
		);
		$modal_content = ob_get_clean();

		$modal  = '<div class="tribe-common"> <div id="edit-purchaser-modal-container"></div></div><div class="">';
		$modal .= $modal_content;
		$modal .= '</div>';
		echo $modal;

		return $html;
	}


	public function template( $name, $context = [] ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
		$template = tribe( 'tickets.admin.views' );
		return $template->template( $name, $context, false );

		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
		//	$this->template->set_template_folder( 'src/admin-views/commerce/orders/single' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template->template( $name, $context, false );
	}


}
