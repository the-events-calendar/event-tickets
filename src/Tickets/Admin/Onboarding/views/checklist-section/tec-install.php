<?php
/**
 * TEC install section template.
 *
 * @since TBD
 *
 * @var \Tribe\Tickets\Admin\Onboarding\Template  $this      The template instance.
 * @var \Tribe\Tickets\Admin\Onboarding\Installer $installer The installer instance.
 */

use TEC\Common\StellarWP\Installer\Installer;

$tec_installed = Installer::get()->is_installed( 'the-events-calendar' );
$tec_activated = Installer::get()->is_active( 'the-events-calendar' );
$step_title         = $tec_installed ?
	_x( 'Activate The Events Calendar', 'Activate label for the installer button.', 'event-tickets' )
	: _x( 'Install The Events Calendar', 'Install label for the installer button.', 'event-tickets' );
?>
<div id="tec-tickets-onboarding-wizard-calendar">
	<h2 class="tec-admin-page__content-header">
		<?php esc_html_e( 'The Events Calendar', 'event-tickets' ); ?>
	</h2>
	<h3 class="tec-admin-page__content-subheader">
		<?php esc_html_e( 'Full control over your event management needs', 'event-tickets' ); ?>
	</h3>
	<ul class="tec-admin-page__content-step-list">
		<li
			id="tec-tickets-onboarding-wizard-events-item"
			<?php
			tribe_classes(
				[
					'step-list__item',
					'tec-tickets-onboarding-step-5',
					'tec-admin-page__onboarding-step--completed' => ( $tec_activated ),
				]
			);
			?>
		>
			<div class="step-list__item-left">
				<span class="step-list__item-icon" role="presentation"></span>
				<?php echo esc_html( $step_title ); ?>
			</div>
			<div class="step-list__item-right">
				<?php
				if ( ! $tec_installed ) {
					$this->template( 'checklist-section/tec-install/install-button' );
				} elseif ( ! $tec_activated ) {
					$this->template( 'checklist-section/tec-install/activate-button' );
				}
				?>
			</div>
		</li>
	</ul>
</div>
