<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Webhooks;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Webhook_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Return_Endpoint;
use TEC\Tickets\Commerce\Settings;

use Tribe__Settings_Manager as Settings_Manager;
use Tribe__Tickets__Main as Tickets_Plugin;

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
	 * Option name for the option to store the known webhooks.
	 *
	 * @since 5.11.0
	 *
	 * @var string
	 */
	public const OPTION_KNOWN_WEBHOOKS = 'tickets-commerce-stripe-known-webhooks';

	/**
	 * Option name for the option to store pending webhooks.
	 *
	 * @since 5.18.1
	 *
	 * @var string
	 */
	public const PENDING_WEBHOOKS_KEY = '_tec_tickets_commerce_stripe_webhook_pending';

	/**
	 * Nonce key for webhook on-demand set up.
	 *
	 * @since 5.11.0
	 *
	 * @var string
	 */
	public const NONCE_KEY_SETUP = 'tec_tickets_commerce_gateway_stripe_set_up_webhooks';

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
	 * Add a pending webhook to the order.
	 *
	 * @since 5.18.1
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $new_status New status.
	 * @param string $old_status Old status.
	 * @param array  $metadata   Metadata.
	 *
	 * @return void
	 */
	public function add_pending_webhook( int $order_id, string $new_status, string $old_status, array $metadata = [] ): void {
		add_post_meta(
			$order_id,
			self::PENDING_WEBHOOKS_KEY,
			[
				'new_status' => $new_status,
				'metadata'   => $metadata,
				'old_status' => $old_status,
			]
		);
	}

	/**
	 * Get the pending webhooks for an order.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return array
	 */
	public function get_pending_webhooks( int $order_id ): array {
		return (array) get_post_meta( $order_id, self::PENDING_WEBHOOKS_KEY );
	}

	/**
	 * Delete the pending webhooks for an order.
	 *
	 * @since 5.18.1
	 *
	 * @param int $order_id Order ID.
	 *
	 * @return void
	 */
	public function delete_pending_webhooks( int $order_id ): void {
		delete_post_meta( $order_id, self::PENDING_WEBHOOKS_KEY );
	}

	/**
	 * Attempts to get the database option for the valid key from Stripe
	 * This function was introduced to enable a cache-free polling of the database for the Valid Key, it will include a
	 * filter to the WordPress All Options and remove the WordPress request cache for the option we are looking at.
	 *
	 * This will also check every half a second instead of a flat time. Allowing us in the future to chance how much we
	 * are waiting without much work.
	 *
	 * @since 5.7.1 Modified from a simple `sleep(10)` it speeds the process by increasing the amount of times it checks the database.
	 *
	 * @param int $max_attempts Number of attempts we will try to poll the database option.
	 *
	 * @return string|bool|null
	 */
	protected function pool_to_get_valid_key( int $max_attempts = 20 ) {
		$attempts  = 0;
		$valid_key = tribe_get_option( static::$option_is_valid_webhooks, false );

		$remove_settings_from_wp_all_options_cache = static function ( $all_options ) {
			if ( isset( $all_options[ \Tribe__Main::OPTIONNAME ] ) ) {
				unset( $all_options[ \Tribe__Main::OPTIONNAME ] );
			}

			return $all_options;
		};

		add_filter( 'alloptions', $remove_settings_from_wp_all_options_cache, 15 );
		while (
			(
				empty( $valid_key )
				|| ! is_string( $valid_key )
			)
			&& $attempts < $max_attempts
		) {
			usleep( 500000 ); // Wait half a second.

			// Resets the cache since we will want to attempt again.
			tribe_set_var( Settings_Manager::OPTION_CACHE_VAR_NAME, [] );
			wp_cache_delete( \Tribe__Main::OPTIONNAME, 'options' );

			$valid_key = tribe_get_option( static::$option_is_valid_webhooks, false );

			$attempts ++;
		}
		remove_filter( 'alloptions', $remove_settings_from_wp_all_options_cache, 15 );

		return $valid_key;
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
			wp_send_json_error(
				[
					'updated' => false,
					'status'  => $status,
				]
			);
			exit;
		}

		$signing_key    = trim( tribe_get_request_var( 'signing_key' ) );
		$stored_key     = tribe_get_option( static::$option_webhooks_signing_key, false );
		$current_status = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( empty( $signing_key ) ) {
			$status = esc_html__( 'Signing Secret cannot be empty.', 'event-tickets' );
			wp_send_json_success(
				[
					'is_valid_webhook' => false,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		if ( $signing_key === $stored_key && $current_status === md5( $signing_key ) ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			wp_send_json_success(
				[
					'is_valid_webhook' => true,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		// backwards compat
		if ( $signing_key === $stored_key && true === $current_status ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			tribe_update_option( static::$option_is_valid_webhooks, md5( tribe_get_option( static::$option_webhooks_signing_key ) ) );
			wp_send_json_success(
				[
					'is_valid_webhook' => true,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		// at this point, either webhooks were not yet validated with the current key, or we're changing keys so we can start over.
		// replace stored key
		tribe_update_option( static::$option_webhooks_signing_key, $signing_key );
		// wipe success indicator
		tribe_update_option( static::$option_is_valid_webhooks, false );

		// create a test payment
		if ( true !== Payment_Intent::test_creation( [ 'card' ] ) ) {
			// payment creation failed
			$status = esc_html__( 'Could not connect to Stripe for validation. Please check your connection configuration.', 'event-tickets' );
			tribe_update_option( static::$option_webhooks_signing_key, $stored_key );
			wp_send_json_success(
				[
					'is_valid_webhook' => false,
					'updated'          => false,
					'status'           => $status,
				]
			);
			exit;
		}

		/**
		 * Allows changing the amount of attempts Stripe will check for the validated key on our database
		 *
		 * @since 5.7.1
		 *
		 * @param int $max_attempts How many attempts, each one takes half a second. Defaults to 20, total of 10 seconds of polling.
		 */
		$max_attempts = (int) apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_valid_key_polling_attempts', 20 );
		$valid_key    = $this->pool_to_get_valid_key( $max_attempts );
		$updated      = false;

		if ( false === $valid_key ) {
			$status   = esc_html__( 'We have not received any Stripe events yet. Please wait a few seconds and refresh the page.', 'event-tickets' );
			$is_valid = false;
		} elseif ( $valid_key === md5( $signing_key ) ) {
			$status   = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			$is_valid = true;
		} else {
			$status   = esc_html__( 'This key has not been used in the latest events received. If you are setting up a new key, this status will be properly updated as soon as a new event is received.', 'event-tickets' );
			$is_valid = false;
			$updated  = true;
		}

		wp_send_json_success( [ 'is_valid_webhook' => $is_valid, 'updated' => $updated, 'status' => $status ] );
		exit;
	}

	/**
	 * Handles the setup of the webhook.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function handle_webhook_setup() {
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return false;
		}

		if ( tec_tickets_commerce_is_sandbox_mode() ) {
			return false;
		}

		if ( ! $this->get_merchant()->is_active() ) {
			// Bail if Stripe is not active.
			return false;
		}

		if ( $this->has_valid_signing_secret() ) {
			// Already set up and validated.
			return true;
		}

		$webhook_set_up_endpoint = tribe( WhoDat::class )->get_api_url(
			'webhook/enable',
			[
				'stripe_user_id' => rawurlencode( tribe( Merchant::class )->get_client_id() ),
				// We sent this so that WhoDat can check our domain visibility and build the webhook URL.
				'home_url'       => rawurlencode( tribe( Return_Endpoint::class )->get_route_url() ),
				'version'        => rawurlencode( Tickets_Plugin::VERSION ),
				'mode'           => rawurlencode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' ),
				// array_keys to expose only webhook ids. in values we have the webhoo signing secrets we don't want exposed.
				'known_webhooks' => array_map( 'rawurlencode', array_keys( $this->get_known_webhooks() ) ),
			]
		);

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = wp_remote_get( $webhook_set_up_endpoint );

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['webhook']['id'] ) ) {
			return false;
		}

		$this->add_webhook( $body['webhook'] );

		return true;
	}

	/**
	 * Disables the current webhook.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function disable_webhook(): bool {
		if ( ! ( function_exists( 'tec_tickets_commerce_is_enabled' ) && tec_tickets_commerce_is_enabled() ) ) {
			return false;
		}

		if ( tec_tickets_commerce_is_sandbox_mode() ) {
			return false;
		}

		if ( ! $this->get_merchant()->is_active() ) {
			// Bail if Stripe is not active.
			return false;
		}

		if ( ! $this->has_valid_signing_secret( 'disable' ) ) {
			// If we don't have a webhook set up and validated we won't disable any.
			return false;
		}

		// Pinpoint the current webhook in use.
		$known_webhooks = $this->get_current_webhook_id();

		// Current being used, not known. We bail.
		if ( empty( $known_webhooks ) ) {
			return false;
		}

		$webhook_disable_endpoint = tribe( WhoDat::class )->get_api_url(
			'webhook/disable',
			[
				'stripe_user_id' => rawurlencode( tribe( Merchant::class )->get_client_id() ),
				'home_url'       => rawurlencode( tribe( Return_Endpoint::class )->get_route_url() ),
				'version'        => rawurlencode( Tickets_Plugin::VERSION ),
				'mode'           => rawurlencode( tec_tickets_commerce_is_sandbox_mode() ? 'sandbox' : 'live' ),
				'known_webhooks' => array_map( 'rawurlencode', $known_webhooks ),
			]
		);

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		$response = wp_remote_get( $webhook_disable_endpoint );

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['webhook']['id'] ) ) {
			return false;
		}

		// Invalidate webhook related options.
		tribe_remove_option( self::$option_webhooks_signing_key );
		tribe_remove_option( self::$option_is_valid_webhooks );

		return true;
	}

	/**
	 * Get the current webhook ID.
	 *
	 * @since 5.11.0
	 *
	 * @return array
	 */
	public function get_current_webhook_id(): array {
		$current_signing_key = tribe_get_option( static::$option_webhooks_signing_key );

		if ( empty( $current_signing_key ) ) {
			return [];
		}

		$known_webhooks = $this->get_known_webhooks();

		if ( empty( $known_webhooks ) ) {
			return [];
		}

		$known_webhooks = array_flip( $known_webhooks );

		return $known_webhooks[ $current_signing_key ] ? [ $known_webhooks[ $current_signing_key ] ] : [];
	}

	/**
	 * Get the known webhooks.
	 *
	 * @since 5.11.0
	 *
	 * @return array
	 */
	public function get_known_webhooks(): array {
		$known_webhooks = tribe_get_option( self::OPTION_KNOWN_WEBHOOKS, [] );

		if ( ! is_array( $known_webhooks ) ) {
			return [];
		}

		return $known_webhooks;
	}

	/**
	 * Adds a webhook to the known webhooks and updates the current signing secrets
	 * and marks it as validated.
	 *
	 * @since 5.11.0
	 *
	 * @param array $webhook The webhook being added.
	 *
	 * @return void
	 */
	public function add_webhook( $webhook ) {
		$known_webhooks = $this->get_known_webhooks();

		$signing_key = $webhook['secret'] ?? false;
		$signing_key = $signing_key ? $signing_key : ( $known_webhooks[ $webhook['id'] ] ?? false );

		$known_webhooks[ $webhook['id'] ] = $signing_key;

		// Keeping multiple and unlimited known_webhooks is not a good idea.
		// If abused it could grow too big into the DB and also cause the whodat URLs to become longer than 2048 chars which is the limit.
		// In any case, each installation should have only one webhook.
		// Let's keep a maximum of 3 known webhooks.
		if ( count( $known_webhooks ) > 3 ) {
			$known_webhooks = array_slice( $known_webhooks, count( $known_webhooks ) - 3, 3, true );

			// Should never happen since array_slice keeps the latest 3 entries, but just in case.
			if ( ! isset( $known_webhooks[ $webhook['id'] ] ) ) {
				// If we are adding a new one, we need to remove one.
				array_shift( $known_webhooks );
				$known_webhooks[ $webhook['id'] ] = $signing_key;
			}
		}

		tribe_update_option( self::OPTION_KNOWN_WEBHOOKS, $known_webhooks );

		// Since we create the webhook automatically, we overcome the checks and set the key as valid.
		tribe_update_option( self::$option_webhooks_signing_key, $signing_key );
		tribe_update_option( self::$option_is_valid_webhooks, md5( $signing_key ) );
	}

	/**
	 * Testing the current Signing Key has been verified with success.
	 *
	 * @since 5.5.6
	 *
	 * @return void
	 */
	public function handle_verification(): void {
		$nonce  = tribe_get_request_var( 'tc_nonce' );
		$status = esc_html__( 'The signing key appears to be invalid. Please check your webhook configuration in the Stripe Dashboard.', 'event-tickets' );

		if ( ! wp_verify_nonce( $nonce, static::$nonce_key_handle_validation ) ) {
			wp_send_json_error( [ 'updated' => false, 'status' => $status ] );
			exit;
		}

		$stored_key     = tribe_get_option( static::$option_webhooks_signing_key, false );
		$current_status = tribe_get_option( static::$option_is_valid_webhooks, false );

		if ( $current_status === md5( $stored_key ) ) {
			$status = esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' );
			wp_send_json_success( [ 'is_valid_webhook' => true, 'updated' => false, 'status' => $status ] );
			exit;
		}

		wp_send_json_success( [ 'is_valid_webhook' => false, 'updated' => false, 'status' => $status ] );
		exit;
	}

	/**
	 * Get the max number of retries for the webhooks.
	 *
	 * @since 5.19.3
	 *
	 * @return int The number of retries.
	 */
	public function get_max_number_of_retries(): int {
		/**
		 * Filter the maximum number of attempts we will try to retry a webhook process.
		 *
		 * @since 5.19.3
		 *
		 * @param int $max_attempts How many attempts we will try to retry a webhook process. Defaults to 5.
		 *
		 * @return int
		 */
		return (int) apply_filters( 'tec_tickets_commerce_gateway_stripe_webhook_maximum_attempts', 5 );
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

		if ( ! $this->has_valid_signing_secret() ) {
			$signing_key_tooltip = '<span class="dashicons dashicons-no"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks not validated yet.', 'event-tickets' ) . '</span>';
		} else {
			$signing_key_tooltip = '<span class="dashicons dashicons-yes"></span><span class="tribe-field-tickets-commerce-stripe-webhooks-signing-key-status">' . esc_html__( 'Webhooks were properly validated for sales.', 'event-tickets' ) . '</span>';
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
				'html' => $this->get_description_webhook_html(),
			],
			static::$option_webhooks_value       => [
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
			static::$option_webhooks_signing_key => [
				'type'                => 'text',
				'label'               => esc_html__( 'Signing Secret', 'event-tickets' ),
				'tooltip'             => $signing_key_tooltip,
				'size'                => 'large',
				'default'             => '',
				'validation_callback' => 'is_string',
				'validation_type'     => 'textarea',
				'attributes'          => [
					'data-ajax-nonce'         => wp_create_nonce( static::$nonce_key_handle_validation ),
					'data-loading-text'       => esc_attr__( 'Validating signing key with Stripe, please wait. This can take up to one minute.', 'event-tickets' ),
					'data-ajax-action'        => 'tec_tickets_commerce_gateway_stripe_test_webhooks',
					'data-ajax-action-verify' => 'tec_tickets_commerce_gateway_stripe_verify_webhooks',
				],
			],
			'tickets-commerce-gateway-settings-group-end-webhook' => [
				'type' => 'html',
				'html' => '<div class="clear"></div></div>',
			],
		];
	}

	/**
	 * Get the description for the webhooks.
	 *
	 * @since 5.11.0
	 *
	 * @return string
	 */
	protected function get_description_webhook_html(): string {
		$template = tribe( Settings::class )->get_template();
		return $template->template( 'stripe/webhook-description', [], false );
	}

	/**
	 * Whether we have a valid signing secret.
	 *
	 * @since 5.11.0
	 *
	 * @param string $context The context in which we are checking the signing secret.
	 *
	 * @return bool
	 */
	public function has_valid_signing_secret( $context = 'enable' ) {
		// If we have a constant defined, we should bail webhook creation.
		if ( $this->is_signing_secret_const_defined() ) {
			// When the context is enable, it should return true to bail on attempt create/update webhook.
			// Because if valid signing secret, we dont need to create/update one.

			// When the context is disable, it should return false to bail on attempt to disable webhook.
			// Because if we don't have valid signing secret, we don't need to disable one.
			return 'enable' === $context;
		}

		$has_signing_key = tribe_get_option( static::$option_webhooks_signing_key );
		if ( empty( $has_signing_key ) ) {
			return false;
		}

		$is_valid_signing_key = tribe_get_option( static::$option_is_valid_webhooks, false );

		return md5( $has_signing_key ) === $is_valid_signing_key;
	}

	/**
	 * Whether the signing secret constant is defined.
	 *
	 * @since 5.11.0
	 *
	 * @return bool
	 */
	public function is_signing_secret_const_defined() {
		return defined( 'TEC_TC_STRIPE_SIGNING_SECRET' ) && TEC_TC_STRIPE_SIGNING_SECRET;
	}
}
