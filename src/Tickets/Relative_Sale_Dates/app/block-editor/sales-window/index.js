/**
 * External dependencies
 */
import { cloneElement, useEffect, useMemo, useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { clearTicketDurationError, hasTicketDurationError, subscribeToCommonStore } from '../common-store-bridge';
import { useEventDates } from '../event-dates';
import { MODE_RELATIVE, MODE_SPECIFIC } from '../../rule-constants';
import { resolveWindow } from '../../sale-window';
import { getFormRule, isSpecificWindow } from '../rule';
import { getHelperText } from '../sale-dates';
import { useWindowDraft } from '../use-window-draft';
import WindowBoundaries from '../window-boundaries';
import { getBoundaryErrors, useShownError, useWindowError } from '../window-error';
import { BLOCK_SALES_WINDOW } from '../window-kinds';

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
	const { rule, formRule, onChange, isTyping, isDisabled } = useWindowDraft( clientId, BLOCK_SALES_WINDOW );
	const eventDates = useEventDates();
	const saleWindow = useMemo(
		() => resolveWindow( getFormRule( rule, BLOCK_SALES_WINDOW ), eventDates, BLOCK_SALES_WINDOW ),
		[ rule, eventDates ]
	);
	const errors = getBoundaryErrors(
		useShownError( useWindowError( clientId, BLOCK_SALES_WINDOW ), isTyping ),
		formRule,
		BLOCK_SALES_WINDOW
	);
	const isSpecific = isSpecificWindow( formRule );
	const hasDurationError = useSyncExternalStore( subscribeToCommonStore, () => hasTicketDurationError( clientId ) );

	// The legacy code checks the picker's dates again on every picker edit, though they are hidden unless both are specific.
	useEffect( () => {
		if ( hasDurationError && ! isSpecific ) {
			clearTicketDurationError( clientId );
		}
	}, [ clientId, hasDurationError, isSpecific ] );

	return (
		<WindowBoundaries
			kind={ BLOCK_SALES_WINDOW }
			formRule={ formRule }
			onChange={ onChange }
			disabled={ isDisabled }
			getBoundaryProps={ ( name ) => ( {
				// Each end shows its own half of the range picker.
				picker: cloneElement( picker, {
					className: [ picker.props.className, `tec-tickets-relative-sale-dates__picker--${ name }` ]
						.filter( Boolean )
						.join( ' ' ),
					// Each half is bounded by the other's date, hidden and left stale once that end is not specific.
					...( MODE_SPECIFIC !== formRule[ 'start' === name ? 'end' : 'start' ].mode && {
						[ 'start' === name ? 'toDate' : 'fromDate' ]: undefined,
					} ),
				} ),
				// Mounted empty, so the date is announced once a relative mode gives the end one.
				helperText: MODE_RELATIVE === formRule[ name ].mode ? getHelperText( name, saleWindow?.[ name ] ) : '',
				...errors[ name ],
			} ) }
		/>
	);
}
