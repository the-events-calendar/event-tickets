import {Select} from '@moderntribe/common/elements';
import React, {Fragment, useState} from 'react';
import {
	ACTION_EVENT_LAYOUT_UPDATED,
	ajaxNonce,
	ajaxUrl
} from '@tec/tickets/seating/ajax';
import {Modal, Dashicon, CheckboxControl, Button, Spinner} from '@wordpress/components';
import {useSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import {globals} from '@moderntribe/common/utils';
import './style.pcss';
import RemoveLayout from "./removeLayout";

/**
 * The layout select component.
 *
 * @since 5.16.0
 *
 * @param {Object} props The component props.
 */
const LayoutSelect = ({
	layouts,
	currentLayout
}) => {
	/**
	 * Gets the current layout option.
	 *
	 * @since 5.16.0
	 *
	 * @param {number} layoutId The layout ID.
	 * @param {Array}  layouts  The layouts.
	 *
	 * @return {Object|null}
	 */
	const getCurrentLayoutOption = (layoutId, layouts) => {
		return layouts && layoutId
			? layouts.find((layoutOption) => layoutOption.value === layoutId)
			: null;
	}

	/**
	 * The post ID.
	 *
	 * @since 5.16.0
	 *
	 * @type {number}
	 */
	const postId = useSelect(
		(select) => select('core/editor').getCurrentPostId(),
		[]
	);

	const [activeLayout, setActiveLayout] = useState(getCurrentLayoutOption(currentLayout, layouts));
	const [isModalOpen, setIsModalOpen] = useState(false);
	const [newLayout, setNewLayout] = useState(null);
	const [isChecked, setChecked] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const exportUrl = globals.adminUrl() + `edit.php?post_type=tribe_events&page=tickets-attendees&event_id=${postId}`;

	/**
	 * Handles the layout change.
	 *
	 * @since 5.16.0
	 *
	 * @param {Object} selectedLayout The selected layout.
	 */
	const handleLayoutChange = (selectedLayout) => {
		if (selectedLayout === activeLayout) {
			return;
		}

		setIsModalOpen(true);
		setNewLayout(selectedLayout);
	};

	/**
	 * Close the modal.
	 *
	 * @since 5.16.0
	 */
	const closeModal = () => {
		setIsModalOpen(false);
		setChecked(false);
		setIsLoading(false);
	}

	/**
	 * Handle Modal confirmation.
	 *
	 * @since 5.16.0
	 */
	const handleModalConfirm = async () => {
		setActiveLayout(newLayout);
		setIsLoading(true);
		if (await saveNewLayout()) {
			setIsLoading(false);
			setIsModalOpen(false);
			window.location.reload();
		}
	}

	/**
	 * Save the new layout with changes.
	 *
	 * @since 5.16.0
	 *
	 * @return {Promise<boolean>}
	 */
	async function saveNewLayout() {
		const url = new URL(ajaxUrl);
		url.searchParams.set('_ajax_nonce', ajaxNonce);
		url.searchParams.set('newLayout', newLayout.value);
		url.searchParams.set('postId', postId);
		url.searchParams.set('action', ACTION_EVENT_LAYOUT_UPDATED);
		const response = await fetch(url.toString(), {method: 'POST'});

		return response.status === 200;
	}

	/**
	 * Renders the no layouts message.
	 *
	 * @since 5.16.0
	 */
	function NoLayouts() {
		if (currentLayout === null || currentLayout.length === 0 || layouts.length === 0) {
			return (
				<span className="tec-tickets-seating__settings_layout--description">
					{__('The event is not using assigned seating.', 'event-tickets')}
				</span>
			);
		}
	}

	/**
	 * Renders the select dropdown for the layout.
	 *
	 * @since 5.16.0
	 *
	 * @return {JSX.Element|null}
	 */
	function RenderSelect() {
		if (currentLayout === null || currentLayout.length === 0 || layouts.length === 0) {
			return null;
		}

		return (
			<Fragment>
				<div className="tec-tickets-seating__settings_layout--select-container">
					<Select
						id="tec-tickets-seating__settings_layout-select"
						className="tec-tickets-seating__settings_layout--select"
						value={activeLayout}
						options={layouts}
						onChange={handleLayoutChange}
					/>
					<RemoveLayout postId={postId}/>
				</div>
				<span className="tec-tickets-seating__settings_layout--description">
					{__(
						'Changing the event’s layout will impact all existing tickets. Attendees will lose their seat assignments.',
						'event-tickets'
					)}
				</span>
			</Fragment>
		);
	}

	const MemoizedRenderSelect = React.memo(RenderSelect);
	return (
		<div className="tec-tickets-seating__settings_layout--wrapper">
			<span className="tec-tickets-seating__settings_layout--title">{__('Seat Layout', 'event-tickets')}</span>
			<NoLayouts/>
			<MemoizedRenderSelect/>
			{isModalOpen && (
				<Modal
					className="tec-tickets-seating__settings--layout-modal"
					title="Confirm Seat Layout Change"
					isDismissible={true}
					onRequestClose={closeModal}
					size="medium"
				>
					{!isLoading && (
						<Fragment>
							<div className="tec-tickets-seating__settings-intro">
								<Dashicon icon="warning"/>
								<span className="icon-text">{__('Caution', 'event-tickets')}</span>
								<p className="warning-text">
									{__('All attendees will lose their seat assignments. All existing tickets will be assigned to a default seat type.', 'event-tickets')}
									{' '}
									<span
										style={{textDecoration: 'underline'}}>{__('This action cannot be undone.', 'event-tickets')}</span>
								</p>
							</div>

							<CheckboxControl
								className="tec-tickets-seating__settings--checkbox"
								label="I Understand"
								checked={isChecked}
								onChange={setChecked}
								name="tec-tickets-seating__settings--switched-layout"
							/>

							<p>
								{__('You may want to', 'event-tickets')}{' '}
								<a href={exportUrl} target="_blank" rel="noopener noreferrer">
									{__('export attendee', 'event-tickets')}
								</a>{' '}
								{__('data first as a record of current seat assignments.', 'event-tickets')}
							</p>

							<div className="tec-tickets-seating__settings--actions">
								<Button
									onClick={handleModalConfirm}
									disabled={!isChecked}
									isPrimary={isChecked}
								>
									{__('Change Seat Layout', 'event-tickets')}
								</Button>
								<Button
									onClick={closeModal}
									isSecondary={true}
								>
									{__('Cancel', 'event-tickets')}
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
