<?php
/**
 * Handles the landing page of the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\StellarWP\Installer\Installer;
use TEC\Common\Admin\Abstract_Admin_Page;
use TEC\Common\Admin\Traits\Is_Tickets_Page;
use TEC\Common\Lists\Currency;
use TEC\Tickets\Admin\Onboarding\API;
use TEC\Common\Asset;
use TEC\Tickets\Admin\Onboarding\Template;

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
	public static bool $has_logo = false;

	/**
	 * The position of the submenu in the menu.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public int $menu_position = 1;

	/**
	 * The template instance.
	 *
	 * @since TBD
	 *
	 * @var Template
	 */
	protected Template $template;

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
		<a class="tec-dismiss-onboarding-screen" href="<?php echo esc_url( $action_url ); ?>"><?php esc_html_e( 'Dismiss this screen', 'event-tickets' ); ?></a>
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

		wp_safe_redirect( admin_url( $this->get_parent_page_slug() ) );
		exit;
	}

	/**
	 * Render the landing page content.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_page_main_content(): void {
		$installer = Installer::get();

		$this->template = tribe( Template::class );

		$this->admin_content_checklist_section();

		$this->admin_content_resources_section();

		$this->tec_onboarding_wizard_target();

		// Stop redirecting if the user has visited the Guided Setup page.
		tribe_update_option( self::VISITED_GUIDED_SETUP_OPTION, true );
	}

	/**
	 * Render the checklist section.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function admin_content_checklist_section(): void {
		$data           = tribe( Data::class );
		$completed_tabs = array_flip( (array) $data->get_wizard_setting( 'completed_tabs', [] ) );

		$this->template->template(
			'checklist-section',
			[
				'data'       => $data,
				'installer'  => Installer::get(),
				'list_items' => $this->get_list_items( $completed_tabs ),
			]
		);
		?>

		<?php
	}

	/**
	 * Get the list items for the checklist section.
	 *
	 * @since TBD
	 *
	 * @param array $completed_tabs The completed tabs.
	 *
	 * @return array The list items.
	 */
	public function get_list_items( array $completed_tabs ): array {
		$settings_url = '';

		return [
			[
				'id'        => 'tec-tickets-onboarding-wizard-currency-item',
				'classes'   => [
					'tec-tickets-onboarding-step-1',
					'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[1] ) || ! empty( tribe_get_option( 'defaultCurrencyCode' ) ),
				],
				'title'     => __( 'Location & Currency', 'event-tickets' ),
				'link'      => admin_url( "{$settings_url}&tab=display-currency-tab" ),
				'link_text' => __( 'Edit currency', 'event-tickets' ),
			],
			[
				'id'        => 'tec-tickets-onboarding-wizard-email-item',
				'classes'   => [
					'tec-tickets-onboarding-step-2',
					'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[2] ) || ! empty( tribe_get_option( 'defaultCurrencyCode' ) ),
				],
				'title'     => __( 'Email communication setup', 'event-tickets' ),
				'link'      => admin_url( "{$settings_url}&tab=display-currency-tab" ),
				'link_text' => __( 'Edit email settings', 'event-tickets' ),
			],
			[
				'id'        => 'tec-tickets-onboarding-wizard-stripe-item',
				'classes'   => [
					'tec-tickets-onboarding-step-3',
					'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[3] ) || ! empty( tribe_get_option( 'defaultCurrencyCode' ) ),
				],
				'title'     => __( 'Stripe payment setup', 'event-tickets' ),
				'link'      => admin_url( "{$settings_url}&tab=display-currency-tab" ),
				'link_text' => __( 'Edit Stripe settings', 'event-tickets' ),
			],
			[
				'id'        => 'tec-tickets-onboarding-wizard-square-item',
				'classes'   => [
					'tec-tickets-onboarding-step-3',
					'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[3] ) || ! empty( tribe_get_option( 'defaultCurrencyCode' ) ),
				],
				'title'     => __( 'Square for in-person and online payments', 'event-tickets' ),
				'link'      => admin_url( "{$settings_url}&tab=display-currency-tab" ),
				'link_text' => __( 'Edit Square settings', 'event-tickets' ),
			],
			[
				'id'        => 'tec-tickets-onboarding-wizard-login-item',
				'classes'   => [
					'tec-tickets-onboarding-step-4',
					'tec-admin-page__onboarding-step--completed' => isset( $completed_tabs[4] ) || ! empty( tribe_get_option( 'dateWithYearFormat' ) ),
				],
				'title'     => __( 'Login requirement for purchasing tickets', 'event-tickets' ),
				'link'      => admin_url( "{$settings_url}&tab=display-currency-tab" ),
				'link_text' => __( 'Edit', 'event-tickets' ),
			],
		];
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
		$customize_link = 'https://theeventscalendar.com/knowledgebase/guide/customization/';
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
			'begun'         => (bool) $data->get_wizard_setting( 'begun', false ),
			'currentTab'    => absint( $data->get_wizard_setting( 'current_tab', 0 ) ),
			'finished'      => (bool) $data->get_wizard_setting( 'finished', false ),
			'completedTabs' => (array) $data->get_wizard_setting( 'completed_tabs', [] ),
			'skippedTabs'   => (array) $data->get_wizard_setting( 'skipped_tabs', [] ),
			/* nonces */
			'action_nonce'  => wp_create_nonce( API::NONCE_ACTION ),
			'_wpnonce'      => wp_create_nonce( 'wp_rest' ),
			/* Data */
			'currencies'    => tribe( Currency::class )->get_currency_list(),
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
		$force        = apply_filters( 'tec_tickets_onboarding_wizard_force', false );
		$tec_versions = (array) tribe_get_option( 'previous_etp_versions', [] );
		// If there is more than one previous version, don't show the wizard.
		if ( ! $force && count( $tec_versions ) > 1 ) {
			return;
		}

		$data = tribe( Data::class );
		// Don't display if we've finished the wizard.
		if ( ! $force && $data->get_wizard_setting( 'finished', false ) ) {
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
	 * Register the assets for the landing page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_assets(): void {
		Asset::add(
			'tec-tickets-onboarding-wizard-script',
			'index.js'
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
			'index.css'
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
