import * as React from 'react';
import { Modal } from '@wordpress/components';
import { TicketUpsert } from '../TicketUpsert';
import { PartialTicket } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';

type TicketUpsertModalProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onClose: () => void;
	onSave: ( ticket: PartialTicket ) => void;
	value: PartialTicket;
} & TicketComponentProps;

/**
 * TicketUpsertModal component for rendering a modal to create or update a ticket.
 *
 * @since TBD
 *
 * @param {TicketData} props
 * @return {JSX.Element} The rendered modal component.
 */
export default function TicketUpsertModal( props: TicketUpsertModalProps ): JSX.Element {
	const {
		isUpdate,
		onCancel,
		onClose,
		onSave,
		value
	} = props;

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
				value={ value }
			/>
		</Modal>
	);
}
