<?php
/**
 * The main service provider for the Tickets Emails.
 *
 * @since   5.5.6
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use TEC\Common\Contracts\Service_Provider;

/**
 * Service provider for the Tickets Emails.
 *
 * @since   5.5.6
 * @package TEC\Tickets\Emails
 */
class Provider extends Service_Provider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.5.6
	 */
	public function register(): void {
		$this->register_assets();
		$this->register_hooks();

		// Register singletons.
		$this->container->singleton( static::class, $this );

		// Dispatcher is not a singleton!
		$this->container->bind( Dispatcher::class, Dispatcher::class );

		// Emails are not singletons!
		$this->container->bind( Email\Completed_Order::class, Email\Completed_Order::class );
		$this->container->bind( Email\Purchase_Receipt::class, Email\Purchase_Receipt::class );
		$this->container->bind( Email\RSVP_Not_Going::class, Email\RSVP_Not_Going::class );
		$this->container->bind( Email\RSVP::class, Email\RSVP::class );
		$this->container->bind( Email\Completed_Order::class, Email\Completed_Order::class );
		$this->container->bind( Email\Ticket::class, Email\Ticket::class );

		$this->container->singleton( Legacy_Hijack::class );

		$this->container->singleton( Admin\Emails_Tab::class );

		$this->container->singleton( Admin\Preview_Modal::class );

		$this->container->singleton( Admin\Notice_Upgrade::class, Admin\Notice_Upgrade::class, [ 'hook' ] );
		$this->container->singleton( Admin\Notice_Extension::class, Admin\Notice_Extension::class, [ 'hook' ] );

		$this->container->register( Email_Handler::class );

		$this->container->singleton( Web_View::class );

		$this->boot();
	}

	/**
	 * Boot the provider.
	 *
	 * @since 5.6.0
	 */
	public function boot(): void {
		$this->container->make( Admin\Notice_Upgrade::class );
		$this->container->make( Admin\Notice_Extension::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since 5.5.6
	 */
	protected function register_assets(): void {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Emails.
	 *
	 * @since 5.5.6
	 */
	protected function register_hooks(): void {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
	}
}
