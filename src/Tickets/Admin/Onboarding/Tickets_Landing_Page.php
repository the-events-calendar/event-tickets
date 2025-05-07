<?php
/**
 * Handles the landing page of the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding;

use Tribe__Main;
use TEC\Common\StellarWP\Installer\Installer;
use TEC\Common\Admin\Abstract_Admin_Page;
use TEC\Common\Admin\Traits\Is_Tickets_Page;
use TEC\Common\Lists\Currency;
use TEC\Common\Lists\Country;
use TEC\Common\Asset;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Tickets\Admin\Onboarding\Data;
use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;

/**
 * Class Landing_Page
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Tickets_Landing_Page extends Abstract_Admin_Page {
	use Is_Tickets_Page;

	/**
	 * The action to dismiss the onboarding page.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const DISMISS_PAGE_ACTION = 'tec_tickets_dismiss_onboarding_page';

	/**
	 * The option to dismiss the onboarding page.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const DISMISS_PAGE_OPTION = 'tec_tickets_onboarding_page_dismissed';

	/**
	 * The option to mark the guided setup as visited.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const VISITED_GUIDED_SETUP_OPTION = 'tec_tickets_onboarding_wizard_visited_guided_setup';

	/**
	 * The option to redirect to the guided setup after bulk activation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const BULK_ACTIVATION_REDIRECT_OPTION = '_tec_tickets_wizard_redirect';

	/**
	 * The option to redirect to the guided setup after single activation.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const ACTIVATION_REDIRECT_OPTION = '_tec_tickets_activation_redirect';

	/**
	 * The slug for the admin menu.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $slug = 'tickets-setup';

	/**
	 * Whether the page has been dismissed.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static bool $is_dismissed = false;

	/**
	 * Whether the page has a header.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static bool $has_header = true;

	/**
	 * Whether the page has a sidebar.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static bool $has_sidebar = true;

	/**
	 * Whether the page has a footer.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static bool $has_footer = false;

	/**
	 * Whether the page has a logo.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	public static bool $has_logo = true;

	/**
	 * The position of the submenu in the menu.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $menu_position = 100;

	/**
	 * Get the admin page title.
	 *
	 * @since TBD
	 *
	 * @return string The page title.
	 */
	public function get_the_page_title(): string {
		return esc_html__( 'TEC Tickets Setup Guide', 'event-tickets' );
	}

	/**
	 * Has the page been dismissed?
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function is_dismissed(): bool {
		return (bool) tribe_get_option( self::DISMISS_PAGE_OPTION, false );
	}

	/**
	 * Get the admin menu title.
	 *
	 * @since TBD
	 *
	 * @return string The menu title.
	 */
	public function get_the_menu_title(): string {
		return esc_html__( 'Setup Guide', 'event-tickets' );
	}

	/**
	 * Add some wrapper classes to the admin page.
	 *
	 * @since TBD
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
	 * Render the admin page title.
	 * In the header.
	 *
	 * @since TBD
	 *
	 * @return void Renders the admin page title.
	 */
	public function admin_page_title(): void {
		?>
			<h1 class="tec-admin__header-title"><?php esc_html_e( 'Event Tickets', 'event-tickets' ); ?></h1>
		<?php

		$action_url = add_query_arg(
			// We do not need a nonce. This page can be seen only by admins. see `required_capability` method.
			[ 'action' => self::DISMISS_PAGE_ACTION ],
			admin_url( '/admin-post.php' )
		);
		?>
		<a class="tec-dismiss-admin-page" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Dismiss this screen', 'event-tickets' ); ?></a>
		<?php
	}

	/**
	 * Handle the dismissal of the onboarding page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function handle_onboarding_page_dismiss(): void {
		if ( ! current_user_can( $this->required_capability() ) ) {
			return;
		}

		tribe_update_option( self::DISMISS_PAGE_OPTION, true );

		wp_safe_redirect( add_query_arg( array( 'page' => $this->get_parent_page_slug() ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function logo_source( $source ): string {
		if ( ! $this->is_on_page() ) {
			return $source;
		}

		return tribe_resource_url( 'images/logo/the-events-calendar.svg', false, null, Tribe__Main::instance() );
	}

	/**
	 * Render the landing page content.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_page_main_content(): void {
		$this->admin_content_checklist_section();

		$this->admin_content_resources_section();

		$this->tec_onboarding_wizard_target();

		// Stop redirecting if the user has visited the Guided Setup page.
		tribe_update_option( self::VISITED_GUIDED_SETUP_OPTION, true );
		delete_transient( self::ACTIVATION_REDIRECT_OPTION );
	}

	/**
	 * Render the checklist section.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_content_checklist_section(): void {
		$settings_url   = 'admin.php?page=tec-tickets-settings';
		$data           = tribe( Data::class );
		$completed_tabs = array_flip( (array) $data->get_wizard_setting( 'completed_tabs', [] ) );
		$installer      = Installer::get();
		$tec_installed  = $installer->is_installed( 'the-events-calendar' );
		$tec_activated  = $installer->is_active( 'the-events-calendar' );
		?>
			<div class="tec-admin-page__content-section tec-tickets-admin-page__content-section">
				<h2 class="tec-admin-page__content-header"><?php esc_html_e( 'Tickets setup', 'event-tickets' ); ?></h2>
				<ul class="tec-admin-page__content-step-list">
					<li
						id="tec-tickets-onboarding-wizard-currency-item"
						<?php
						tec_classes(
							[
								'step-list__item' => true,
								'tec-tickets-onboarding-step-1' => true,
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[1] ) || ! empty( tribe_get_option( 'defaultCurrencyCode' ) ),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Currency', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tab=payments" ) ); ?>" class="tec-admin-page__link">
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
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[2] ) || ! empty( tribe_get_option( 'dateWithYearFormat' ) ),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Email communication setup', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tab=emails" ) ); ?>" class="tec-admin-page__link">
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
								'tec-tickets-onboarding-step-3' => true,
								'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[3] ) || ! empty( tribe_get_option( 'dateWithYearFormat' ) ),
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Stripe for online payments', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( "{$settings_url}&tc-section=stripe&tab=payments" ) ); ?>" class="tec-admin-page__link">
								<?php esc_html_e( 'Edit Stripe settings', 'event-tickets' ); ?>
							</a>
						</div>
					</li>
					<li
						id="tec-tickets-onboarding-wizard-login-item"
						<?php
						tec_classes(
							[
								'step-list__item' => true,
								'tec-admin-page__onboarding-step--completed' => false,
							]
						);
						?>
					>
						<div class="step-list__item-left">
							<span class="step-list__item-icon" role="presentation"></span>
							<?php esc_html_e( 'Login requirement for purchasing tickets', 'event-tickets' ); ?>
						</div>
						<div class="step-list__item-right">
							<a href="<?php echo esc_url( admin_url( $settings_url ) ); ?>" class="tec-admin-page__link">
								<?php esc_html_e( 'Edit', 'event-tickets' ); ?>
							</a>
						</div>
					</li>
				</ul>
				<div id="tec-tickets-onboarding-wizard-calendar">
					<h2 class="tec-admin-page__content-header">
						<?php esc_html_e( 'The Events Calendar', 'event-tickets' ); ?>
					</h2>
					<h3 class="tec-admin-page__content-subheader">
						<?php esc_html_e( 'Do you need events for your tickets?', 'event-tickets' ); ?>
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
								<?php esc_html_e( 'The Events Calendar', 'event-tickets' ); ?>
							</div>
							<?php if ( ! $tec_installed || ! $tec_activated ) : ?>
								<div class="step-list__item-right">
									<?php
									Installer::get()->render_plugin_button(
										'the-events-calendar',
										$tec_installed ? 'activate' : 'install',
										$tec_installed ? __( 'Activate The Events Calendar', 'event-tickets' ) : __( 'Install The Events Calendar', 'event-tickets' )
									);
									?>
								</div>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			</div>
		<?php
	}

	/**
	 * Render the resources section.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_content_resources_section(): void {
		$chatbot_link   = admin_url( 'admin.php?page=tec-tickets-help' );
		$guide_link     = 'https://theeventscalendar.com/knowledgebase/guide/event-tickets/';
		$customize_link = 'https://theeventscalendar.com/knowledgebase/ticket-rsvp-template-files/';
		?>
		<div class="tec-admin-page__content-section">
			<h2 class="tec-admin-page__content-header">
				<?php esc_html_e( 'Useful Resources', 'event-tickets' ); ?>
			</h2>
			<ul>
				<li>
					<span class="tec-admin-page__icon tec-admin-page__icon--stars" role="presentation"></span>
					<a href="<?php echo esc_url( $chatbot_link ); ?>" class="tec-admin-page__link">
						<?php esc_html_e( 'Ask our AI Chatbot anything', 'event-tickets' ); ?>
					</a>
				</li>
				<li>
					<span class="tec-admin-page__icon tec-admin-page__icon--book" role="presentation"></span>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $guide_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Event Tickets guide', 'event-tickets' ); ?>
						</a>
					</span>
				</li>
				<li>
					<span class="tec-admin-page__icon tec-admin-page__icon--customize" role="presentation"></span>
					<span class="tec-admin-page__link--external">
						<a href="<?php echo esc_url( $customize_link ); ?>" class="tec-admin-page__link" target="_blank" rel="nofollow noopener">
							<?php esc_html_e( 'Customize styles and templates', 'event-tickets' ); ?>
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
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_page_sidebar_content(): void {
		?>
			<section class="tec-admin-page__sidebar-section has-icon">
				<span class="tec-admin-page__icon tec-admin-page__sidebar-icon tec-admin-page__icon--stars" role="presentation"></span>
				<div>
					<h3 class="tec-admin-page__sidebar-header"><?php esc_html_e( 'Our AI Chatbot is here to help you', 'event-tickets' ); ?></h3>
					<p><?php esc_html_e( 'You have questions? The TEC Chatbot has the answers.', 'event-tickets' ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=tec-tickets-help' ) ); ?>" class="tec-admin-page__link"><?php esc_html_e( 'Talk to TEC Chatbot', 'event-tickets' ); ?></a></p>
				</div>
			</section>
			<section class="tec-admin-page__sidebar-section has-icon">
				<span class="tec-admin-page__icon tec-admin-page__sidebar-icon tec-admin-page__icon--chat" role="presentation"></span>
				<div>
					<h2 class="tec-admin-page__sidebar-header"><?php esc_html_e( 'Get priority live support', 'event-tickets' ); ?></h2>
					<p><?php esc_html_e( 'You can get live support from The Events Calendar team if you have an active license for one of our products.', 'event-tickets' ); ?></p>
					<p><span class="tec-admin-page__link--external"><a href="https://theeventscalendar.com/knowledgebase/priority-support-through-the-tec-support-hub" target="_blank" rel="nofollow noopener" class="tec-admin-page__link"><?php esc_html_e( 'Learn how to get an active license', 'event-tickets' ); ?></a></span></p>
				</div>
			</section>
		<?php
	}

	/**
	 * Render the admin page footer.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_page_footer_content(): void {
		// no op.
	}

	/**
	 * Check if the TEC wizard is completed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function is_tec_wizard_completed(): bool {
		if ( ! did_action( 'tribe_common_loaded' ) ) {
			return false;
		}

		$settings = tribe( Data::class )->get_wizard_settings();
		$finished  = $settings['finished'] ?? false;

		if ( $finished ) {
			return true;
		}

		if ( tribe_get_option( self::DISMISS_PAGE_OPTION ) ) {
			return true;
		}

		if ( tribe_get_option( self::VISITED_GUIDED_SETUP_OPTION ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the initial data for the wizard.
	 *
	 * @since TBD
	 *
	 * @return array<string, mixed> The initial data.
	 */
	public function get_initial_data(): array {
		$data         = tribe( Data::class );
		$initial_data = [
			/* Wizard History */
			'begun'                     => (bool) $data->get_wizard_setting( 'begun', false ),
			'currentTab'                => absint( $data->get_wizard_setting( 'current_tab', 0 ) ),
			'finished'                  => (bool) $data->get_wizard_setting( 'finished', false ),
			'completedTabs'             => (array) $data->get_wizard_setting( 'completed_tabs', [] ),
			'skippedTabs'               => (array) $data->get_wizard_setting( 'skipped_tabs', [] ),
			'paymentOption'             => $data->get_wizard_setting( 'payment_option', '' ),
			/* nonces */
			'action_nonce'              => wp_create_nonce( API::NONCE_ACTION ),
			'_wpnonce'                  => wp_create_nonce( 'wp_rest' ),
			/* Data */
			'currencies'                => tribe( Currency::class )->get_currency_list(),
			'countries'                 => tribe( Country::class )->get_gateway_countries(),
			'optin'                     => tribe_get_option( 'opt-in-status', false ),
			'stripeConnected'           => tribe( Merchant::class )->is_connected( true ),
			/* TEC install step */
			'events-calendar-installed' => Installer::get()->is_installed( 'the-events-calendar' ),
			'events-calendar-active'    => Installer::get()->is_active( 'the-events-calendar' ),
			'tec-wizard-completed'      => $this->is_tec_wizard_completed(),
		];


		/**
		 * Filter the initial data.
		 *
		 * @since TBD
		 *
		 * @param array      $initial_data The initial data.
		 * @param Controller $controller   The controller object.
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
	 * @since TBD
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
	 * Check if the wizard should be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function should_show_wizard(): bool {
		/**
		 * Allow users to force-ignore the checks and display the wizard.
		 *
		 * @since TBD
		 *
		 * @param bool $force Whether to force the wizard to display.
		 *
		 * @return bool
		 */
		$force = apply_filters( 'tec_tickets_onboarding_wizard_force_display', false );

		if ( $force ) {
			return true;
		}

		$et_versions = (array) tribe_get_option( 'previous_etp_versions', [] );
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
	 * Register the assets for the landing page.
	 *
	 * @since TBD
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
			->set_condition( [ __CLASS__, 'is_on_page' ] )
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
			->set_condition( [ __CLASS__, 'is_on_page' ] )
			->use_asset_file( false )
			->set_dependencies( 'wp-components', 'tribe-common-admin' )
			->register();
	}
}
