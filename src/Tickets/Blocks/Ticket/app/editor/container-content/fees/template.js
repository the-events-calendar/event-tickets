/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { LabeledItem, Select } from '@moderntribe/common/elements';

const FeesSelector = ( {
						   selectedFees,
						   activeFees,
						   onSelectedFeesChange,
					   } ) => (
	<Fragment>
		<div className="tribe-editor__ticket__order_modifier_fees">
			{/* Selected Fees Section */}
			<LabeledItem
				className="tribe-editor__ticket__selected-fees-label"
				label="Selected Fees"
			/>
			<Select
				id="tec-ticket-selected-fees-select"
				placeholder="Select Fees"
				options={activeFees}
				value={selectedFees}
				onChange={onSelectedFeesChange}
				isMulti={true}
				noOptionsMessage={() => 'No fees available'}
			/>
		</div>

		{/* Active Fees Section */}
		<div className="tribe-editor__ticket__active-fees">
			<LabeledItem
				className="tribe-editor__ticket__active-fees-label"
				label="Active Fees"
			/>
			<ul>
				{activeFees.map( ( fee ) => (
					<li key={fee.value}>
						{fee.label}
					</li>
				) )}
			</ul>
		</div>
	</Fragment>
);

FeesSelector.propTypes = {
	selectedFees: PropTypes.array.isRequired,
	activeFees: PropTypes.array.isRequired,
	onSelectedFeesChange: PropTypes.func.isRequired,
};

export default FeesSelector;
