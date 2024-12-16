<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers
 */

namespace TEC\Tickets\Commerce\Order_Modifiers;

use InvalidArgumentException;
use Exception;
use TEC\Tickets\Commerce\Order_Modifiers\Controller;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Manager;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Main as Tickets_Plugin;
use TEC\Common\StellarWP\Assets\Assets;
use TEC\Common\Asset;

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
		return $should_enqueue ? $should_enqueue : $this->is_on_page();
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
		->add_to_group_path( 'et-core' )
		->set_condition( fn () => $this->is_on_page() )
		->set_dependencies( 'jquery', 'wp-util' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->add_to_group( 'tec-tickets-order-modifiers' )
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
				'title'    => esc_html__( 'Booking Fees', 'event-tickets' ),
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
		$modifier_type = sanitize_key( tec_get_request_var( 'modifier', $this->get_default_type() ) );
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
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// If the strategy doesn't exist, show an error message.
		if ( ! $modifier_strategy ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );
			return;
		}

		// Create a Modifier Manager with the selected strategy.
		$manager = new Modifier_Manager( $modifier_strategy );

		if ( ! $is_edit ) {
			$this->render_table_view( $manager, $context );
			return;
		}
		$this->render_edit_view( $manager, $context );
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
		$modifier_type = tribe_get_request_var( 'modifier', $this->get_default_type() );

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );
		if ( ! $modifier_strategy ) {
			return null;
		}

		// Use the strategy to retrieve the modifier data by ID.
		return $modifier_strategy->get_modifier_by_id( $modifier_id, $modifier_type );
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
		$modifier_type = sanitize_key( tec_get_request_var( 'modifier', $this->get_default_type() ) );
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
		$modifier_strategy = tribe( Controller::class )->get_modifier( $context['modifier'] );

		// Early bail if the strategy doesn't exist.
		if ( ! $modifier_strategy ) {
			$this->render_error_message( __( 'Invalid modifier.', 'event-tickets' ) );
			return;
		}

		$raw_data                      = tribe_get_request_vars( true );
		$raw_data['order_modifier_id'] = $context['modifier_id'];

		try {
			// Use the Modifier Manager to sanitize and save the data.
			$manager       = new Modifier_Manager( $modifier_strategy );
			$modifier_data = $modifier_strategy->map_form_data_to_model( $raw_data );

			$result = $manager->save_modifier( $modifier_data );
		} catch ( InvalidArgumentException $exception ) {
			$this->render_error_message(
				sprintf(
					/* translators: %s: error message */
					__( 'Error saving modifier: %s', 'event-tickets' ),
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

		$modifier_type = sanitize_key( tec_get_request_var( 'modifier', $this->get_default_type() ) );

		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		if ( ! $modifier_strategy ) {
			return;
		}

		if ( tec_get_request_var( 'page' ) !== rawurldecode( $modifier_strategy->get_page_slug() ) ) {
			return;
		}

		$this->render_success_message( __( 'Modifier saved successfully!', 'event-tickets' ) );
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
		$action        = tribe_get_request_var( 'action', '' );
		$modifier_id   = absint( tribe_get_request_var( 'modifier_id', '' ) );
		$nonce         = tribe_get_request_var( '_wpnonce', '' );
		$modifier_type = sanitize_key( tribe_get_request_var( 'modifier', '' ) );

		// Early bail if the action is not 'delete_modifier'.
		if ( 'delete_modifier' !== $action ) {
			return;
		}

		// Bail if the modifier ID or type is empty.
		if ( empty( $modifier_id ) || empty( $modifier_type ) ) {
			return;
		}

		// Verify nonce.
		if ( ! wp_verify_nonce( $nonce, 'delete_modifier_' . $modifier_id ) ) {
			wp_die( esc_html__( 'Nonce verification failed.', 'event-tickets' ) );
		}

		// Get the appropriate strategy for the selected modifier type.
		$modifier_strategy = tribe( Controller::class )->get_modifier( $modifier_type );

		// Handle invalid modifier strategy.
		if ( ! $modifier_strategy ) {
			wp_die( esc_html__( 'Invalid modifier type.', 'event-tickets' ) );
		}

		// Perform the deletion logic.
		$deletion_success = $modifier_strategy->delete_modifier( $modifier_id );

		// Construct the redirect URL with a success or failure flag.
		$redirect_url = remove_query_arg( [ 'action', 'modifier_id', '_wpnonce' ], wp_get_referer() );
		$redirect_url = add_query_arg( 'deleted', $deletion_success ? 'success' : 'fail', $redirect_url );

		// Redirect to the original page to avoid resubmitting the form upon refresh.
		wp_safe_redirect( $redirect_url );
		tribe_exit();
	}
}
