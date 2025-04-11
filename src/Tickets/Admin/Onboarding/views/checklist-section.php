<?php
/**
 * Checklist section template.
 *
 * @since TBD
 *
 * @var \Tribe\Tickets\Admin\Onboarding\Template $this       The template instance.
 * @var \Tribe\Tickets\Admin\Onboarding\Installer $installer The installer instance.
 */

?>
<div class="tec-admin-page__content-section tec-tickets-admin-page__content-section">
	<?php

	$this->template( 'checklist-section/header' );

	$this->template( 'checklist-section/step-list' );

	$this->template( 'checklist-section/tec-install' );
	?>
</div>
