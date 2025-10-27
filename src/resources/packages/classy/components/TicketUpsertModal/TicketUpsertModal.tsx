import * as React from 'react';
import { ClassyModal, IconNew } from '@tec/common/classy/components';
import { _x } from '@wordpress/i18n';
import { TicketUpsert } from '../TicketUpsert';
import { TicketId, TicketSettings } from '../../types/Ticket';
import { TicketComponentProps } from '../../types/TicketComponentProps';

type TicketUpsertModalProps = {
	isUpdate: boolean;
	onCancel: () => void;
	onClose: () => void;
	onDelete: ( ticketId: TicketId ) => void;
	onSave: ( ticket: TicketSettings ) => void;
	value: TicketSettings;
} & TicketComponentProps;

const editTicketTitle = _x( 'Edit Ticket', 'Update ticket modal header title', 'event-tickets' );
const newTicketTitle = _x( 'New Ticket', 'Create ticket modal header title', 'event-tickets' );

/**
 * TicketUpsertModal component for rendering a modal to create or update a ticket.
 *
 * @since TBD
 *
 * @param {TicketData} props
 * @return {JSX.Element} The rendered modal component.
 */
export default function TicketUpsertModal( props: TicketUpsertModalProps ): JSX.Element {
	const { isUpdate, onCancel, onClose, onDelete, onSave, value } = props;

	const title = isUpdate ? editTicketTitle : newTicketTitle;

	return (
		<ClassyModal onClose={ onClose } type="ticket" title={ title } icon={ <IconNew /> }>
			<TicketUpsert
				isUpdate={ isUpdate }
				onCancel={ onCancel }
				onDelete={ onDelete }
				onSave={ onSave }
				value={ value }
			/>
		</ClassyModal>
	);
}
