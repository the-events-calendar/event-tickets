<?php
/**
 * The options of one boundary of a window in the classic ticket form: its mode, its relative date and its specific date.
 *
 * Every window kind renders its boundaries with this markup; the kind's template gives the copy and the date inputs. The
 * mode and relative inputs have no `name`: the kind's template sends the rule as JSON in its own field.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var Window_Kind                                                      $window_kind       The kind of the window.
 * @var string                                                           $boundary_end      The boundary, `start` or `end`.
 * @var array{mode: string, value: int, unit: int, anchor?: string}      $boundary_fields   The mode and relative values the boundary shows.
 * @var string                                                           $field_prefix      The prefix of the ids of the window's inputs.
 * @var array<string,string>                                             $mode_labels       The label of each mode the boundary offers, keyed by the mode.
 * @var array{relative?: string, value: string, unit: string, anchor?: string, date: string} $boundary_labels The text that opens the relative date, if any, and the accessible names of the inputs.
 * @var string                                                           $date_input_id     The id of the input of the specific date.
 * @var bool                                                             $shows_helper_text Whether the relative inputs are described by a helper text the script writes.
 * @var callable(string): void                                           $render_date_inputs Renders the inputs of the specific date of the boundary it is given, `start` or `end`.
 */

use TEC\Tickets\Relative_Sale_Dates\Boundary;
use TEC\Tickets\Relative_Sale_Dates\Rule;
use TEC\Tickets\Relative_Sale_Dates\Window_Kind;

defined( 'ABSPATH' ) || die();

$boundary_id = $field_prefix . '_' . $boundary_end;

/*
 * The unit names follow the number, as the script does when it changes; the msgids and context match the script's so
 * one translation serves both.
 */
$unit_names = [
	MINUTE_IN_SECONDS => _nx( 'minute', 'minutes', $boundary_fields['value'], 'Unit of a relative ticket sale date.', 'event-tickets' ),
	HOUR_IN_SECONDS   => _nx( 'hour', 'hours', $boundary_fields['value'], 'Unit of a relative ticket sale date.', 'event-tickets' ),
	DAY_IN_SECONDS    => _nx( 'day', 'days', $boundary_fields['value'], 'Unit of a relative ticket sale date.', 'event-tickets' ),
	WEEK_IN_SECONDS   => _nx( 'week', 'weeks', $boundary_fields['value'], 'Unit of a relative ticket sale date.', 'event-tickets' ),
];

$anchor_names = [
	Rule::ANCHOR_START => _x( 'before the event starts', 'What a relative ticket sale date is measured from.', 'event-tickets' ),
	Rule::ANCHOR_END   => _x( 'before the event ends', 'What a relative ticket sale date is measured from.', 'event-tickets' ),
];

/*
 * The line that includes this template indents its first tag. The control tags of the optional parts sit at the start
 * of their lines, so the markup keeps its indentation whether or not a part is shown.
 */
?>
<select
			class="tribe-dependency"
			id="<?php echo esc_attr( $boundary_id ); ?>_mode"
		>
			<?php foreach ( $window_kind->get_modes( $boundary_end ) as $boundary_mode ) : ?>
				<option value="<?php echo esc_attr( $boundary_mode ); ?>" <?php selected( $boundary_fields['mode'], $boundary_mode ); ?>><?php echo esc_html( $mode_labels[ $boundary_mode ] ); ?></option>
			<?php endforeach; ?>
		</select>
		<div
			class="tribe-dependent tec-tickets-relative-sale-dates__relative"
			data-depends="#<?php echo esc_attr( $boundary_id ); ?>_mode"
			data-condition="<?php echo esc_attr( Rule::MODE_RELATIVE ); ?>"
		>
<?php if ( isset( $boundary_labels['relative'] ) ) : ?>
			<span><?php echo esc_html( $boundary_labels['relative'] ); ?></span>
<?php endif; ?>
			<input
				type="number"
				min="<?php echo esc_attr( Boundary::MIN_VALUE ); ?>"
				max="<?php echo esc_attr( $window_kind->get_max_value() ); ?>"
				step="1"
				id="<?php echo esc_attr( $boundary_id ); ?>_value"
				value="<?php echo esc_attr( $boundary_fields['value'] ); ?>"
				aria-label="<?php echo esc_attr( $boundary_labels['value'] ); ?>"
<?php if ( $shows_helper_text ) : ?>
				aria-describedby="<?php echo esc_attr( $boundary_id ); ?>_helper"
<?php endif; ?>
			/>
			<select
				id="<?php echo esc_attr( $boundary_id ); ?>_unit"
				aria-label="<?php echo esc_attr( $boundary_labels['unit'] ); ?>"
<?php if ( $shows_helper_text ) : ?>
				aria-describedby="<?php echo esc_attr( $boundary_id ); ?>_helper"
<?php endif; ?>
			>
				<?php foreach ( $window_kind->get_units() as $unit ) : ?>
					<option value="<?php echo esc_attr( $unit ); ?>" <?php selected( $boundary_fields['unit'], $unit ); ?>><?php echo esc_html( $unit_names[ $unit ] ); ?></option>
				<?php endforeach; ?>
			</select>
<?php if ( $window_kind->takes_anchor() ) : ?>
			<select
				id="<?php echo esc_attr( $boundary_id ); ?>_anchor"
				aria-label="<?php echo esc_attr( $boundary_labels['anchor'] ); ?>"
<?php if ( $shows_helper_text ) : // phpcs:ignore Generic.WhiteSpace.ScopeIndent.Incorrect -- Indenting the tag would indent the markup. ?>
				aria-describedby="<?php echo esc_attr( $boundary_id ); ?>_helper"
<?php endif; ?>
			>
				<?php foreach ( $window_kind->get_anchors() as $anchor ) : ?>
					<option value="<?php echo esc_attr( $anchor ); ?>" <?php selected( $boundary_fields['anchor'], $anchor ); ?>><?php echo esc_html( $anchor_names[ $anchor ] ); ?></option>
				<?php endforeach; ?>
			</select>
<?php else : ?>
			<span><?php echo esc_html( $anchor_names[ $window_kind->get_anchors()[0] ] ); ?></span>
<?php endif; ?>
<?php if ( $shows_helper_text ) : ?>
			<span
				class="tec-tickets-relative-sale-dates__helper"
				id="<?php echo esc_attr( $boundary_id ); ?>_helper"
				aria-live="polite"
			></span>
<?php endif; ?>
		</div>
		<div
			class="tribe-dependent tec-tickets-relative-sale-dates__specific"
			data-depends="#<?php echo esc_attr( $boundary_id ); ?>_mode"
			data-condition="<?php echo esc_attr( Rule::MODE_SPECIFIC ); ?>"
		>
			<label class="screen-reader-text" for="<?php echo esc_attr( $date_input_id ); ?>">
				<?php echo esc_html( $boundary_labels['date'] ); ?>
			</label>
			<?php $render_date_inputs( $boundary_end ); ?>
		</div>
