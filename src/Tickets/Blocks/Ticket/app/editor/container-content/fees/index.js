/**
 * External dependencies
 */
import classNames from 'classnames';
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';
import {
	Checkbox,
	LabeledItem,
	Select
} from '@moderntribe/common/elements';
import { __ } from '@wordpress/i18n';
import {
	withSelect,
	withDispatch,
	useSelect,
	useDispatch,
} from '@wordpress/data';


const storeName = 'tec-tickets-fees';


const mapFeeToItem = ( { isDisabled, onChange, isChecked } ) => {
	/**
	 * @typedef {Object} Fee
	 * @property {string} id
	 * @property {string} display_name
	 * @property {float} raw_amount
	 * @property {string} status
	 * @property {string} sub_type
	 * @property {string} meta_value
	 */
	return ( fee ) => {
		// We shouldn't have these here, but just in case skip anything not active.
		if ( fee.status !== 'active' ) {
			return null;
		}

		// Todo: the precision should be determined by settings.
		const amount = Number.parseFloat( fee.raw_amount ).toFixed( 2 );

		let feeLabel;
		if ( fee.sub_type === 'percent' ) {
			feeLabel = `${ fee.display_name } (${ amount }%)`;
		} else {
			feeLabel = `${ fee.display_name } ($${ amount })`;
		}

		const classes = ['tribe-editor__ticket__fee-checkbox'];
		const name = `tec-ticket-fee-${ fee.id }`;

		return (
			<Checkbox
				key={ fee.id }
				checked={ isChecked }
				className={ classes.join( ' ' ) }
				disabled={ isDisabled }
				id={ name }
				name={ name }
				label={ feeLabel }
				value={ fee.id }
				onchange={ onChange }
			/>
		);
	}
};

function FeesSection( props ) {
	// If we shouldn't display this component, return null.
	const shouldDisplay = useSelect(
		( select ) => select( storeName ).shouldShowFees(),
		[]
	);
	if ( ! shouldDisplay ) {
		return null;
	}

	const feesAvailable = useSelect(
		( select ) => select( storeName ).getAvailableFees(),
		[]
	);
	const feesAutomatic = useSelect(
		( select ) => select( storeName ).getAutomaticFees(),
		[]
	);

	// If there are no fees to display, return null.
	const hasItemsToDisplay = feesAutomatic.length > 0 || feesAvailable.length > 0;
	if ( ! hasItemsToDisplay ) {
		return null;
	}

	const { addFeeToTicket, removeFeeFromTicket } = useDispatch( storeName );
	const {
		// feesSelected,
		// feesAvailable,
		// feesAutomatic,
		clientId,
	} = props;

	/**
	 * Handles the change event for the selected fees.
	 *
	 * @param event
	 */
	const onSelectedFeesChange = ( event ) => {
		const feeId = event.target.value;
		const isChecked = event.target.checked;

		if ( isChecked ) {
			addFeeToTicket( clientId, feeId );
		} else {
			removeFeeFromTicket( clientId, feeId );
		}
	}



	return (
		<div
			className={ classNames(
				'tribe-editor__ticket__fees',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--fees'
			) }
		>
			<LabeledItem
				className="tribe-editor__ticket__active-fees-label"
				label={ __( 'Ticket Fees', 'event-tickets' ) }
			/>

			<div className="tribe-editor__ticket__order_modifier_fees">
				{ feesAutomatic.length > 0 ? (
					feesAutomatic.map( mapFeeToItem( { isDisabled: true } ) )
				) : null }

				{ feesAvailable.length > 0 ? (
					feesAvailable.map( mapFeeToItem( { isDisabled: false, onChange: onSelectedFeesChange } ) )
				) : null }

				{ ! hasItemsToDisplay ? (
					<p>{ __( 'No available fees.', 'event-tickets' ) }</p>
				) : null }
			</div>
		</div>
	);
}

// export default FeesSectionComponent;
export default FeesSection;
