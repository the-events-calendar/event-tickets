<?php
/**
 * Handles the integration for emails.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\lucatume\DI52\Container;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Emails\Email\Ticket;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets\Emails\Email_Template;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Emails\Mock_Event_Post;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Emails\Series_Pass;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Emails\Upcoming_Series_Events_List;
use TEC\Tickets_Plus\Emails\Email\Ticket as Tickets_Plus_Ticket_Email;
use TEC\Tickets_Plus\Emails\Hooks as Tickets_Plus_Email_Hooks;
use TEC\Tickets_Wallet_Plus\Emails\Controller as Wallet_Plus_Email_Controller;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers\Attach_To_Emails;
use Tribe__Template as Template;
use WP_Post;

/**
 * Class Emails controller.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Emails extends Controller {
	/**
	 * A reference to the Upcoming Series Events List instance.
	 *
	 * @since 5.8.4
	 *
	 * @var Upcoming_Series_Events_List
	 */
	private Upcoming_Series_Events_List $upcoming_events_list;

	/**
	 * Emails constructor.
	 *
	 * @since 5.8.4
	 *
	 * @param Container                   $container            The DI container.
	 * @param Upcoming_Series_Events_List $upcoming_events_list The Upcoming Series Events List instance.
	 */
	public function __construct( Container $container, Upcoming_Series_Events_List $upcoming_events_list ) {
		parent::__construct( $container );
		$this->upcoming_events_list = $upcoming_events_list;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_emails_registered_emails', [ $this, 'add_series_to_registered_email_types' ] );

		// Hook Event Tickets Plus template components.
		$tickets_plus_email_controller_action = 'tec_container_registered_provider_' . Tickets_Plus_Email_Hooks::class;
		if ( did_action( $tickets_plus_email_controller_action ) ) {
			$this->hook_tickets_plus_filters();
		} else {
			add_action( $tickets_plus_email_controller_action, [ $this, 'hook_tickets_plus_filters' ] );
		}

		// Hook Wallet Plus template components.
		$wallet_plus_email_controller_action = 'tec_container_registered_provider_' . Wallet_Plus_Email_Controller::class;
		if ( did_action( $wallet_plus_email_controller_action ) ) {
			$this->hook_wallet_plus_filters();
		} else {
			add_action( $wallet_plus_email_controller_action, [ $this, 'hook_wallet_plus_filters' ] );
		}

		add_filter( 'tec_tickets_emails_series-pass_preview_args', [ $this, 'filter_email_preview_args' ], 100 );
		add_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/post-title',
			[ $this, 'include_series_dates_for_series_pass_email' ],
			10,
			3
		);
		add_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/tickets',
			[
				$this,
				'include_series_thumbnail',
			],
			15,
			3
		);
		add_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/post-description',
			[ $this, 'include_series_upcoming_events_list' ],
			10,
			3
		);

		add_action(
			'tribe_template_after_include:tickets/emails/template-parts/header/head/styles',
			[
				$this,
				'include_email_styles',
			],
			10,
			3
		);

		add_filter( 'tec_tickets_email_class', [ $this, 'use_series_pass_email' ], 10, 3 );
		add_action(
			'tribe_template_before_include:tickets-wallet-plus/pdf/pass/body/post-title',
			[ $this, 'include_series_dates_for_series_pass_email' ],
			10,
			3
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_emails_registered_emails', [ $this, 'add_series_to_registered_email_types' ] );

		$tickets_plus_email_controller_action = 'tec_container_registered_provider_' . Tickets_Plus_Email_Hooks::class;
		if ( did_action( $tickets_plus_email_controller_action ) ) {
			$this->unhook_tickets_plus_filters();
		} else {
			remove_action( $tickets_plus_email_controller_action, [ $this, 'hook_tickets_plus_filters' ] );
		}

		$wallet_plus_email_controller_action = 'tec_container_registered_provider_' . Wallet_Plus_Email_Controller::class;
		if ( did_action( $wallet_plus_email_controller_action ) ) {
			$this->unhook_wallet_plus_filters();
		} else {
			remove_action( $wallet_plus_email_controller_action, [ $this, 'hook_wallet_plus_filters' ] );
		}

		remove_filter( 'tec_tickets_emails_series-pass_preview_args', [ $this, 'filter_email_preview_args' ], 100 );
		remove_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/post-title',
			[ $this, 'include_series_dates_for_series_pass_email' ]
		);
		remove_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/tickets',
			[
				$this,
				'include_series_thumbnail',
			],
			15,
			3
		);
		remove_action(
			'tribe_template_before_include:tickets/emails/template-parts/body/post-description',
			[ $this, 'include_series_upcoming_events_list' ],
			10,
			3
		);

		remove_action(
			'tribe_template_after_include:tickets/emails/template-parts/header/head/styles',
			[
				$this,
				'include_email_styles',
			],
			10
		);
		remove_filter( 'tec_tickets_email_class', [ $this, 'use_series_pass_email' ], 10, 3 );
		remove_action(
			'tribe_template_before_include:tickets-wallet-plus/pdf/pass/body/post-title',
			[ $this, 'include_series_dates_for_series_pass_email' ]
		);
	}

	/**
	 * Add the Series Pass to the registered email types.
	 *
	 * @since 5.8.4
	 *
	 * @param array<Email_Abstract> $email_types The email types.
	 *
	 * @return array<Email_Abstract> The modified email types.
	 */
	public function add_series_to_registered_email_types( array $email_types ): array {
		$ticket_email_position = 0;
		foreach ( $email_types as $position => $email_type ) {
			if ( $email_type instanceof Ticket ) {
				$ticket_email_position = $position;
				break;
			}
		}

		// Insert the Series Pass email after the Ticket one.
		array_splice(
			$email_types,
			$ticket_email_position + 1,
			0,
			[ tribe( Emails\Series_Pass::class ) ]
		);

		return $email_types;
	}

	/**
	 * Filter the upcoming Events in preview context.
	 *
	 * @since 5.8.4
	 *
	 * @return array{0: WP_Post[], 1: int} The list of mock Events to use in the email preview, and the
	 *                                     hard-coded found value that will trigger the link to the Series
	 *                                     post.
	 */
	public function pre_fill_upcoming_events(): array {
		// Filter the number of Events to show in the list to hard-code the number to 5.
		add_filter(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events_list_count',
			static fn() => 5,
			1000
		);

		$events = Mock_Event_Post::get_preview_events();

		// Return 5 mock Events and a found value that will suggest there are more to see on the Series page.
		return [ $events, 6 ];
	}

	/**
	 * Filters the preview arguments for the email templates.
	 *
	 * @since 5.8.4
	 *
	 * @param array<string,mixed> $preview_args The existing preview arguments.
	 *
	 * @return array<string,mixed> The modified preview arguments.
	 */
	public function filter_email_preview_args( array $preview_args ): array {
		if (
			isset( $preview_args['post'] )
			&& is_object( $preview_args['post'] )
			&& ( $preview_args['post']->post_type ?? null ) === Series_Post_Type::POSTTYPE
		) {
			$preview_args = array_diff_key( $preview_args, [ 'event' => false ] );
		}

		add_filter(
			'tec_tickets_flexible_tickets_series_pass_email_upcoming_events',
			[
				$this,
				'pre_fill_upcoming_events',
			]
		);

		return $preview_args;
	}

	/**
	 * Include the Series date range in the ticket emails.
	 *
	 * @since 5.8.0
	 *
	 * @param string   $file     Template file, unused.
	 * @param string[] $name     Template name components, unused.
	 * @param Template $template The template handler currently rendering. Note this might not be the Tickets Email one,
	 *                           it might be the Wallet Plus one or the template used by other integrations.
	 */
	public function include_series_dates_for_series_pass_email( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$context = $template->get_values();

		if ( ! (
			isset( $context['post'] )
			&& is_object( $context['post'] )
			&& ( $context['post']->post_type ?? null ) === Series_Post_Type::POSTTYPE
		) ) {
			return;
		}

		$post_id = $context['post']->ID;

		$dates = array_values(
			array_filter(
				[
					tribe_get_start_date( $post_id ),

					tribe_get_end_date( $post_id ),
				]
			)
		);

		// Get hold of the Tickets Email template handler.
		$email_template = $this->container->get( Email_Template::class )->get_template();
		$email_template->set_values( $template->get_values() );
		$email_template->template( 'template-parts/body/series-pass-dates', [ 'dates' => $dates ], true );
	}

	/**
	 * Includes the Series Pass template image, if set.
	 *
	 * @since 5.8.4
	 *
	 * @param string   $file     Template file, unused.
	 * @param string[] $name     Template name components, unused.
	 * @param Template $template Event Tickets template object.
	 *
	 * @return void The series thumbnail is included, if set.
	 */
	public function include_series_thumbnail( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$template->template( 'template-parts/body/thumbnail', $template->get_local_values(), true );
	}

	/**
	 * Includes the Series Pass email styles.
	 *
	 * @since 5.8.4
	 *
	 * @param string   $file     Template file, unused.
	 * @param string[] $name     Template name components, unused.
	 * @param Template $template Event Tickets template object.
	 *
	 * @return void The series thumbnail is included, if set.
	 */
	public function include_email_styles( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$context = $template->get_local_values();

		if ( ! ( $context['email'] ?? null ) instanceof Series_Pass ) {
			return;
		}

		$template->template( 'template-parts/header/head/series-pass-styles' );
	}

	/**
	 * Include, in the Series Pass email, the upcoming Events list fragment.
	 *
	 * @since 5.8.4
	 *
	 * @param string   $file     Template file, unused.
	 * @param string[] $name     Template name components, unused.
	 * @param Template $template Event Tickets template object.
	 *
	 * @return void The list of upcoming Events is included in the email template.
	 */
	public function include_series_upcoming_events_list( $file, $name, $template ): void {
		if ( ! $template instanceof Template ) {
			return;
		}

		$context = $template->get_local_values();

		if ( ! ( $context['email'] ?? null ) instanceof Series_Pass ) {
			return;
		}

		if ( ! isset( $context['post_id'] ) ) {
			return;
		}

		if ( isset( $context['show_events_in_email'] ) && ! $context['show_events_in_email'] ) {
			return;
		}

		$this->upcoming_events_list->set_template( $template )->render( $context['post_id'] );
	}

	/**
	 * Hooks the Email filters provided by Event Tickets Plus.
	 *
	 * Note that this method will only fire if the ET+ plugin has registered.
	 *
	 * @since 5.8.4
	 *
	 * @return void
	 */
	public function hook_tickets_plus_filters(): void {
		$ticket_plus_email = $this->container->get( Tickets_Plus_Ticket_Email::class );
		add_filter(
			'tec_tickets_emails_series-pass_settings',
			[ $ticket_plus_email, 'filter_tec_tickets_emails_ticket_settings' ]
		);
	}

	/**
	 * Unhooks the Email filters provided by Event Tickets Plus.
	 *
	 * Note that this method will only fire if the ET+ plugin has registered.
	 *
	 * @since 5.8.4
	 *
	 * @return void
	 */
	private function unhook_tickets_plus_filters(): void {
		$ticket_plus_email = $this->container->get( Tickets_Plus_Ticket_Email::class );
		remove_filter(
			'tec_tickets_emails_series-pass_settings',
			[
				$ticket_plus_email,
				'filter_tec_tickets_emails_ticket_settings',
			]
		);
	}

	/**
	 * Hooks the Email filters provided by Wallet Plus.
	 *
	 * Note that this method will only fire if the Wallet Plus plugin has registered.
	 *
	 * @since 5.8.4
	 *
	 * @return void
	 */
	public function hook_wallet_plus_filters(): void {
		$wallet_plus_controller = $this->container->get( Wallet_Plus_Email_Controller::class );
		add_filter(
			'tec_tickets_emails_series-pass_settings',
			[
				$wallet_plus_controller,
				'add_ticket_email_settings',
			]
		);
		$attach_to_email = tribe( Attach_To_Emails::class );
		add_filter(
			'tec_tickets_emails_dispatcher_series-pass_attachments',
			[ $attach_to_email, 'add_ticket_email_attachments' ],
			10,
			2
		);
	}

	/**
	 * Unhooks the Email filters provided by Wallet Plus.
	 *
	 * Note that this method will only fire if the Wallet Plus plugin has registered.
	 *
	 * @since 5.8.4
	 *
	 * @return void
	 */
	private function unhook_wallet_plus_filters(): void {
		$wallet_plus_controller = $this->container->get( Wallet_Plus_Email_Controller::class );
		remove_filter(
			'tec_tickets_emails_series-pass_settings',
			[
				$wallet_plus_controller,
				'add_ticket_email_settings',
			]
		);
		$attach_to_email = tribe( Attach_To_Emails::class );
		remove_filter(
			'tec_tickets_emails_dispatcher_series-pass_attachments',
			[ $attach_to_email, 'add_ticket_email_attachments' ]
		);
	}

	/**
	 * Filters the email class used to email Attendees their Tickets to use the Series Pass email when sending emails
	 * for Series Passes.
	 *
	 * @since 5.8.4
	 *
	 * @param Email_Abstract|null $email    The email class to use.
	 * @param string|null         $provider The Ticket provider, unused.
	 * @param int|null            $post_id  The post ID the Tickets are for.
	 *
	 * @return Email_Abstract|null The email class to use.
	 */
	public function use_series_pass_email( ?Email_Abstract $email = null, ?string $provider = null, ?int $post_id = null ): ?Email_Abstract {
		if ( empty( $post_id ) || get_post_type( $post_id ) !== Series_Post_Type::POSTTYPE ) {
			return $email;
		}

		return $this->container->get( Series_Pass::class );
	}

	/**
	 * Get the Upcoming Series Events List instance.
	 *
	 * @since 5.8.4
	 *
	 * @return Upcoming_Series_Events_List The Upcoming Series Events List instance.
	 */
	public function get_upcoming_events_list(): Upcoming_Series_Events_List {
		return $this->upcoming_events_list;
	}
}
