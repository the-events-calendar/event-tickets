<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers;

use Exception;
use InvalidArgumentException;
use TEC\Common\Asset;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Modifier_Settings.
 *
 * Manages the admin settings UI in relation to Order Modifiers.
 *
 * @since 5.18.0
 */
class Modifier_Admin_Handler extends Controller_Contract {

	use Valid_Types;

	/**
	 * Event Tickets menu page slug.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	protected static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Order Modifiers page slug.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-order-modifiers';

	/**
	 * Event Tickets Order Modifiers page hook suffix.
	 *
	 * @since 5.18.0
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-order-modifiers';

	/**
	 * Retrieves the page slug associated with the modifier settings.
	 *
	 * This method returns the page slug.
	 *
	 * @since 5.18.0
	 *
	 * @return string The page slug for the modifier settings.
	 */
	public static function get_page_slug(): string {
		return self::$slug;
	}

	/**
	 * Un-registers hooks and actions.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'admin_menu', [ $this, 'add_tec_tickets_order_modifiers_page' ], 15 );
		remove_action( 'admin_init', [ $this, 'handle_delete_modifier' ] );
		remove_action( 'admin_init', [ $this, 'handle_form_submission' ] );
		remove_action( 'current_screen', [ $this, 'prepare_items_for_table_view' ] );

		remove_action( 'admin_notices', [ $this, 'handle_notices' ] );

		remove_filter( 'event_tickets_should_enqueue_admin_settings_assets', [ $this, 'enqueue_tec_tickets_settings_css' ] );

		Assets::instance()->remove( 'tec-tickets-order-modifiers-table' );
	}

	/**
	 * Register hooks and actions.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		add_action( 'admin_menu', [ $this, 'add_tec_tickets_order_modifiers_page' ], 15 );
		add_action( 'admin_init', [ $this, 'handle_delete_modifier' ] );
		add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
		add_action( 'current_screen', [ $this, 'prepare_items_for_table_view' ] );

		add_action( 'admin_notices', [ $this, 'handle_notices' ] );

		add_filter( 'event_tickets_should_enqueue_admin_settings_assets', [ $this, 'enqueue_tec_tickets_settings_css' ] );

		$this->register_assets();
	}

	/**
	 * Enqueues the admin settings CSS when on the Order Modifiers page.
	 *
	 * @since 5.18.0
	 *
	 * @param bool $should_enqueue Whether the CSS should be enqueued.
	 *
	 * @return bool
	 */
	public function enqueue_tec_tickets_settings_css( bool $should_enqueue ): bool {
		return $should_enqueue ?: $this->is_on_page();
	}

	/**
	 * Register the assets for the Order Modifiers page.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	protected function register_assets() {
		Asset::add(
			'tec-tickets-order-modifiers-table',
			'admin/order-modifiers/table.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_condition( fn() => $this->is_on_page() )
			->set_dependencies( 'jquery', 'wp-util' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->add_to_group( 'tec-tickets-order-modifiers' )
			->add_localize_script(
				'etOrderModifiersTable',
				function () {
					return [
						'modifier' => $this->get_modifier_type_from_request(),
					];
				}
			)
			->register();

		Asset::add(
			'tec-tickets-imask',
			'https://unpkg.com/imask@7.6.1/dist/imask.js',
		)->register();

		Asset::add(
			'tec-tickets-order-modifiers-amount-field-edit-js',
			'admin/order-modifiers/amount-field.js',
			Tickets_Plugin::VERSION,
		)
			->add_to_group_path( Tickets_Plugin::class )
			->set_condition( fn() => $this->is_on_edit_page() )
			->set_dependencies( 'jquery', 'tribe-validation', 'tec-tickets-imask' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->add_to_group( 'tec-tickets-order-modifiers' )
			->add_localize_script(
				'etOrderModifiersAmountField',
				function () {
					$code        = Currency::get_currency_code();
					$percent_max = 'coupon' === $this->get_modifier_type_from_request()
						? 100
						: 999999999;

					return [
						'currencySymbol'     => Currency::get_currency_symbol( $code ),
						'decimalSeparator'   => Currency::get_currency_separator_decimal( $code ),
						'thousandsSeparator' => Currency::get_currency_separator_thousands( $code ),
						'percentMax'         => $percent_max,
						'placement'          => Currency::get_currency_symbol_position( $code ),
						'precision'          => Currency::get_currency_precision( $code ),
					];
				}
			)
			->register();
	}

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers page.
	 *
	 * @since 5.18.0
	 *
	 * @return bool True if on the Order Modifiers page, false otherwise.
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Defines whether the current page is the Event Tickets Order Modifiers edit page.
	 *
	 * @since 5.21.0
	 *
	 * @return bool True if on the Order Modifiers edit page, false otherwise.
	 */
	protected function is_on_edit_page(): bool {
		$is_edit = tribe_is_truthy( tec_get_request_var( 'edit', '0' ) );

		return $is_edit && $this->is_on_page();
	}

