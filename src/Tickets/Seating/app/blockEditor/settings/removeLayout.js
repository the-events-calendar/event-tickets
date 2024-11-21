import React, {Fragment, useState} from 'react';
import {Modal, Dashicon, CheckboxControl, Button, Spinner} from '@wordpress/components';
import {__} from '@wordpress/i18n';
import {
	ACTION_REMOVE_EVENT_LAYOUT,
	ajaxNonce,
	ajaxUrl
} from '@tec/tickets/seating/ajax';
import { globals } from '@moderntribe/common/utils';

/**
 * The Remove Layout link component.
 *
 * @since TBD
 */
const RemoveLayout = React.memo(({postId}) => {
	const [isChecked, setChecked] = useState(false);
	const [removeModalOpen, setRemoveModalOpen] = useState(false);
	const [isLoading, setIsLoading] = useState(false);
	const exportUrl = globals.adminUrl() + `edit.php?post_type=tribe_events&page=tickets-attendees&event_id=${postId}`;

	const textUnderline = {
		textDecoration: 'underline'
	};

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
				{__('Remove Seat Layout', 'event-tickets')}
			</a>

			{removeModalOpen && (
				<Modal
					className="tec-tickets-seating__settings--layout-modal"
					title={__('Confirm Seat Layout removal', 'event-tickets')}
					isDismissible={true}
					onRequestClose={closeModal}
					size="medium"
				>
					<div className="tec-tickets-seating__settings-intro">
						<Dashicon icon="warning"/>
						<span className="icon-text">{ __( 'Caution', 'event-tickets' ) }</span>
						<p className="warning-text">
							{__('All attendees will lose their seat assignments. All seated tickets will switch to 1 capacity.', 'event-tickets')}
							{' '}
							<span style={textUnderline}>{__('This action cannot be undone.', 'event-tickets')}</span>
						</p>
					</div>

					{ ! isLoading && (
						<Fragment>
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
									onClick={handleRemoveLayout}
									disabled={!isChecked}
									isPrimary={isChecked}
								>
									{__('Remove Seat Layout', 'event-tickets')}
								</Button>
								<Button
									onClick={closeModal}
									isSecondary={true}
								>
									{__('Cancel', 'event-tickets')}
								</Button>
							</div>
						</Fragment>
					) }
					{isLoading && <Spinner/>}
				</Modal>
			)}
		</Fragment>
	);
});

export default RemoveLayout;