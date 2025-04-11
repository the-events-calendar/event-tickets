<?php
/**
 * Step list item template.
 *
 * @since TBD
 *
 * @var \Tribe\Tickets\Admin\Onboarding\Template  $this      The template instance.
 * @var \Tribe\Tickets\Admin\Onboarding\Installer $installer The installer instance.
 *
 * @var string  $id            The ID of the step list item.
 * @var array   $classes       The classes for the step list item.
 * @var string  $title         The title of the step list item.
 * @var string  $link          The link for the step list item.
 * @var string  $link_text     The text for the step list item link.
 */

$classes = array_merge( $classes, [ 'step-list__item' ] );
if ( $id === 'tec-tickets-onboarding-wizard-events-item' ) {
	$foo = 'bar';
}
?>
<li
	id="<?php echo esc_attr( $id ); ?>"
	<?php tribe_classes( $classes ); ?>
>
	<div class="step-list__item-left">
		<span class="step-list__item-icon" role="presentation"></span>
		<?php echo esc_html( $title ); ?>
	</div>
	<div class="step-list__item-right">
		<a href="<?php echo esc_url( $link ); ?>" class="tec-admin-page__link">
			<?php echo esc_html( $link_text ); ?>
		</a>
	</div>
</li>
