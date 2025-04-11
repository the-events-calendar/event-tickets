<?php
/**
 * Step list template.
 *
 * @since TBD
 *
 * @var array<string,mixed>                       $list_items The list items.
 * @var \Tribe\Tickets\Admin\Onboarding\Template  $this       The template instance.
 * @var \Tribe\Tickets\Admin\Onboarding\Installer $installer  The installer instance.
 */

?>
<ul class="tec-admin-page__content-step-list">
	<?php foreach ( $list_items as $item ) : ?>
		<?php $this->template( 'checklist-section/step-list/list-item', $item ); ?>
	<?php endforeach; ?>
</ul>
