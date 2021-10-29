<?php
/**
 * Tickets Commerce styled, generic banner.
 *
 * @since TBD
 *
 * @var string $banner_title   Banner title.
 * @var string $banner_content Banner content text or HTML.
 * @var string $button_text    Button text.
 * @var string $button_url     Button URL.
 * @var string $link_text      Link text.
 * @var string $link_url       Link URL.
 * @var bool   $show_new       Show "New!" badge.
 */

$show_new_badge = isset( $show_new ) && $show_new === true;
$show_button = ! empty( $button_text ) && ! empty( $button_url );
$show_link = ! empty( $link_text ) && ! empty( $link_url );

?>
<div class="event-tickets__admin-banner event-tickets__admin-tc_banner">
    <div class="event-tickets__admin-tc_banner-header">
        <h4><?php echo esc_html( $banner_title ); ?></h4>
        <?php if ( $show_new_badge ) : ?>
            <span class="event-tickets__admin-banner-links-link-label--new">
                <?php esc_html_e( 'New!', 'event-tickets' ); ?>
            </span>
        <?php endif; ?>
    </div>
	<p class="event-tickets__admin-banner-help-text"><?php echo wp_kses( $banner_content, 'post' ); ?></p>
    <?php if ( $show_button || $show_link ) : ?>
        <div class="event-tickets__admin-tc_banner-footer">
            <?php if ( $show_button ) : ?>
                <a href="<?php echo esc_url( $button_url ); ?>" class="event-tickets__admin-tc_banner-button">
                    <?php echo esc_html( $button_text ); ?>
                </a>
            <?php endif; ?>
            <?php if ( $show_link ) : ?>
                <a 
                    href="<?php echo esc_url( $link_url ); ?>" 
                    class="event-tickets__admin-tc_banner-link"
                    rel="noopener noreferrer"
                    target="_blank"
                >
                    <?php echo esc_html( $link_text ); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
