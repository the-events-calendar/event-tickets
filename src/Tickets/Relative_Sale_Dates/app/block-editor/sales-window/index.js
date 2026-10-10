/**
 * External dependencies
 */
import { cloneElement, useEffect, useMemo, useSyncExternalStore } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { clearTicketDurationError, hasTicketDurationError, subscribeToCommonStore } from '../common-store-bridge';
import { useEventDates } from '../event-dates';
import { MODE_RELATIVE } from '../../rule-constants';
import { getFormRule, isSpecificWindow } from '../rule';
import { getHelperText, resolveTicketWindow } from '../sale-dates';
import { useWindowDraft } from '../use-window-draft';
import WindowBoundaries from '../window-boundaries';
import { getBoundaryErrors, useWindowError } from '../window-error';
import { BLOCK_SALES_WINDOW } from '../window-kinds';
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
	const errors = getBoundaryErrors( useWindowError( clientId, BLOCK_SALES_WINDOW ), formRule, BLOCK_SALES_WINDOW );
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
			getBoundaryProps={ ( name ) => ( {
				// Each end shows its own half of the range picker.
				picker: cloneElement( picker, {
					className: [ picker.props.className, `tec-tickets-relative-sale-dates__picker--${ name }` ]
						.filter( Boolean )
						.join( ' ' ),
				} ),
				helperText:
					MODE_RELATIVE === formRule[ name ].mode ? getHelperText( name, saleWindow?.[ name ] ) : undefined,
				...errors[ name ],
			} ) }
		/>
	);
}
