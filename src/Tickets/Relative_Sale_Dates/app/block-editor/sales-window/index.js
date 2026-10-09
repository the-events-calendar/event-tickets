/**
 * External dependencies
 */
import { cloneElement, useEffect, useMemo, useSyncExternalStore } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { clearTicketDurationError, hasTicketDurationError, subscribeToCommonStore } from '../common-store-bridge';
import { useEventDates } from '../event-dates';
import { MAX_VALUE, MIN_VALUE, MODE_RELATIVE } from '../../rule-constants';
import { getFormRule, isSpecificWindow } from '../rule';
import { getHelperText, resolveTicketWindow } from '../sale-dates';
import { useWindowDraft } from '../use-window-draft';
import WindowBoundary from '../window-boundary';
import { useTicketWindowError } from '../window-error';
import { BLOCK_SALES_WINDOW } from '../window-kinds';
import { RELATIVE_VALUE_OUT_OF_RANGE, getOutOfRangeBoundary } from '../../window-check';
import './style.pcss';

/**
 * Renders the sales window options of a ticket block in place of its date and time range picker.
 *
 * @since TBD
 *
 * @param {Object} props          The component props.
 * @param {string} props.clientId The client ID of the ticket block.
 * @param {Object} props.picker   The date and time range picker element the Sale Duration section would render.
 *
 * @return {Object} The sales window options.
 */
export default function SalesWindow( { clientId, picker } ) {
	const { rule, formRule, onChange } = useWindowDraft( clientId, BLOCK_SALES_WINDOW );
	const eventDates = useEventDates();
	const saleWindow = useMemo(
		() => resolveTicketWindow( getFormRule( rule, BLOCK_SALES_WINDOW ), eventDates ),
		[ rule, eventDates ]
	);
	const error = useTicketWindowError( clientId, rule, eventDates );
	const outOfRange = RELATIVE_VALUE_OUT_OF_RANGE === error ? getOutOfRangeBoundary( formRule ) : null;
	const isSpecific = isSpecificWindow( formRule );
	const hasDurationError = useSyncExternalStore( subscribeToCommonStore, () => hasTicketDurationError( clientId ) );

	// The legacy code checks the picker's dates again on every picker edit, though they are hidden unless both are specific.
	useEffect( () => {
		if ( hasDurationError && ! isSpecific ) {
			clearTicketDurationError( clientId );
		}
	}, [ clientId, hasDurationError, isSpecific ] );

	const settings = BLOCK_SALES_WINDOW.getBoundarySettings();

	return (
		<div className="tec-tickets-relative-sale-dates">
			{ [ 'start', 'end' ].map( ( name ) => (
				<WindowBoundary
					key={ name }
					kind={ BLOCK_SALES_WINDOW }
					name={ name }
					boundary={ formRule[ name ] }
					// Each end shows its own half of the range picker.
					picker={ cloneElement( picker, {
						className: [ picker.props.className, `tec-tickets-relative-sale-dates__picker--${ name }` ]
							.filter( Boolean )
							.join( ' ' ),
					} ) }
					helperText={
						MODE_RELATIVE === formRule[ name ].mode ? getHelperText( name, saleWindow?.[ name ] ) : ''
					}
					errorMessage={
						'end' === name && error && ! outOfRange
							? __(
									'Ticket sales cannot end before they start. Please adjust the sales window.',
									'event-tickets'
							  )
							: ''
					}
					valueErrorMessage={
						name === outOfRange
							? sprintf(
									// translators: %1$d is the smallest number a relative sale date takes, %2$d the largest.
									__( 'Enter a number from %1$d to %2$d.', 'event-tickets' ),
									MIN_VALUE,
									MAX_VALUE
							  )
							: ''
					}
					onChange={ ( changes ) => onChange( name, changes ) }
					{ ...settings[ name ] }
				/>
			) ) }
		</div>
	);
}
