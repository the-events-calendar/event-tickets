/**
 * A stand-in for `@wordpress/components`, which is not installed: the block editor provides it at runtime.
 *
 * Native controls that keep the props are enough to read and drive the window options.
 *
 * @since TBD
 */
const React = require( 'react' );

/**
 * A select that keeps the props of `SelectControl`, enough to read and drive the options.
 *
 * @param {Object}   props          The control props.
 * @param {string}   props.label    The label.
 * @param {string}   props.value    The selected value.
 * @param {Object[]} props.options  The options.
 * @param {Function} props.onChange Called with the new value.
 * @param {Object}   props.help     The help the control describes itself with.
 *
 * @return {Object} The select.
 */
const SelectControl = ( { label, value, options, onChange, help } ) => (
	<>
		<select aria-label={ label } value={ value } onChange={ ( event ) => onChange( event.target.value ) }>
			{ options.map( ( option ) => (
				<option key={ option.value } value={ option.value }>
					{ option.label }
				</option>
			) ) }
		</select>
		{ help }
	</>
);

/**
 * An input that keeps the props of `TextControl`, enough to read and drive the options.
 *
 * @param {Object}   props                     The control props.
 * @param {string}   props.label               The label.
 * @param {string}   props.value               The value.
 * @param {Function} props.onChange            Called with the new value.
 * @param {boolean}  props.hideLabelFromVision Whether the label is for screen readers only.
 *
 * @return {Object} The input.
 */
const TextControl = ( { label, value, onChange, hideLabelFromVision, ...rest } ) => (
	<input aria-label={ label } value={ value } onChange={ ( event ) => onChange( event.target.value ) } { ...rest } />
);

module.exports = { SelectControl, TextControl };