	/**
	 * Returns the main admin order modifiers URL.
	 *
	 * @since 5.18.0
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string The URL for the Order Modifiers admin page.
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Merge default args and passed args.
		$args = wp_parse_args( $args, $defaults );

		// Generate the admin URL.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets Order Modifiers page.
		 *
		 * @since 5.18.0
		 *
		 * @param string $url The URL to the Order Modifiers page.
		 */
		return apply_filters( 'tec_tickets_commerce_order_modifiers_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Order Modifiers page.
	 *
	 * @since 5.18.0
	 */
	public function add_tec_tickets_order_modifiers_page(): void {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Coupons &amp; Fees', 'event-tickets' ),
				'position' => 1.5,
				'callback' => [ $this, 'render_tec_order_modifiers_page' ],
			]
		);
	}

	/**
	 * Render the `Order Modifiers` page for the selected strategy.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function render_tec_order_modifiers_page(): void {
		// Get and sanitize request vars for modifier and modifier_id.
		$modifier_type = $this->get_modifier_type_from_request();
		$modifier_id   = absint( tec_get_request_var( 'modifier_id', '0' ) );
		$is_edit       = tribe_is_truthy( tec_get_request_var( 'edit', '0' ) );

		// Prepare the context for the page.
		$context = [
			'event_id'    => 0,
			'modifier'    => $modifier_type,
			'modifier_id' => $modifier_id,
			'is_edit'     => $is_edit,
		];

		// Get the appropriate strategy for the selected modifier.
		$manager = $this->get_manager_for_type( $modifier_type );

		// If the strategy doesn't exist, show an error message.
		if ( false === $manager ) {
			return;
		}

		// Render the appropriate view based on the context.
		if ( $is_edit ) {
			$this->render_edit_view( $manager, $context );
		} else {
			$this->render_table_view( $manager, $context );
		}
	}

	/**
	 * Get the modifier manager instance.
	 *
	 * @since 5.18.1
	 *
	 * @param string $modifier_type The type of modifier to get the manager for.
	 * @param bool   $render_error  Whether to render an error message if the manager is not found.
	 *
	 * @return false|Modifier_Manager
	 */
	protected function get_manager_for_type( string $modifier_type, bool $render_error = true ) {
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// If the strategy doesn't exist, show an error message.
		if ( ! $modifier_strategy && $render_error ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );

			return false;
		}

		return new Modifier_Manager( $modifier_strategy );
	}

	/**
	 * Retrieves the modifier data by ID.
	 *
	 * @since 5.18.0
	 *
	 * @param int $modifier_id The ID of the modifier to retrieve.
	 *
	 * @return array|null The modifier data or null if not found.
	 */
	protected function get_modifier_data_by_id( int $modifier_id ): ?array {
		// Get the modifier type from the request or use the default.
		$modifier_type = $this->get_modifier_type_from_request();

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );
		if ( ! $modifier_strategy ) {
			return null;
		}

		// Use the strategy to retrieve the modifier data by ID.
		return $modifier_strategy->get_modifier_by_id( $modifier_id );
	}

	/**
	 * Render the table view for the selected modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the table.
	 *
	 * @return void
	 */
	protected function render_table_view( Modifier_Manager $manager, array $context ): void {
		$manager->render_table( $context );
	}

	/**
	 * Render the edit view for the selected modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param Modifier_Manager $manager The modifier manager.
	 * @param array            $context The context for rendering the edit screen.
	 *
	 * @return void
	 */
	protected function render_edit_view( Modifier_Manager $manager, array $context ): void {
		// Get modifier ID from the context.
		$modifier_id = (int) $context['modifier_id'];

		if ( 0 > $modifier_id ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid Modifier ID provided.', 'event-tickets' ) . '</p></div>';
			return;
		}

		if ( ! $modifier_id ) {
			$manager->render_edit_screen( $context );
			return;
		}

		try {
			$modifier_data = $this->get_modifier_data_by_id( $modifier_id );
		} catch ( Exception $e ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Error retrieving modifier data.', 'event-tickets' ) . '</p></div>';
			return;
		}

		// Render the edit screen, passing the populated context.
		$manager->render_edit_screen( array_merge( $context, $modifier_data ) );
	}

	/**
	 * Handles the form submission and saves the modifier data.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function handle_form_submission(): void {
		// Check if the form was submitted and verify nonce.
		if ( empty( tec_get_request_var( 'order_modifier_form_save' ) ) || ! check_admin_referer( 'order_modifier_save_action', 'order_modifier_save_action' ) ) {
			return;
		}

		// Get and sanitize request vars for modifier and modifier_id.
		$modifier_type = $this->get_modifier_type_from_request();
		$modifier_id   = absint( tec_get_request_var( 'modifier_id', '0' ) );

		// Get the appropriate strategy for the selected modifier.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// Early bail if the strategy doesn't exist.
		if ( ! $modifier_strategy ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );
			return;
		}

		$raw_data                      = tribe_get_request_vars( true );
		$raw_data['order_modifier_id'] = $modifier_id;

		try {
			// Use the Modifier Manager to sanitize and save the data.
			$manager       = new Modifier_Manager( $modifier_strategy );
			$modifier_data = $modifier_strategy->map_form_data_to_model( $raw_data );

			$result = $manager->save_modifier( $modifier_data );
		} catch ( InvalidArgumentException $exception ) {
			$this->render_error_message(
				sprintf(
					/* translators: 1: the modifier name 2:error message */
					__( 'Error saving %1$s: %2$s', 'event-tickets' ),
					$modifier_strategy->get_modifier_display_name(),
					$exception->getMessage()
				)
			);
			return;
		}

		$edit_link = add_query_arg(
			[
				'page'        => rawurlencode( $modifier_strategy->get_page_slug() ),
				'modifier'    => rawurlencode( $modifier_strategy->get_modifier_type() ),
				'edit'        => 1,
				'modifier_id' => $result->id,
				'updated'     => 1,
			],
			admin_url( '/admin.php' )
		);

		wp_safe_redirect( $edit_link );
		tribe_exit();
	}

	/**
	 * Handles the display of notices.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function handle_notices() {
		if ( (int) tec_get_request_var_raw( 'updated' ) !== 1 ) {
			return;
		}

		if ( (int) tec_get_request_var_raw( 'edit' ) !== 1 ) {
			return;
		}

		$modifier_type     = $this->get_modifier_type_from_request();
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		if ( ! $modifier_strategy ) {
			return;
		}

		if ( tec_get_request_var( 'page' ) !== rawurldecode( $modifier_strategy->get_page_slug() ) ) {
			return;
		}

		$this->render_success_message( $this->get_successful_save_message( $modifier_type ) );
	}

	/**
	 * Shows a success message.
	 *
	 * @since 5.18.0
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	protected function render_success_message( string $message ): void {
		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Shows an error message.
	 *
	 * @since 5.18.0
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	protected function render_error_message( string $message ): void {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Handles the deletion of a modifier.
	 *
	 * This function checks for the 'delete_modifier' action in the query parameters, verifies the nonce, and
	 * deletes the modifier if the nonce is valid. It also redirects the user back to the referring page after
	 * performing the deletion to avoid form resubmission.
	 *
	 * @since 5.18.0
	 *
	 * @return void
	 */
	public function handle_delete_modifier(): void {
		// Check if the action is 'delete_modifier' and nonce is set.
		$action        = tec_get_request_var( 'action', '' );
		$modifier_id   = absint( tec_get_request_var( 'modifier_id', '' ) );
		$nonce         = tec_get_request_var( '_wpnonce', '' );
		$modifier_type = $this->get_modifier_type_from_request();

		// Early bail if the action is not 'delete_modifier'.
		if ( 'delete_modifier' !== $action ) {
			return;
		}

		// Bail if the modifier ID or type is empty.
		if ( empty( $modifier_id ) || empty( $modifier_type ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, "delete_modifier_{$modifier_id}" ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'event-tickets' ) );
		}

		try {
			// Get the appropriate strategy for the selected modifier type.
			$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

			// Perform the deletion logic.
			$success = $modifier_strategy->delete_modifier( $modifier_id );

			// Construct the redirect URL with a success or failure flag.
			$redirect_url = remove_query_arg( [ 'action', 'modifier_id', '_wpnonce' ], wp_get_referer() );
			$redirect_url = add_query_arg( 'deleted', $success ? 'success' : 'fail', $redirect_url );

			// Redirect to the original page to avoid resubmitting the form upon refresh.
			wp_safe_redirect( $redirect_url ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect,StellarWP.CodeAnalysis.RedirectAndDie
			tribe_exit();
		} catch ( Exception $e ) {
			// Handle invalid modifier strategy.
			tribe_exit( esc_html__( 'Invalid modifier type.', 'event-tickets' ) );
		}
	}

	/**
	 * When we're on the table view page, prepare the items for the table view.
	 *
	 * @since 5.18.1
	 *
	 * @return void
	 */
	public function prepare_items_for_table_view(): void {
		if ( ! $this->is_on_page() ) {
			return;
		}

		$singular_id = tec_get_request_var( 'modifier_id', false );
		if ( $singular_id && is_numeric( $singular_id ) ) {
			return;
		}

		// Prepare the items based on the modifier type.
		$modifier_type = $this->get_modifier_type_from_request();
		$manager       = $this->get_manager_for_type( $modifier_type );
		$manager->get_table_class()->prepare_items();
	}

	/**
	 * Get the modifier type from the request variables.
	 *
	 * @since 5.21.0
	 *
	 * @return string The modifier type.
	 */
	protected function get_modifier_type_from_request(): string {
		return sanitize_key( tec_get_request_var( 'modifier', $this->get_default_type() ) );
	}

	/**
	 * Get the successful save message for the modifier type.
	 *
	 * @since 5.21.0
	 *
	 * @param string $modifier_type The type of modifier that was saved.
	 *
	 * @return string The success message to display.
	 */
	protected function get_successful_save_message( string $modifier_type ): string {
		switch ( $modifier_type ) {
			case 'coupon':
				return __( 'Coupon saved successfully!', 'event-tickets' );

			case 'fee':
				return __( 'Fee saved successfully!', 'event-tickets' );

			default:
				$message = __( 'Modifier saved successfully!', 'event-tickets' );

				/**
				 * Filters the successful save message for order modifiers.
				 *
				 * @since 5.21.0
				 *
				 * @param string $message       The success message to display.
				 * @param string $modifier_type The type of modifier that was saved.
				 */
				return (string) apply_filters( 'tec_tickets_commerce_order_modifiers_successful_save_message', $message, $modifier_type );
		}
	}
}
