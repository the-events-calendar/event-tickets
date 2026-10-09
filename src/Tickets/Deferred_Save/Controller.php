<?php
/**
 * Registers the Deferred Ticket Save feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Main as Tickets_Main;

/**
 * Class Controller.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Controller extends Controller_Contract {
	/**
	 * The name of the constant, and of the environment variable, that switches the feature off.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private const DISABLED = 'TEC_TICKETS_DEFERRED_SAVE_DISABLED';

	/**
	 * The post types the REST entry point is hooked for, so `unregister()` unhooks exactly those.
	 *
	 * @since TBD
	 *
	 * @var array<string,true>
	 */
	private array $rest_post_types = [];

	/**
	 * Whether the feature is active.
	 *
	 * Ticket changes are deferred to the post save on every ticketable post while the feature is
	 * active. It is active unless the constant, the environment variable or the filter switch it off.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the feature is active.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {
			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {
			// The environment variable to disable the feature is truthy.
			return false;
		}

		/**
		 * Filters whether the Deferred Ticket Save feature is active.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since TBD
		 *
		 * @param bool $active Whether the feature is active. Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_deferred_save_active', true );
	}

	/**
	 * Hooks the save entry points.
	 *
	 * The classic save hooks the generic `save_post`, with the post type checked when it fires, so a
	 * post type made ticketable after this registered is still covered. It is bound as a singleton so
	 * the container returns the same callback to `unregister()`.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Classic_Save::class );

		add_action( 'save_post', $this->container->callback( Classic_Save::class, 'on_save_post' ), Classic_Save::PRIORITY, 2 );
		// After core's `wp_refresh_post_nonces()` at 10, which decides whether the post's nonces are refreshed.
		add_filter( 'wp_refresh_nonces', $this->container->callback( Classic_Save::class, 'refresh_nonce' ), 11 );

		$this->container->singleton( Block_Save::class );

		$this->container->singleton( Classic\Editor::class );
		$this->container->singleton( Classic\Notices::class );

		add_action( 'tribe_tickets_metabox_end', $this->container->callback( Classic\Editor::class, 'print_fields' ) );
		add_action( 'tec_tickets_deferred_save_classic_committed', $this->container->callback( Classic\Notices::class, 'remember' ), 10, 2 );
		add_action( 'admin_notices', $this->container->callback( Classic\Notices::class, 'render' ) );

		$this->container->singleton( Classic\Assets::class );
		$this->container->get( Classic\Assets::class )->register();

		$this->container->singleton( Block\Editor_Config::class );

		add_filter( 'tec_tickets_editor_configuration_localized_data', $this->container->callback( Block\Editor_Config::class, 'add_flag' ) );

		$this->hook_rest_saves();
		// A type made ticketable after this ran, by a theme's filter for instance, is hooked once REST starts.
		add_action( 'rest_api_init', [ $this, 'hook_rest_saves' ] );
	}

	/**
	 * Hooks the REST entry point for every ticketable post type not hooked yet.
	 *
	 * There is no generic `rest_after_insert` action, so the entry point is attached per post type.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function hook_rest_saves(): void {
		foreach ( Tickets_Main::instance()->post_types() as $post_type ) {
			if ( isset( $this->rest_post_types[ $post_type ] ) ) {
				continue;
			}

			$this->rest_post_types[ $post_type ] = true;
			add_action( "rest_after_insert_{$post_type}", $this->container->callback( Block_Save::class, 'on_rest_after_insert' ), Block_Save::PRIORITY, 3 );
			add_filter( "rest_prepare_{$post_type}", $this->container->callback( Block_Save::class, 'add_result_to_response' ), 10, 3 );
		}
	}

	/**
	 * Unhooks the save entry points.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'save_post', $this->container->callback( Classic_Save::class, 'on_save_post' ), Classic_Save::PRIORITY );
		remove_filter( 'wp_refresh_nonces', $this->container->callback( Classic_Save::class, 'refresh_nonce' ), 11 );

		remove_action( 'rest_api_init', [ $this, 'hook_rest_saves' ] );

		// What was hooked, whatever the ticketable types are now.
		foreach ( array_keys( $this->rest_post_types ) as $post_type ) {
			remove_action( "rest_after_insert_{$post_type}", $this->container->callback( Block_Save::class, 'on_rest_after_insert' ), Block_Save::PRIORITY );
			remove_filter( "rest_prepare_{$post_type}", $this->container->callback( Block_Save::class, 'add_result_to_response' ), 10 );
		}

		$this->rest_post_types = [];

		remove_action( 'tribe_tickets_metabox_end', $this->container->callback( Classic\Editor::class, 'print_fields' ) );
		remove_action( 'tec_tickets_deferred_save_classic_committed', $this->container->callback( Classic\Notices::class, 'remember' ), 10 );
		remove_action( 'admin_notices', $this->container->callback( Classic\Notices::class, 'render' ) );

		$this->container->get( Classic\Assets::class )->unregister();
		remove_filter( 'tec_tickets_editor_configuration_localized_data', $this->container->callback( Block\Editor_Config::class, 'add_flag' ) );
	}
}
