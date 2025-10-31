import { Button, CustomSelectControl } from '@wordpress/components';
import { CustomSelectOption } from '@wordpress/components/build-types/custom-select-control/types';
import { _x } from '@wordpress/i18n';
import * as React from 'react';
import { Fee } from '../../types/Fee';

type SelectFeeProps = {
	availableFees: Fee[];
	onCancel: () => void;
	onConfirm: ( feeId: number ) => void;
};

const placeholderOption: CustomSelectOption = {
	key: _x( 'Select a fee', 'Placeholder text for fee selection dropdown', 'event-tickets' ),
	name: '',
};

const mapFeeToOption = ( fee: Fee ): CustomSelectOption => {
	const { amount, label, id, subType } = fee;
	const optionLabel = subType === 'percentage' ? `${ label } (${ amount }%)` : `${ label } ($${ amount })`;

	return {
		key: optionLabel,
		name: id.toString(),
	};
};

export default function SelectFee( props: SelectFeeProps ): React.JSX.Element {
	const { availableFees, onCancel, onConfirm } = props;
	const feeOptions: CustomSelectOption[] = availableFees.map( mapFeeToOption );
	feeOptions.unshift( placeholderOption );

	const [ selectedFee, setSelectedFee ] = React.useState< CustomSelectOption >( placeholderOption );
	const [ addButtonDisabled, setAddButtonDisabled ] = React.useState< boolean >( true );

	return (
		<div className="classy-field__fee-select-container">
			<CustomSelectControl
				label="Select fee"
				options={ feeOptions }
				onChange={ ( option ) => {
					setSelectedFee( option.selectedItem );
					setAddButtonDisabled( option.selectedItem.name === '' );
				} }
			/>
			<Button
				variant="secondary"
				disabled={ addButtonDisabled }
				onClick={ () => {
					onConfirm( parseInt( selectedFee.name, 10 ) );
				} }
			>
				{ _x( 'Add Fee', 'Button label to add selected fee', 'event-tickets' ) }
			</Button>
			<Button variant="tertiary" onClick={ onCancel }>
				{ _x( 'Cancel', 'Button label to cancel fee selection', 'event-tickets' ) }
			</Button>
		</div>
	);
}
