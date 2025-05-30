<?php
/**
 * View: Switch Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/admin-views/components/switch-field.php
 *
 * See more documentation about our views templating system.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @link    http://evnt.is/1aiy
 *
 * @var string $id      The ID of the field.
 * @var string $name    The name of the field.
 * @var string $label   The label of the field.
 * @var string $tooltip The tooltip for the field.
 * @var string $value   The value of the field.
 */

?>
<fieldset id="tec-field-<?php echo esc_attr( $id ); ?>" class="tribe-field tribe-field-text tribe-size-medium tribe-field-switch">
	<span class="tribe-field-switch-inner-wrap">
		<legend class="tribe-field-label">
			<?php echo esc_html( $label ); ?>
			<?php if ( $tooltip ) : ?>
				<div class="tribe-tooltip event-helper-text" aria-expanded="false">
					<span class="dashicons dashicons-info"></span>
					<div class="down">
						<p>
							<?php echo wp_kses( $tooltip, [ 'a' => [ 'href' => [] ] ] ); ?>
						</p>
					</div>
				</div>
			<?php endif; ?>
		</legend>
		<div class="tribe-field-wrap">
			<?php
			$this->template( 'components/switch',
				[
					'id'            => $id,
					'label'         => $label,
					'classes_wrap'  => [ 'tec-tickets-settings-switch-control' ],
					'classes_input' => [ 'tec-tickets-settings-switch__input' ],
					'classes_label' => [ 'tec-tickets-settings-switch__label' ],
					'name'          => $name,
					'value'         => 1,
					'checked'       => $value,
					'attrs'         => [],
				]
			);
			?>
		</div>
	</span>
</fieldset>
