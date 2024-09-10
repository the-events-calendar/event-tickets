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
			$this->register_assets();
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

	public function register_assets() {
		$tickets_main = tribe( 'tickets.main' );

		tribe_asset(
			$tickets_main,
			'tickets-commerce-purchaser-modal-scripts',
			'admin/orders/purchaser-modal.js',
			[
				'jquery',
				'tribe-dialog',
				'tribe-common-skeleton-style',
				'tribe-common-full-style',
				'tribe-common',
			],
			null
		);
	}

	public function ajax_handle_request() {
		check_ajax_referer('tec_commerce_purchaser_edit', '_nonce' );
		$error_message = '';

		switch( $_SERVER['REQUEST_METHOD'] ) {
			case 'POST':
				list( $first_name, $last_name ) = explode( ' ', $_POST['name'] );
				$email   					    = $_POST['email'];
				$post_id 						= $_POST['id'];

				if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
					wp_send_json_error(
						_x(
							'Invalid email address',
							'When the provided purchaser email address is invalid.',
							'event-tickets'
						)
					);
					die();
				}

				// Update through gateway first.


				// Local database update.
				tec_tc_orders()->by_args(
						[
							'status' => 'any',
							'id'     => $post_id,
						]
					)->set_args( [
						'purchaser_email'      => sanitize_email( $email ),
						'purchaser_first_name' => sanitize_text_field( $first_name ),
						'purchaser_last_name'  => sanitize_text_field( $last_name ),
					] )->save();


				if( $updated ) {
					wp_send_json_success();
				} else {
					wp_send_json_error(
						_x(
							'There was an unknown error while updating the purchaser. Please try again later.',
							'When the provided purchaser email address is invalid.',
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
