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


const mapFeeToItem = ( isDisabled ) => {
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

		return (
			<FeeItem
				key={ fee.id }
				isDisabled={ isDisabled }
				isSelected={ true }
				label={ feeLabel }
				value={ fee.id }
				classes={ [ 'tribe-editor__ticket__fee-checkbox' ] }
			/>
		);
	}
};

class FeeItem extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool.isRequired,
		isSelected: PropTypes.bool.isRequired,
		label: PropTypes.string.isRequired,
		value: PropTypes.string.isRequired,
		classes: PropTypes.array.isRequired,
	};

	constructor( props ) {
		super( props );
	}

	render() {
		const {
			isDisabled,
			isSelected,
			label,
			value,
			classes,
		} = this.props;

		const name = `tec-ticket-fee-${ value }`;

		return (
			<Checkbox
				checked={ isSelected }
				className={ classes.join( ' ' ) }
				disabled={ isDisabled }
				id={ name }
				name={ name }
				label={ label }
				value={ value }
			/>
		);
	}
}

class FeesSection extends PureComponent {
	static propTypes = {
		feesSelected: PropTypes.array.isRequired,
		feesAvailable: PropTypes.array.isRequired,
		feesAutomatic: PropTypes.array.isRequired,
		onSelectedFeesChange: PropTypes.func.isRequired,
		shouldDisplay: PropTypes.bool.isRequired,
	};

	constructor( props ) {
		super( props );
	}

	render() {
		const {
			feesSelected,
			feesAvailable,
			feesAutomatic,
			onSelectedFeesChange,
			shouldDisplay,
		} = this.props;

		// If we shouldn't display this component, return null.
		if ( ! shouldDisplay ) {
			return null;
		}

		const hasItemsToDisplay = feesAutomatic.length > 0 || feesAvailable.length > 0;

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
						feesAvailable.map( mapFeeToItem( { isDisabled: false } ) )
					) : null }

					{ ! hasItemsToDisplay ? (
						<p>{ __( 'No available fees.', 'event-tickets' ) }</p>
					) : null }
				</div>
			</div>
		);
	}
}

export default FeesSection;
