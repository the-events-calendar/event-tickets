/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import { __, _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { markTicketChanged } from '../common-store-bridge';
import { useEventDates } from '../event-dates';
import { MODE_NOW, MODE_RELATIVE, MODE_SPECIFIC } from '../../rule-constants';
import { useSalePriceLengthText } from '../sale-price-length-text';
import { getFormSalePriceRule } from '../sale-price-rule';
import { STORE_NAME } from '../store/constants';
import SalePriceWindowBoundary from './sale-price-window-boundary';

/**
 * Builds the labels and options of each boundary of the sale price window.
 *
 * The msgids and contexts are the classic editor's, so one translation serves both.
 *
 * @since TBD
 *
 * @return {Object} The labels and mode options, keyed by boundary.
 */
function getBoundarySettings() {
	return {
		start: {
			labels: {
				mode: __( 'Sale Starts:', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sale price start.
				value: __( 'Number of units before the event that the sale price starts', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sale price start.
				unit: __( 'Unit of the sale price start', 'event-tickets' ),
				// translators: The screen reader label of the date picker of a specific ticket sale price start.
				date: __( 'Sale price start date', 'event-tickets' ),
			},
			modeOptions: [
				{ value: MODE_NOW, label: _x( 'Now', 'When the sale price starts.', 'event-tickets' ) },
				{
					value: MODE_RELATIVE,
					label: _x( 'On a relative date', 'When the sale price starts.', 'event-tickets' ),
				},
				{
					value: MODE_SPECIFIC,
					label: _x( 'On a specific date', 'When the sale price starts.', 'event-tickets' ),
				},
			],
		},
		end: {
			labels: {
				mode: __( 'Sale Ends:', 'event-tickets' ),
				// translators: The screen reader label of the number of a relative ticket sale price end.
				value: __( 'Number of units before the event that the sale price ends', 'event-tickets' ),
				// translators: The screen reader label of the unit of a relative ticket sale price end.
				unit: __( 'Unit of the sale price end', 'event-tickets' ),
				// translators: The screen reader label of the date picker of a specific ticket sale price end.
				date: __( 'Sale price end date', 'event-tickets' ),
			},
			modeOptions: [
				{
					value: MODE_RELATIVE,
					label: _x( 'On a relative date', 'When the sale price ends.', 'event-tickets' ),
				},
				{
					value: MODE_SPECIFIC,
					label: _x( 'On a specific date', 'When the sale price ends.', 'event-tickets' ),
				},
			],
		},
	};
}

/**
 * Renders the sale price window options of a ticket block in place of its sale dates row.
 *
 * A new sale price's defaults are kept as its draft at once, so the ticket is saved with them as the classic editor
 * saves its form; a sale price saved without a rule keeps no draft until the admin changes an option. How long the
 * sale price lasts shows under *Sale Ends*.
 *
 * @since TBD
 *
 * @param {Object}                       props          The component props.
 * @param {string}                       props.clientId The client ID of the ticket block.
 * @param {{start: Object, end: Object}} props.pickers  The sale price start and end date pickers.
 *
 * @return {Object} The sale price window options.
 */
export default function SalePriceWindow( { clientId, pickers } ) {
	const rule = useSelect( ( select ) => select( STORE_NAME ).getDraftSalePriceRule( clientId ), [ clientId ] );
	const { setDraftSalePriceRule } = useDispatch( STORE_NAME );
	const formRule = getFormSalePriceRule( rule );
	const eventDates = useEventDates();
	const lengthText = useSalePriceLengthText( clientId, formRule, eventDates );

	useEffect( () => {
		if ( undefined === rule ) {
			setDraftSalePriceRule( clientId, getFormSalePriceRule( rule ) );
		}
	}, [ clientId, rule, setDraftSalePriceRule ] );

	const onChange = ( name, changes ) => {
		setDraftSalePriceRule( clientId, { ...formRule, [ name ]: { ...formRule[ name ], ...changes } } );
		// The legacy dashboard re-checks its Create or Update button, which reads this draft, only on a legacy store change.
		markTicketChanged( clientId );
	};

	const settings = getBoundarySettings();

	return (
		<div className="tec-tickets-relative-sale-dates">
			{ [ 'start', 'end' ].map( ( name ) => (
				<SalePriceWindowBoundary
					key={ name }
					name={ name }
					boundary={ formRule[ name ] }
					picker={ pickers[ name ] }
					helperText={ 'end' === name ? lengthText : undefined }
					onChange={ ( changes ) => onChange( name, changes ) }
					{ ...settings[ name ] }
				/>
			) ) }
		</div>
	);
}
