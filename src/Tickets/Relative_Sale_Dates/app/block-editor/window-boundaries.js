/**
 * Internal dependencies
 */
import WindowBoundary from './window-boundary';

/** @typedef {import( '../sale-window' ).SaleWindowRule} SaleWindowRule */
/** @typedef {import( './window-kinds' ).BlockWindowKind} BlockWindowKind */
/** @typedef {import( './window-kinds' ).BoundarySettings} BoundarySettings */

/**
 * Renders the options of both boundaries of a ticket block's window, the start first.
 *
 * @since TBD
 *
 * @param {Object}                                       props                  The component props.
 * @param {BlockWindowKind}                              props.kind             The window kind.
 * @param {SaleWindowRule}                               props.formRule         The rule the options show.
 * @param {function( string, Object ): void}             props.onChange         Called with the boundary, `start` or
 *                                                                              `end`, and its changed values.
 * @param {function( string, BoundarySettings ): Object} props.getBoundaryProps Builds what the window adds to a
 *                                                                              boundary's options: its picker, helper
 *                                                                              text and error messages.
 *
 * @return {Object} The options of both boundaries.
 */
export default function WindowBoundaries( { kind, formRule, onChange, getBoundaryProps } ) {
	const settings = kind.getBoundarySettings();

	return (
		<div className="tec-tickets-relative-sale-dates">
			{ [ 'start', 'end' ].map( ( name ) => (
				<WindowBoundary
					key={ name }
					kind={ kind }
					name={ name }
					boundary={ formRule[ name ] }
					{ ...getBoundaryProps( name, settings[ name ] ) }
					onChange={ ( changes ) => onChange( name, changes ) }
					{ ...settings[ name ] }
				/>
			) ) }
		</div>
	);
}
