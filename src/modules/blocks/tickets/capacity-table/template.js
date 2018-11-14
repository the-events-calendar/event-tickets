/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import noop from 'lodash/noop';

/**
 * Internal dependencies
 */
import { sendValue } from '@moderntribe/common/utils/input';
import itemsSchema, { getValues } from './schema';
import { toLabel } from './utils';
import Row from './row/template';
import './style.pcss';

const CapacityTable = ( props ) => {
	const {
		sharedTickets,
		independentTickets,
		totalCapacity,
		onSharedCapacityChange,
		sharedCapacity,
	} = props;

	const sharedData = getValues( sharedTickets );
	const independentData = getValues( independentTickets );

	const sharedInput = (
		<input
			onChange={ sendValue( onSharedCapacityChange ) }
			value={ sharedCapacity }
			type="number"
			min="0"
		/>
	);

	return (
		<div className="tribe-editor__capacity">
			<h3 className="tribe-editor__capacity__title">
				{ __( 'Capacity', 'events-gutenberg' ) }
			</h3>
			<Row
				label={ __( 'Shared Capacity', 'events-gutenberg' ) }
				items={ toLabel( sharedData.names ) }
				right={ sharedInput }
			/>
			<Row
				label={ __( 'Independent capacity', 'events-gutenberg' ) }
				items={ toLabel( independentData.names ) }
				right={ independentData.total }
			/>
			<Row
				label={ __( 'Total Capacity', 'events-gutenberg' ) }
				right={ totalCapacity }
			/>
		</div>
	);
};

CapacityTable.propTypes = {
	sharedTickets: itemsSchema,
	independentTickets: itemsSchema,
	totalCapacity: PropTypes.number,
	sharedCapacity: PropTypes.oneOfType( [ PropTypes.number, PropTypes.string ] ),
	onSharedCapacityChange: PropTypes.func,
};

CapacityTable.defaultProps = {
	sharedTickets: [],
	independentTickets: [],
	totalCapacity: 0,
	onSharedCapacityChange: noop,
};

export default CapacityTable;
