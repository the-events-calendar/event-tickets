import * as React from 'react';
import { Modal } from '@wordpress/components';
import { TicketUpsert } from '../TicketUpsert';
import { Ticket as TicketData } from '../../types/Ticket';

/**
 * TicketUpsertModal component for rendering a modal to create or update a ticket.
 *
 * @since TBD
 *
 * @param {TicketData} props
 * @return {JSX.Element} The rendered modal component.
 */
export default function TicketUpsertModal( props: {
	isUpdate: boolean;
	onCancel: () => void;
	onClose: () => void;
	onSave: ( ticketData : Partial<TicketData> ) => void;
	values: Partial<TicketData>;
} ): JSX.Element {

	const { isUpdate, onCancel, onClose, onSave, values } = props;

	return (
		<Modal
			__experimentalHideHeader={ true }
			className="classy-modal classy-modal--ticket"
			onRequestClose={ onClose }
			overlayClassName="classy-modal__overlay classy-modal__overlay--ticket"
		>
			<TicketUpsert
				isUpdate={ isUpdate }
				onCancel={ onCancel }
				onSave={ onSave }
				values={ values }
			/>
		</Modal>
	);
}
