import {Select} from '@moderntribe/common/elements';
import {Fragment, useState} from 'react';
import {ACTION_EVENT_LAYOUT_UPDATED, ajaxNonce, ajaxUrl} from '@tec/tickets/seating/ajax';
import {getLink, getLocalizedString} from '@tec/tickets/seating/utils';
import {Modal, Dashicon, CheckboxControl, Button, Spinner } from '@wordpress/components';
import {useSelect} from '@wordpress/data';
import './style.pcss';

const getString = (key) => getLocalizedString(key, 'settings');

const LayoutSelect = ({
	layouts,
	currentLayout
}) => {
	const getCurrentLayoutOption = (layoutId, layouts)=> {
		return layouts && layoutId
			? layouts.find((layoutOption) => layoutOption.value === layoutId)
			: null;
	}

	const postId = useSelect(
		(select) => select('core/editor').getCurrentPostId(),
		[]
	);

	const [activeLayout, setActiveLayout] = useState(getCurrentLayoutOption(currentLayout, layouts));
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [ newLayout, setNewLayout ] = useState(null);
	const [ isChecked, setChecked ] = useState(false);
	const [ isLoading, setIsLoading ] = useState(false);

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
		setIsLoading(false);
	}

	const handleModalConfirm = async () => {
		setActiveLayout(newLayout);
		setIsLoading(true);
		if ( await saveNewLayout() ) {
			setIsLoading(false);
			setIsModalOpen(false);
			window.location.reload();
		}
	}

	async function saveNewLayout() {
		const url = new URL(ajaxUrl);
		url.searchParams.set('_ajax_nonce', ajaxNonce);
		url.searchParams.set('newLayout', newLayout.value);
		url.searchParams.set('postId', postId);
		url.searchParams.set('action', ACTION_EVENT_LAYOUT_UPDATED);
		const response = await fetch(url.toString(), { method: 'POST' });

		return response.status === 200;
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
			<span className="tec-tickets-seating__settings_layout--title">Seat Layout</span>
			<NoLayouts />
			<RenderSelect />
			{ isModalOpen && (
				<Modal
					className="tec-tickets-seating__settings--layout-modal"
					title="Confirm Seat Layout Change"
					isDismissible={true}
					onRequestClose={closeModal}
					size="medium"
				>
					{ !isLoading && (
						<Fragment>
							<div className="tec-tickets-seating__settings-intro">
								<Dashicon icon="warning"/>
								<span className="icon-text">Caution</span>
								<p className="warning-text">All attendees will lose their seat assignments. All existing
									tickets will be assigned to a default seat type. This action cannot be undone.</p>
							</div>

							<CheckboxControl
								className="tec-tickets-seating__settings--checkbox"
								label="I Understand"
								checked={isChecked}
								onChange={setChecked}
								name="tec-tickets-seating__settings--switched-layout"
							/>

							<p>You may want to export attendee data first as a record of current seat assignments.</p>

							<div className="tec-tickets-seating__settings--actions">
								<Button
									onClick={handleModalConfirm}
									disabled={!isChecked}
									isPrimary={isChecked}
								>
									Change Seat Layout
								</Button>
								<Button
									onClick={closeModal}
									isSecondary={true}
								>
									Cancel
								</Button>
							</div>
						</Fragment>
					)}

					{isLoading && <Spinner/>}
				</Modal>
			)}
		</div>
	);
}

export default LayoutSelect;
