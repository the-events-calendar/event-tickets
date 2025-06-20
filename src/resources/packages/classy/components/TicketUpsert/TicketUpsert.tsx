import * as React from 'react';
import { useCallback, useState } from 'react';
import { _x } from '@wordpress/i18n';
import { __experimentalInputControl as InputControl, Button, CustomSelectControl } from '@wordpress/components';
import { CenteredSpinner, IconNew, LabeledInput } from '@tec/common/classy/components';
import { CustomSelectOption } from '@wordpress/components/build-types/custom-select-control/types';
import { useSelect } from '@wordpress/data';
import { SelectFunction } from '@wordpress/data/build-types/types';
import { decodeEntities } from '@wordpress/html-entities';
import { Ticket } from '../../types/Ticket';
import {
	TicketName,
	TicketDescription,
} from '../../fields';

type TicketUpsertProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onSave: ( data: Ticket ) => void;
	values: Ticket;
}

const defaultValues: Ticket = {
	name: '',
	description: '',
	price: '',
	hasSalePrice: false,
	salePrice: '',
	capacityType: 'general-admission',
	selectedFees: [],
	displayedFees: [],
};

export default function TicketUpsert( props: TicketUpsertProps ) {

	const {
		isUpdate,
		onCancel,
		onSave,
		values,
	} = props;

	const [ currentValues, setCurrentValues ] = useState<Ticket>( {
		...defaultValues,
		...values,
	} );

	// Tickets must have a name at a minimum.
	const [ confirmEnabled, setConfirmEnabled ] = useState<boolean>( currentValues.name !== '' );

	const invokeSaveWithData: () => void = useCallback( (): void => {
		if ( ! confirmEnabled ) {
			return;
		}

		const dataToSave: Ticket = {
			name: currentValues.name,
			description: currentValues.description,
			price: currentValues.price,
			hasSalePrice: currentValues.hasSalePrice,
			salePrice: currentValues.salePrice,
			capacityType: currentValues.capacityType,
			selectedFees: currentValues.selectedFees,
			displayedFees: currentValues.displayedFees,
		};

		onSave( dataToSave );
	}, [ currentValues ] );


	return (
		<div className="classy-root">
			<header className="classy-modal__header classy-modal__header--ticket">
				<IconNew />
				<h4 className="classy-modal__header-title">
					{ isUpdate
						? _x( 'Edit Ticket', 'Update ticket modal header title', 'event-tickets' )
						: _x( 'New Ticket', 'Create ticket modal header title', 'event-tickets' ) }
				</h4>
			</header>

			<TicketName
				value={ decodeEntities( currentValues.name ) }
				onChange={ ( value: string ) => {
					const newValue = value || '';
					setCurrentValues( { ...currentValues, name: newValue } );
					setConfirmEnabled( newValue !== '' );
				} }
			/>

			<TicketDescription
				value={ decodeEntities( currentValues.description ) }
				onChange={ ( value: string ) => {
					setCurrentValues( { ...currentValues, description: value || '' } );
				} }
			/>
		</div>
	);
}
