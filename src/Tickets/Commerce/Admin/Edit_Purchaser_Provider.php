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
		add_action(
			'tribe_template_pre_html:tickets/admin-views/commerce/orders/single/order-details-metabox',
			[ $this, 'render_modal' ]
		);
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
			],
			'admin_enqueue_scripts'
		);
	}

	public function render_modal() {
		$dialog_view = tribe( 'dialog.view' );

		ob_start();
		$dialog_view->render_modal(
			$this->template('edit-purchaser-modal'),
			[
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
	}


	public function template( $name, $context = [] ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/commerce/orders/single' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template->template( $name, $context, false );
	}


}
