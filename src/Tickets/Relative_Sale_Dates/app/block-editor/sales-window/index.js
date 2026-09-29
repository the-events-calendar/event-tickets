/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useMemo } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { markTicketChanged } from '../common-store-bridge';
import { useEventDates } from '../event-dates';
import { ANCHOR_END, ANCHOR_START, MODE_DEFAULT, MODE_RELATIVE, MODE_SPECIFIC } from '../../rule-constants';
import { getFormRule } from '../rule';
import { getHelperText, resolveTicketWindow } from '../sale-dates';
import { STORE_NAME } from '../store/constants';
import { useTicketWindowError } from '../window-error';
import SalesWindowEnd from './sales-window-end';
import './style.pcss';

/**
 * Builds the labels and options of each end of the window.
 *
 * Where a msgid is the classic editor's (Now, When the event starts, the anchors and the relative labels), its context
 * is too, so one translation serves both.
 *
 * @since TBD
 *
 * @return {Object} The labels, mode options and anchor options, keyed by end.
 */
function getEndSettings() {
	const anchorOptions = [
		{
			value: ANCHOR_START,
			label: _x(
				'before the event starts',
				'What a relative ticket sale date is measured from.',
				'event-tickets'
			),
		},
		{
			value: ANCHOR_END,
			label: _x( 'before the event ends', 'What a relative ticket sale date is measured from.', 'event-tickets' ),
		},
	];

	return {
		start: {
			labels: {
				mode: _x( 'From', 'When ticket sales start, in the Ticket block.', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sales start.
				value: __( 'Number of units before the event that sales start', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sales start.
				unit: __( 'Unit of the sales start', 'event-tickets' ),
				// translators: The screen reader label of what a relative ticket sales start is measured from.
				anchor: __( 'What the sales start is measured from', 'event-tickets' ),
			},
			modeOptions: [
				{ value: MODE_DEFAULT, label: _x( 'Now', 'When ticket sales start.', 'event-tickets' ) },
				{ value: MODE_RELATIVE, label: _x( 'A relative date', 'When ticket sales start.', 'event-tickets' ) },
				{ value: MODE_SPECIFIC, label: _x( 'A specific date', 'When ticket sales start.', 'event-tickets' ) },
			],
			anchorOptions,
		},
		end: {
			labels: {
				mode: _x( 'To', 'When ticket sales end, in the Ticket block.', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sales end.
				value: __( 'Number of units before the event that sales end', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sales end.
				unit: __( 'Unit of the sales end', 'event-tickets' ),
				// translators: The screen reader label of what a relative ticket sales end is measured from.
				anchor: __( 'What the sales end is measured from', 'event-tickets' ),
			},
			modeOptions: [
				{
					value: MODE_DEFAULT,
					label: _x( 'When the event starts', 'When ticket sales end.', 'event-tickets' ),
				},
				{ value: MODE_RELATIVE, label: _x( 'A relative date', 'When ticket sales end.', 'event-tickets' ) },
				{ value: MODE_SPECIFIC, label: _x( 'A specific date', 'When ticket sales end.', 'event-tickets' ) },
			],
			anchorOptions,
		},
	};
}

/**
 * Renders the sales window options of a ticket block in place of its date and time range picker.
 *
 * A new ticket's defaults are kept as its draft at once, so the ticket is saved with them as the classic editor saves
 * its form; a ticket saved without a rule keeps no draft until the admin changes an option.
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
	const rule = useSelect( ( select ) => select( STORE_NAME ).getDraftRule( clientId ), [ clientId ] );
	const { setDraftRule } = useDispatch( STORE_NAME );
	const formRule = getFormRule( rule );
	const eventDates = useEventDates();
	const saleWindow = useMemo( () => resolveTicketWindow( getFormRule( rule ), eventDates ), [ rule, eventDates ] );
	const error = useTicketWindowError( clientId, rule, eventDates );

	useEffect( () => {
		if ( undefined === rule ) {
			setDraftRule( clientId, getFormRule( rule ) );
		}
	}, [ clientId, rule, setDraftRule ] );

	const onChange = ( name, changes ) => {
		setDraftRule( clientId, { ...formRule, [ name ]: { ...formRule[ name ], ...changes } } );
		// The legacy dashboard re-checks its Create or Update button, which reads this draft, only on a legacy store change.
		markTicketChanged( clientId );
	};

	const settings = getEndSettings();

	return (
		<div className="tec-tickets-relative-sale-dates">
			{ [ 'start', 'end' ].map( ( name ) => (
				<SalesWindowEnd
					key={ name }
					name={ name }
					end={ formRule[ name ] }
					picker={ picker }
					helperText={
						MODE_RELATIVE === formRule[ name ].mode ? getHelperText( name, saleWindow?.[ name ] ) : ''
					}
					errorMessage={
						'end' === name && error
							? __(
									'Ticket sales cannot end before they start. Please adjust the sales window.',
									'event-tickets'
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
