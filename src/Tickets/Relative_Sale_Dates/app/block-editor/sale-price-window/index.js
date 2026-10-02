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
import { SALE_PRICE_ENDS_BEFORE_START, SALE_PRICE_OUTSIDE_SALES_WINDOW } from '../../sale-price-check';
import { useTicketSalePriceError } from '../sale-price-error';
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
 * Gets the message of a sale price window error.
 *
 * The msgids are the ones the server rejects a save with, so one translation serves both.
 *
 * @since TBD
 *
 * @param {string|null} error The message key of the error, or `null`.
 *
 * @return {string} The message, or an empty string without an error.
 */
function getErrorMessage( error ) {
	if ( SALE_PRICE_ENDS_BEFORE_START === error ) {
		return __(
			'The sale price cannot end before it starts. Please adjust the sale price window.',
			'event-tickets'
		);
	}

	if ( SALE_PRICE_OUTSIDE_SALES_WINDOW === error ) {
		return __(
			'The sale price window falls outside the ticket sales window. Please adjust the dates.',
			'event-tickets'
		);
	}

	return '';
}

/**
 * Renders the sale price window options of a ticket block in place of its sale dates row.
 *
 * A new sale price's defaults are kept as its draft at once, so the ticket is saved with them as the classic editor
 * saves its form, and Create or Update checks them; a sale price saved without a rule keeps no draft until the admin
 * changes an option. How long the
 * sale price lasts, or why the window cannot be saved, shows under *Sale Ends*.
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
	const salesWindowRule = useSelect( ( select ) => select( STORE_NAME ).getDraftRule( clientId ), [ clientId ] );
	const { setDraftSalePriceRule } = useDispatch( STORE_NAME );
	const formRule = getFormSalePriceRule( rule );
	const eventDates = useEventDates();
	const lengthText = useSalePriceLengthText( clientId, formRule, eventDates );
	const errorMessage = getErrorMessage( useTicketSalePriceError( clientId, rule, salesWindowRule, eventDates ) );

	useEffect( () => {
		if ( undefined === rule ) {
			setDraftSalePriceRule( clientId, getFormSalePriceRule( rule ) );
			/*
			 * Create or Update was checked when the sale price was checked, before these defaults existed, and is checked
			 * again only on a legacy store change; the checkbox has marked the ticket as changed already.
			 */
			markTicketChanged( clientId );
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
					errorMessage={ 'end' === name ? errorMessage : '' }
					onChange={ ( changes ) => onChange( name, changes ) }
					{ ...settings[ name ] }
				/>
			) ) }
		</div>
	);
}
