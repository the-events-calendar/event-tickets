<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Webhook_Endpoint;

/**
 * Class Webhooks
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Webhooks extends Abstract_Webhooks {

	/**
	 * Option key that determines if the webhooks are valid.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_is_valid_webhooks = 'tickets-commerce-stripe-is-valid-webhooks';

	/**
	 * Option key that determines if the webhooks are valid.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $nonce_key_handle_validation = 'tickets-commerce-stripe-webhook-handle_validation';

	/**
	 * Option key that we use to allow customers to copy.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_webhooks_value = 'tickets-commerce-stripe-webhooks-value';

	/**
	 * Option name for the option to store the webhook signing key
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_webhooks_signing_key = 'tickets-commerce-stripe-webhooks-signing-key';

	/**
	 * @inheritDoc
	 */
	public function get_gateway(): Abstract_Gateway {
		return tribe( Gateway::class );
	}

	/**
	 * @inheritDoc
	 */
	public function get_merchant(): Abstract_Merchant {
		return tribe( Merchant::class );
	}

	/**
	 * Testing if given Signing Key is valid on an AJAX request.
	 *
	 * @since 5.3.0
	 *
	 * @return void
	 */
	public function handle_validation(): void {
		$nonce  = tribe_get_request_var( 'tc_nonce' );
		$status = esc_html__( 'Webhooks not validated yet.', 'event-tickets' );

		if ( ! wp_verify_nonce( $nonce, static::$nonce_key_handle_validation ) ) {
			wp_send_json_error( [ 'updated' => false, 'status' => $status ] );
			exit;
		}

		$signing_key = tribe_get_request_var( 'signing_key' );
		$updated     = tribe_update_option( static::$option_webhooks_signing_key, $signing_key );

		if ( empty( $signing_key ) ) {
			// If we updated and the value was empty we need to reset the validity of the key.
			if ( $updated ) {
				tribe_update_option( static::$option_is_valid_webhooks, false );
			}

			wp_send_json_error( [ 'updated' => $updated, 'status' => $status ] );
			exit;
		}

		$account_data_update = [
			'metadata' => [
				'tec_tc_enabled_webhooks' => 1,
			],
		];

		// This doesn't work on a Sandbox account, so we might need to add some text about .
		$response = tribe( Merchant::class )->update( $account_data_update );

		// We sleep for 5 seconds to allow the API to reach the website after the update.
		sleep( 10 );

		// Reset cache so it fetches from DB again.
		wp_cache_init();
		tribe_unset_var( \Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME );

		$is_valid = tribe_get_option( static::$option_is_valid_webhooks, false );
		if ( $is_valid ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
		} else {
			// Reset saved signing key.
			tribe_update_option( static::$option_webhooks_signing_key, '' );
		}

		wp_send_json_success( [ 'is_valid_webhook' => $is_valid, 'updated' => $updated, 'status' => $status ] );
		exit;
	}

	/**
	 * Includes a Copy button to the webhook UI.
	 *
	 * @since 5.3.0
	 *
	 * @param string        $html
	 * @param \Tribe__Field $field
	 *
	 * @return string
	 */
	public function include_webhooks_copy_button( string $html, \Tribe__Field $field ): string {
		if ( static::$option_webhooks_value !== $field->id ) {
			return $html;
		}
		$copy_button = '<button class="tribe-field-tickets-commerce-stripe-webhooks-copy button-secondary" data-clipboard-target=".tribe-field-tickets-commerce-stripe-webhooks-copy-value"><span class="dashicons dashicons-clipboard"></span></button>';

		return $copy_button . $html;
	}

	/**
	 * Return the fields related to webhooks.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function get_fields(): array {
		// The webhook value should always be empty.
		tribe_remove_option( static::$option_webhooks_value );
		$has_singing_key      = tribe_get_option( static::$option_webhooks_signing_key );
		$is_valid_signing_key = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( ! $has_singing_key || ! $is_valid_signing_key ) {
			$signing_key_tooltip = '<span class="dashicons dashicons-no"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks not validated yet.', 'event-tickets' ) . '</span>';
		} else {
			$signing_key_tooltip = '<span class="dashicons dashicons-yes"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' ) . '</span>';
		}

		if ( tribe( Merchant::class )->is_sandbox() ) {
			$signing_key_tooltip .= '<br /><b>' . esc_html__( 'Webhook validation will not work in Tickets Commerce test mode.', 'event-tickets' ) . '</b>';
		}

		return [
			'tickets-commerce-gateway-settings-group-start-webhook'       => [
				'type' => 'html',
				'html' => '<div class="tribe-settings-form-wrap">',
			],
			'tickets-commerce-gateway-settings-group-header-webhook'      => [
				'type' => 'html',
				'html' => '<h4 class="tec-tickets__admin-settings-tickets-commerce-gateway-group-header">' . esc_html__( 'Webhooks', 'event-tickets' ) . '</h4><div class="clear"></div>',
			],
			'tickets-commerce-gateway-settings-group-description-webhook' => [
				'type' => 'html',
				'html' => '<p class="tec-tickets__admin-settings-tickets-commerce-gateway-group-description-stripe-webhooks contained">' .
							wp_kses_post(
								sprintf(
									// Translators: %1$s A link to the KB article. %2$s closing `</a>` link.
									__( 'Setting up webhooks will enable you to receive notifications on charge statuses and keep order information up to date for asynchronous payments. %1$sLearn more%2$s', 'event-tickets' ),
									'<a target="_blank" rel="noopener noreferrer" href="https://evnt.is/1b3p">',
									'</a>'
								)
							)
							. '</p><div class="clear"></div>',
			],
			static::$option_webhooks_value                                => [
				'type'       => 'text',
				'label'      => esc_html__( 'Webhooks URL', 'event-tickets' ),
				'tooltip'    => '',
				'size'       => 'large',
				'default'    => tribe( Webhook_Endpoint::class )->get_route_url(),
				'attributes' => [
					'readonly' => 'readonly',
					'class'    => 'tribe-field-tickets-commerce-stripe-webhooks-copy-value',
				],
			],
			static::$option_webhooks_signing_key                          => [
				'type'                => 'text',
				'label'               => esc_html__( 'Signing Secret', 'event-tickets' ),
				'tooltip'             => $signing_key_tooltip,
				'size'                => 'large',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
				'attributes'          => [
					'data-ajax-nonce'   => wp_create_nonce( static::$nonce_key_handle_validation ),
					'data-loading-text' => esc_attr__( 'Validating signing key with Stripe, please wait.', 'event-tickets' ),
					'data-ajax-action'  => 'tec_tickets_commerce_gateway_stripe_test_webhooks',
				],
			],
			'tickets-commerce-gateway-settings-group-end-webhook'         => [
				'type' => 'html',
				'html' => '<div class="clear"></div></div>',
			],
		];
	}
}
