/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { LabeledItem, Select } from '@moderntribe/common/elements';

const FeesSelector = ({
						  selectedFees,
						  activeFees,
						  onSelectedFeesChange,
					  }) => (
	<Fragment>
		{/* Active Fees Section */}
		<div className="tribe-editor__ticket__active-fees">
			<LabeledItem
				className="tribe-editor__ticket__active-fees-label"
				label="Active Fees"
			/>
			<ul>
				{activeFees.length > 0 ? (
					activeFees.map((fee) => (
						<li key={fee.value}>
							{fee.label}
						</li>
					))
				) : (
					<li>No active fees.</li>
				)}
			</ul>
		</div>

		{/* Selected Fees Section */}
		<div className="tribe-editor__ticket__order_modifier_fees">
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
				noOptionsMessage={() => 'No selectable fees available'}
			/>
		</div>
	</Fragment>
);

FeesSelector.propTypes = {
	selectedFees: PropTypes.array.isRequired,
	activeFees: PropTypes.array.isRequired,
	onSelectedFeesChange: PropTypes.func.isRequired,
};

export default FeesSelector;
