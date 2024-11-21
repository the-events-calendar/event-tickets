import React, { Fragment, useState } from 'react';
import { Modal, Dashicon, CheckboxControl, Button } from '@wordpress/components';
import {
	ACTION_REMOVE_EVENT_LAYOUT,
	ajaxNonce,
	ajaxUrl
} from '@tec/tickets/seating/ajax';

const RemoveLayout =  React.memo(({ postId }) => {
	const [isChecked, setChecked] = useState(false);
	const [removeModalOpen, setRemoveModalOpen] = useState(false);
	const [isLoading, setIsLoading] = useState(false);

	/**
	 * Close the modal.
	 *
	 * @since TBD
	 *
	 * @return {void}
	 */
	const closeModal = () => {
		setRemoveModalOpen(false);
		setChecked(false);
		setIsLoading(false);
	};


	/**
	 * Handle the removal of the layout.
	 *
	 * @since TBD
	 *
	 * @return {Promise<void>}
	 */
	const handleRemoveLayout = async () => {
		setIsLoading(true);
		if (await removeLayout()) {
			setIsLoading(false);
			setRemoveModalOpen(false);
			window.location.reload();
		}
	};

	/**
	 * Remove the layout.
	 *
	 * @since TBD
	 *
	 * @return {Promise<boolean>}
	 */
	async function removeLayout() {
		const url = new URL(ajaxUrl);
		url.searchParams.set('_ajax_nonce', ajaxNonce);
		url.searchParams.set('postId', postId);
		url.searchParams.set('action', ACTION_REMOVE_EVENT_LAYOUT);
		const response = await fetch(url.toString(), {method: 'POST'});

		return response.status === 200;
	}


	return (
		<Fragment>
			<a
				href="#"
				className="tec-tickets-seating__settings_layout--remove"
				onClick={() => setRemoveModalOpen(true)}
			>
				Remove Layout
			</a>

			{removeModalOpen && (
				<Modal
					className="tec-tickets-seating__settings--layout-modal"
					title="Confirm Seat Layout removal"
					isDismissible={true}
					onRequestClose={closeModal}
					size="medium"
				>
					<div className="tec-tickets-seating__settings-intro">
						<Dashicon icon="warning" />
						<span className="icon-text">Caution</span>
						<p className="warning-text">
							All attendees will lose their seat assignments. All seated tickets will switch to 1 capacity. This action cannot be undone.
						</p>
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
							onClick={handleRemoveLayout}
							disabled={!isChecked}
							isPrimary={isChecked}
						>
							Remove Seat Layout
						</Button>
						<Button
							onClick={closeModal}
							isSecondary={true}
						>
							Cancel
						</Button>
					</div>
				</Modal>
			)}
		</Fragment>
	);
});

export default RemoveLayout;