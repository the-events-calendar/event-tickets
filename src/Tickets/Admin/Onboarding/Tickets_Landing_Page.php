<?php
/**
 * Handles the landing page of the onboarding wizard.
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\StellarWP\Installer\Installer;
use TEC\Common\Admin\Abstract_Admin_Page;
use TEC\Common\Admin\Traits\Is_Tickets_Page;
use TEC\Common\Lists\Currency;
use TEC\Common\Lists\Country;
use TEC\Common\Asset;
use Tribe__Tickets__Main as Tickets;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Tickets\Admin\Onboarding\Data;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant as Stripe_Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Merchant as Square_Merchant;

/**
 * Class Landing_Page
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Tickets_Landing_Page extends Abstract_Admin_Page {
	use Is_Tickets_Page;

	/**
	 * The action to dismiss the onboarding page.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	const DISMISS_PAGE_ACTION = 'tec_tickets_dismiss_onboarding_page';

	/**
	 * The option to dismiss the onboarding page.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	const DISMISS_PAGE_OPTION = 'tec_tickets_onboarding_page_dismissed';

	/**
	 * The option to mark the guided setup as visited.
	 *
	 * @since 5.23.0
	 *
	 * @deprecated 5.24.0
	 *
	 * @var string
	 */
	const VISITED_GUIDED_SETUP_OPTION = 'tec_tickets_onboarding_wizard_visited_guided_setup';

	/**
	 * The option to redirect to the guided setup after bulk activation.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	const BULK_ACTIVATION_REDIRECT_OPTION = '_tec_tickets_wizard_redirect';

	/**
	 * The option to redirect to the guided setup after single activation.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	const ACTIVATION_REDIRECT_OPTION = '_tec_tickets_activation_redirect';

	/**
	 * The slug for the admin menu.
	 *
	 * @since 5.23.0
	 *
	 * @var string
	 */
	public static string $slug = 'tickets-setup';

	/**
	 * Whether the page has been dismissed.
	 *
	 * @since 5.23.0
	 *
	 * @var bool
	 */
	public static bool $is_dismissed = false;

	/**
	 * Whether the page has a header.
	 *
	 * @since 5.23.0
	 *
	 * @var bool
	 */
	public static bool $has_header = true;

	/**
	 * Whether the page has a sidebar.
	 *
	 * @since 5.23.0
	 *
	 * @var bool
	 */
	public static bool $has_sidebar = true;

	/**
	 * Whether the page has a footer.
	 *
	 * @since 5.23.0
	 *
	 * @var bool
	 */
	public static bool $has_footer = false;

	/**
	 * Whether the page has a logo.
	 *
	 * @since 5.23.0
	 *
	 * @var bool
	 */
	public static bool $has_logo = true;

	/**
	 * The position of the submenu in the menu.
	 *
	 * @since 5.23.0
	 *
	 * @var int
	 */
	public int $menu_position = 100;

	/**
	 * Register the assets for the landing page.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		Asset::add(
			'tec-tickets-onboarding-wizard-script',
			'wizard.js'
		)
			->add_to_group_path( 'tec-tickets-onboarding' )
			->add_to_group( 'tec-tickets-onboarding' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_condition( fn() => $this->should_show_wizard() )
			->use_asset_file( true )
			->in_footer()
			->register();

		Asset::add(
			'tec-tickets-onboarding-wizard-style',
			'wizard.css'
		)
			->add_to_group_path( 'tec-tickets-onboarding' )
			->add_to_group( 'tec-tickets-onboarding' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_condition( fn() => $this->should_show_wizard() )
			->use_asset_file( false )
			->set_dependencies( 'wp-components', 'tec-variables-full', 'tribe-common-admin' )
			->register();

		Asset::add(
			'tec-tickets-onboarding-style',
			'tickets-admin-onboarding.css'
		)
			->add_to_group_path( Tickets::class )
			->add_to_group( 'tec-tickets-onboarding' )
			->enqueue_on( 'admin_enqueue_scripts' )
			->set_condition( [ __CLASS__, 'is_on_page' ] )
			->use_asset_file( false )
			->set_dependencies( 'wp-components', 'tec-variables-full', 'tribe-common-admin' )
			->register();

			// Set the webpack public path for dynamic asset loading.
			// This ensures that webpack can correctly resolve asset URLs (images, fonts, etc.)
			// regardless of the WordPress install location or folder structure.
			$public_url    = trailingslashit( plugins_url( 'build/', EVENT_TICKETS_MAIN_PLUGIN_FILE ) );
			$inline_script = sprintf( 'window.tecTicketsWebpackPublicPath = %s;', wp_json_encode( $public_url ) );
			wp_add_inline_script( 'tec-tickets-onboarding-wizard-script', $inline_script, 'before' );
	}

	/**
	 * Has the page been dismissed?
	 *
	 * @since 5.23.0
	 *
	 * @return bool
	 */
	public static function is_dismissed(): bool {
		return (bool) tribe_get_option( self::DISMISS_PAGE_OPTION, false );
	}

	/**
	 * Handle the dismissal of the onboarding page.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function handle_onboarding_page_dismiss(): void {
		if ( ! current_user_can( $this->required_capability() ) ) {
			return;
		}

		tribe_update_option( self::DISMISS_PAGE_OPTION, true );

		wp_safe_redirect( add_query_arg( [ 'page' => $this->get_parent_page_slug() ], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Check if the TEC wizard is completed.
	 *
	 * @since 5.23.0
	 * @since 5.24.0 Made the visibility public.
	 *
	 * @return bool
	 */
	public function is_tec_wizard_completed(): bool {
		if ( ! did_action( 'tribe_common_loaded' ) ) {
			return false;
		}

		$settings = tribe( Data::class )->get_wizard_settings();
		$finished = $settings['finished'] ?? false;

		if ( $finished ) {
			return true;
		}

		if ( tribe_get_option( self::DISMISS_PAGE_OPTION ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Force the wizard to display.
	 *
	 * @since 5.23.0
	 *
	 * @return bool
	 */
	protected function force_wizard_display(): bool {
		/**
		 * Filter to force the wizard to display.
		 *
		 * @since 5.23.0
		 * @since 5.24.0 Passing the page object as the second argument.
		 *
		 * @param bool $force Whether to force the wizard to display.
		 * @param self $page  The page object.
		 *
		 * @return bool
		 */
		return apply_filters( 'tec_tickets_onboarding_wizard_force_display', false, $this );
	}

	/**
	 * Check if the wizard should be displayed.
	 *
	 * @since 5.23.0
	 *
	 * @return bool
	 */
	protected function should_show_wizard(): bool {
		/**
		 * Allow users to force-ignore the checks and display the wizard.
		 *
		 * @since 5.23.0
		 *
		 * @param bool $force Whether to force the wizard to display.
		 *
		 * @return bool
		 */
		$force = $this->force_wizard_display();

		if ( $force ) {
			return true;
		}

		$et_versions = (array) tribe_get_option( 'previous_event_tickets_versions', [] );
		// If there is more than one previous version, don't show the wizard.
		if ( count( $et_versions ) > 1 ) {
			return false;
		}

		$data = tribe( Data::class );
		// Don't display if we've finished the wizard.
		if ( $data->get_wizard_setting( 'finished', false ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the admin page title.
	 *
	 * @since 5.23.0
	 *
	 * @return string The page title.
	 */
	public function get_the_page_title(): string {
		return esc_html__( 'TEC Tickets Setup Guide', 'event-tickets' );
	}

	/**
	 * Get the admin menu title.
	 *
	 * @since 5.23.0
	 *
	 * @return string The menu title.
	 */
	public function get_the_menu_title(): string {
		return esc_html__( 'Setup Guide', 'event-tickets' );
	}

	/**
	 * Render the admin page title.
	 * In the header.
	 *
	 * @since 5.23.0
	 *
	 * @return void Renders the admin page title.
	 */
	public function admin_page_title(): void {
		?>
			<h1 class="tec-admin-page__header-title"><?php esc_html_e( 'Event Tickets', 'event-tickets' ); ?></h1>
		<?php
	}

	/**
	 * Get the initial data for the wizard.
	 *
	 * @since 5.23.0
	 *
	 * @return array<string, mixed> The initial data.
	 */
	public function get_initial_data(): array {
		$data         = tribe( Data::class );
		$last_send    = $data->get_wizard_setting( 'last_send', [] );
		$initial_data = [
			/* Wizard History */
			'forceDisplay'         => $this->force_wizard_display(),
			'begun'                => (bool) $data->get_wizard_setting( 'begun', false ),
			'currentTab'           => absint( $data->get_wizard_setting( 'current_tab', 0 ) ),
			'finished'             => (bool) $data->get_wizard_setting( 'finished', false ),
			'completedTabs'        => (array) $data->get_wizard_setting( 'completed_tabs', [] ),
			'skippedTabs'          => (array) $data->get_wizard_setting( 'skipped_tabs', [] ),
			'paymentOption'        => $data->get_wizard_setting( 'payment_option', '' ),
			'currency'             => $last_send['currency'] ?? '',
			'country'              => $last_send['country'] ?? '',
			/* nonces */
			'action_nonce'         => wp_create_nonce( API::NONCE_ACTION ),
			'_wpnonce'             => wp_create_nonce( 'wp_rest' ),
			/* Data */
			'currencies'           => tribe( Currency::class )->get_currency_list(),
			'countries'            => tribe( Country::class )->get_gateway_countries(),
			'optin'                => tribe_get_option( 'opt-in-status', false ),
			'stripeConnected'      => tribe( Stripe_Merchant::class )->is_connected( true ),
			'squareConnected'      => tribe( Square_Merchant::class )->is_connected( true ),
			/* TEC install step */
			'tecInstalled'         => Installer::get()->is_installed( 'the-events-calendar' ),
			'tecActive'            => Installer::get()->is_active( 'the-events-calendar' ),
			'tec-wizard-completed' => $this->is_tec_wizard_completed(),
			/* User info - for Communication Step */
			'userEmail'            => wp_get_current_user()->user_email,
			'userName'             => wp_get_current_user()->display_name,
		];


		/**
		 * Filter the initial data.
		 *
		 * @since 5.23.0
		 *
		 * @param array $initial_data The initial data.
		 * @param self  $page         The page object.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'tribe_tickets_onboarding_wizard_initial_data', $initial_data, $this );
	}

	/**
	 * Render the onboarding wizard trigger.
	 * To show a button, use code similar to below.
	 *
	 * $button = get_submit_button(
	 *     esc_html__( 'Open Install Wizard (current)', 'event-tickets' ),
	 *     'secondary tec-tickets-onboarding-wizard',
	 *     'open',
	 *     true,
	 *     [
	 *         'id'                     => 'tec-tickets-onboarding-wizard',
	 *         'data-container-element' => ,
	 *         'data-wizard-boot-data'  => wp_json_encode( $this->get_initial_data() ),
	 *     ]
	 * );
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function tec_onboarding_wizard_target(): void {
		if ( ! $this->should_show_wizard() ) {
			return;
		}
		?>
		<span
			id="tec-tickets-onboarding-wizard"
			data-container-element="tec-tickets-onboarding-wizard-target"
			data-wizard-boot-data="<?php echo esc_attr( wp_json_encode( $this->get_initial_data() ) ); ?>"
		></span>
		<div class="wrap" id="tec-tickets-onboarding-wizard-target"></div>
		<?php
	}

	/**
	 * Add some wrapper classes to the admin page.
	 *
	 * @since 5.23.0
	 *
	 * @return array The class(es) array.
	 */
	public function content_wrapper_classes(): array {
		$classes   = parent::content_classes();
		$classes[] = 'tec-tickets-admin__content';
		$classes[] = 'tec-tickets__landing-page-content';

		return $classes;
	}

	/**
	 * Render the landing page content.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function admin_page_main_content(): void {
		$this->admin_content_checklist_section();

		$this->admin_content_resources_section();

		$this->tec_onboarding_wizard_target();

		// Remove the transients.
		delete_transient( self::ACTIVATION_REDIRECT_OPTION );
		delete_transient( self::BULK_ACTIVATION_REDIRECT_OPTION );
	}

	/**
	 * Render the checklist section.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function admin_content_checklist_section(): void {
		$settings_url   = 'admin.php?page=tec-tickets-settings';
		$action_url     = add_query_arg( [ 'action' => self::DISMISS_PAGE_ACTION ], admin_url( '/admin-post.php' ) );
		$data           = tribe( Data::class );
		$completed_tabs = array_flip( (array) $data->get_wizard_setting( 'completed_tabs', [] ) );
		$installer      = Installer::get();
		$tec_installed  = $installer->is_installed( 'the-events-calendar' );
		$tec_activated  = $installer->is_active( 'the-events-calendar' );

		$tab_settings   = [
			'payments' => [
				'currency' => tribe_get_option( 'tickets_commerce_enabled', false ) && tribe_get_option( 'tickets-commerce-currency-code', false ),
			],
			'emails'   => [
				'sender_name'  => tribe_get_option( 'tec-tickets-emails-sender-name', false ),
				'sender_email' => tribe_get_option( 'tec-tickets-emails-sender-email', false ),
			],
			'stripe'   => [
				'connected' => tribe_get_option( 'tickets_commerce_enabled', false ) && tribe_get_option( '_tickets_commerce_gateway_enabled_stripe', false ),
			],
		];
		$count_complete = 0;
		foreach ( [ 0, 1, 2 ] as $step ) {
			if ( in_array( $step, $completed_tabs, true ) ) {
				++$count_complete;
			}
		}
		?>
			<section class="tec-admin-page__content-section">
				<div class="tec-admin-page__content-section-header">
					<h2 class="tec-admin-page__content-header"><?php esc_html_e( 'First-time setup', 'event-tickets' ); ?></h2>
					<a class="tec-dismiss-admin-page" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Dismiss this screen', 'event-tickets' ); ?></a>
				</div>
				<div class="tec-admin-page__content-section-subheader"><?php echo esc_html( $count_complete ) . '/3 ' . esc_html__( 'steps completed', 'event-tickets' ); ?></div>
				<ul class="tec-admin-page__content-step-list">
					<li
						id="tec-tickets-onboarding-wizard-currency-item"
						<?php
						tec_classes(
							[
								'step-list__item' => true,
								'tec-tickets-onboarding-step-0' => true,
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[0] ) || ! empty( $tab_settings['payments']['currency'] ),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Currency', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tab=payments#tribe-field-tickets-commerce-currency-code" ) ); ?>" class="tec-admin-page__link">
								<?php esc_html_e( 'Edit currency', 'event-tickets' ); ?>
							</a>
						</div>
					</li>
					<li
						id="tec-tickets-onboarding-wizard-email-item"
						<?php
						tec_classes(
							[
								'step-list__item' => true,
								'tec-tickets-onboarding-step-2' => true,
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[2] )
									|| (
										! empty( $tab_settings['emails']['sender_name'] )
										&& ! empty( $tab_settings['emails']['sender_email'] )
									),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Email communication setup', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tab=emails#tribe-field-tec-tickets-emails-sender-name" ) ); ?>" class="tec-admin-page__link">
								<?php esc_html_e( 'Edit email settings', 'event-tickets' ); ?>
							</a>
						</div>
					</li>
					<li
						id="tec-tickets-onboarding-wizard-stripe-item"
						<?php
						tec_classes(
							[
								'step-list__item' => true,
								'tec-tickets-onboarding-step-1' => true,
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[1] ) || ! empty( $tab_settings['stripe']['connected'] ),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Stripe for online payments', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tab=tickets-commerce" ) ); ?>" class="tec-admin-page__link">
								<?php esc_html_e( 'Edit Stripe settings', 'event-tickets' ); ?>
							</a>
						</div>
					</li>
				</ul>
				<div class="tec-admin-page__content-section-mid">
					<h2 class="tec-admin-page__content-header">
						<?php esc_html_e( 'Create your first ticket', 'event-tickets' ); ?>
					</h2>
					<ul class="tec-admin-page__content-step-list">
						<li class="step-list__item">
							<div class="step-list__item-left">
								<?php esc_html_e( 'Learn how to create a ticket', 'event-tickets' ); ?>
							</div>
							<div class="step-list__item-right">
								<a href="https://theeventscalendar.com/knowledgebase/guide/event-tickets/" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
									<?php esc_html_e( 'Watch Video', 'event-tickets' ); ?>
								</a>
							</div>
						</li>
						<li class="step-list__item">
							<div class="step-list__item-left">
								<?php esc_html_e( 'Learn how to optimize your workflow with ticket presets', 'event-tickets' ); ?>
							</div>
							<div class="step-list__item-right">
								<a href="https://theeventscalendar.com/knowledgebase/guide/event-tickets/" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
									<?php esc_html_e( 'Watch Video', 'event-tickets' ); ?>
								</a>
							</div>
						</li>
						<li class="step-list__item">
							<div class="step-list__item-left">
								<?php esc_html_e( 'Review login requirement for purchasing tickets', 'event-tickets' ); ?>
							</div>
							<div class="step-list__item-right">
								<a href="<?php echo esc_url( admin_url( "{$settings_url}#tec-tickets-settings-authentication" ) ); ?>" class="tec-admin-page__link">
									<?php esc_html_e( 'Go to settings', 'event-tickets' ); ?>
								</a>
							</div>
						</li>
					</ul>
				</div>
				<div id="tec-tickets-onboarding-wizard-calendar">
					<h2 class="tec-admin-page__content-header">
						<?php esc_html_e( 'The Events Calendar', 'event-tickets' ); ?>
					</h2>
					<h3 class="tec-admin-page__content-subheader">
						<?php esc_html_e( 'Full control over your event management needs', 'event-tickets' ); ?>
					</h3>
					<ul class="tec-admin-page__content-step-list">
						<li
							id="tec-tickets-onboarding-wizard-tickets-item"
							<?php
							tec_classes(
								[
									'step-list__item' => true,
									'tec-tickets-onboarding-step-5' => true,
									'tec-admin-page__onboarding-step--completed' => ( isset( $completed_tabs[5] ) || ( $tec_installed && $tec_activated ) ),
								]
							);
							?>
						>
							<div class="step-list__item-left">
								<span class="step-list__item-icon" role="presentation"></span>
								<?php echo $tec_installed ? esc_html__( 'Activate The Events Calendar', 'event-tickets' ) : esc_html__( 'Install The Events Calendar', 'event-tickets' ); ?>
							</div>
							<?php if ( ! $tec_installed || ! $tec_activated ) : ?>
								<div class="step-list__item-right">
									<?php
									Installer::get()->render_plugin_button(
										'the-events-calendar',
										$tec_installed ? 'activate' : 'install',
										$tec_installed ? esc_html__( 'Activate', 'event-tickets' ) : esc_html__( 'Install', 'event-tickets' )
									);
									?>
								</div>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			</section>
		<?php
	}

	/**
	 * Render the resources section.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function admin_content_resources_section(): void {
		$guide_link     = 'https://theeventscalendar.com/knowledgebase/guide/event-tickets/';
		$attendees_link = 'https://theeventscalendar.com/knowledgebase/tickets-managing-your-orders-and-attendees/';
		$customize_link = 'https://theeventscalendar.com/knowledgebase/tickets-emails-template-files/';
		$rsvps_link     = 'https://theeventscalendar.com/knowledgebase/event-tickets-using-rsvps/';
		$shortcodes     = 'https://theeventscalendar.com/knowledgebase/shortcodes/';
		?>
		<div class="tec-admin-page__content-section">
			<h2 class="tec-admin-page__content-header">
				<?php esc_html_e( 'Useful Resources', 'event-tickets' ); ?>
			</h2>
			<ul>
				<li>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $guide_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Event Tickets guide', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
				<li>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $attendees_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Managing Orders and Attendees', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
				<li>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $customize_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Customizing Ticket Emails', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
				<li>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $rsvps_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Using RSVPs', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
				<li>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $shortcodes ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Shortcodes', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
			</ul>
		</div>
		<?php
	}

	/**
	 * Render the admin page sidebar.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function admin_page_sidebar_content(): void {
		// no op.
	}

	/**
	 * Render the admin page footer.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function admin_page_footer_content(): void {
		// no op.
	}
}
