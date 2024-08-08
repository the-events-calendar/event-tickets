<?php

namespace TEC\Tickets\Commerce\Admin;

use TEC\Common\Contracts\Service_Provider;
use Tec\Tickets\Commerce\Order;
use Tribe__Template;
use TEC\Tickets\Commerce\Gateways\Manager;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use Tribe__Tickets__Main;
use WP_Post;

/**
 * Class Singular_Order_Page
 *
 * @since 5.2.0
 *
 * @package TEC\Tickets\Commerce\Admin
 */
class Singular_Order_Page extends Service_Provider {

	/**
	 * Stores the instance of the template engine that we will use for rendering the metaboxes.
	 *
	 * @since TBD
	 *
	 * @var ?Tribe__Template
	 */
	protected $template = null;

	/**
	 * @inheritdoc
	 */
	public function register() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 10, 2 );
	}

	/**
	 * ET Template class instance.
	 *
	 * @return Tribe__Template
	 */
	public function template( $name, $context = [], $echo = true ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
		if ( ! $this->template ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( Tribe__Tickets__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/commerce/orders/single' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( true );
		}

		return $this->template->template( $name, $context, $echo );
	}

	public function add_meta_boxes( $post_type, $post ) {
		if ( Order::POSTTYPE !== $post_type ) {
			return;
		}

		add_meta_box(
			'tribe-tickets-order-details',
			__( 'Order Details', 'event-tickets' ),
			[ $this, 'render_order_details' ],
			$post_type,
			'advanced',
			'high'
		);

		add_meta_box(
			'tribe-tickets-order-items',
			__( 'Items', 'event-tickets' ),
			[ $this, 'render_order_items' ],
			$post_type,
			'normal',
			'high'
		);


		if ( ! function_exists( 'post_submit_meta_box' ) ) {
			// Something changes in wp core. Let's bail instead of causing fatal.
			return;
		}

		global $wp_meta_boxes;

		$meta_box = $wp_meta_boxes[ get_current_screen()->id ]['side']['core']['submitdiv'] ?? false;

		// Remove core's Publish metabox and add our own.
		remove_meta_box( 'submitdiv', $post_type, 'side' );
		add_meta_box(
			'submitdiv',
			__( 'Actions', 'event-tickets' ),
			[ $this, 'render_actions' ],
			$post_type,
			'side',
			'high',
			$meta_box['args'] ?? []
		);
	}

	public function render_actions( $post ) {
		ob_start();
		post_submit_meta_box( $post );
		$submit = ob_get_clean();

		$template = $this->template(
			'order-actions-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			],
			false
		);

		echo str_replace( '<div class="submitbox" id="submitpost">', '<div class="submitbox" id="submitpost">' . $template, $submit );
	}

	public function render_order_details( $post ) {
		$this->template(
			'order-details-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			]
		);
	}

	public function render_order_items( $post ) {
		$this->template(
			'order-items-metabox',
			[
				'order'       => tec_tc_get_order( $post ),
				'single_page' => $this,
			]
		);
	}

	public function get_gateway_label( $order) {
		if ( is_numeric( $order ) ) {
			$order = tec_tc_get_order( $order );
		}

		if ( ! $order instanceof WP_Post ) {
			return '';
		}

		$gateway = tribe( Manager::class )->get_gateway_by_key( $order->gateway );

		if ( $gateway instanceof Free_Gateway ) {
			return esc_html__( 'Free', 'event-tickets' );
		}

		if ( ! $gateway ) {
			return esc_html( $order->gateway );
		}

		$order_url = $gateway->get_order_controller()->get_gateway_dashboard_url_by_order( $order );

		if ( empty( $order_url ) ) {
			return esc_html( $gateway::get_label() );
		}

		return sprintf(
			'%1$s%2$s%3$s%4$s<br><span class="tribe-dashicons"><input type="text" readonly value="%5$s" /><a href="javascript:void(0)" data-text="%5$s" class="tribe-copy-to-clipboard dashicons dashicons-admin-page"></a></span>',
			'<a class="tribe-dashicons" href="' . esc_url( $order_url ) . '" target="_blank" rel="noopener noreferrer">',
			esc_html( $gateway::get_label() ),
			'<span class="dashicons dashicons-external"></span>',
			'</a>',
			esc_attr( $order->gateway_order_id )
		);
	}
}
