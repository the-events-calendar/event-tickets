<?php
/**
 * Settings back link.
 *
 * @since 5.5.9
 * @since 5.23.0 Added new classes for settings.
 *
 * @var bool    $url   Link URL.
 * @var string  $text  Link text.
 */

if ( empty( $url ) || empty( $text ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-back-link-wrapper tec-settings-form__header-block--horizontal">
	<a class="tec-tickets__admin-settings-back-link" href="<?php echo esc_attr( $url ); ?>" role="link">
		&larr; <?php echo esc_html( $text ); ?>
	</a>
</div>
