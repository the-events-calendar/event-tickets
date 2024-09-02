import {Select} from '@moderntribe/common/elements';
import {useState} from 'react';
import {getLink, getLocalizedString} from '@tec/tickets/seating/utils';
import {Modal, Dashicon, CheckboxControl, Button} from '@wordpress/components';
import './style.pcss';

const getString = (key) => getLocalizedString(key, 'capacity-form');

const LayoutSelect = ({
	layouts,
	currentLayout
}) => {
	const getCurrentLayoutOption = (layoutId, layouts)=> {
		return layouts && layoutId
			? layouts.find((layoutOption) => layoutOption.value === layoutId)
			: null;
	}

	const [activeLayout, setActiveLayout] = useState(getCurrentLayoutOption(currentLayout, layouts));
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [ newLayout, setNewLayout ] = useState(null);
	const [ isChecked, setChecked ] = useState(false);

	const handleLayoutChange = (selectedLayout) => {
		if ( selectedLayout === activeLayout ) {
			return;
		}

		setIsModalOpen(true);
		setNewLayout(selectedLayout);
	};

	const closeModal = () => {
		setIsModalOpen(false);
		setChecked(false);
	}

	const handleModalConfirm = () => {
		setActiveLayout(newLayout);
		setIsModalOpen(false);
	}

	function NoLayouts() {
		if ( currentLayout === null || currentLayout.length === 0 || layouts.length === 0 ) {
			return (
				<span className="tec-tickets-seating__settings_layout--description">
					The event is not using assigned seating.
				</span>
			);
		}
	}

	/**
	 * Renders the select dropdown for the layout.
	 *
	 * @since TBD
	 *
	 * @return {JSX.Element|null}
	 */
	function RenderSelect () {
		if (currentLayout === null || currentLayout.length === 0 || layouts.length === 0 ) {
			return null;
		}

		return (
			<Fragment>
				<Select
					id="tec-tickets-seating__settings_layout-select"
					className="tec-tickets-seating__settings_layout--select"
					value={activeLayout}
					options={layouts}
					onChange={handleLayoutChange}
				/>
				<span className="tec-tickets-seating__settings_layout--description">
					Changing the event’s layout will impact all existing tickets.
					Attendees will lose their seat assignments.
				</span>
			</Fragment>
		);
	}
	return (
		<div className="tec-tickets-seating__settings_layout--wrapper">
			<p className="tec-tickets-seating__settings_layout--title">Seat Layout</p>

			{ isModalOpen &&
				<Modal
					className="tec-tickets-seating__settings--layout-modal"
					title="Confirm Seat Layout Change"
					isDismissible={true}
					onRequestClose={closeModal}
					size="medium"
				>
					<div className="tec-tickets-seating__settings-intro">

						<Dashicon icon="warning" size={20}/>
						Caution
						<p>Changing the event's layout will impact all existing tickets and attendees.</p>
					</div>

					<CheckboxControl
						className="tec-tickets-seating__settings--checkbox"
						__nextHasNoMarginBottom
						label="I Understand"
						checked={ isChecked }
						onChange={ setChecked }
					/>

					<p>You may want to export attendee data first as a record of current seat assignments.</p>

					<div className="tec-tickets-seating__settings--actions">
						<Button onClick={handleModalConfirm} disabled={!isChecked} isPrimary={isChecked}>Change Seat Layout</Button>
						<Button onClick={closeModal} isSecondary={true}>Cancel</Button>
					</div>
				</Modal>
			}
			<NoLayouts />
		</div>
	);
}

export default LayoutSelect;
