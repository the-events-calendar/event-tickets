import { addFilter } from '@wordpress/hooks';
import CapacityForm from './capacity-form';

const shouldRenderAssignedSeatingForm = true;

function filterRenderCapacityForm(renderDefaultForm) {
	if (!shouldRenderAssignedSeatingForm) {
		return renderDefaultForm;
	}

	return () => <CapacityForm renderDefaultForm={renderDefaultForm} />;
}

addFilter(
	'tec.tickets.blocks.Ticket.Capacity.renderForm',
	'tec.tickets.seating',
	filterRenderCapacityForm
);
