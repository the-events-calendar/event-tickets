/**
 * External Dependencies
 */
import { Modal, MenuGroup, MenuItemsChoice, Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Input, Select } from '@moderntribe/common/elements';
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import './style.pcss';
export default class MoveModal extends PureComponent {
	static propTypes = {
		hasSelectedPost: PropTypes.bool.isRequired,
		hideModal: PropTypes.func.isRequired,
		initialize: PropTypes.func.isRequired,
		isFetchingPosts: PropTypes.bool.isRequired,
		isModalShowing: PropTypes.bool.isRequired,
		onPostSelect: PropTypes.func.isRequired,
		onPostTypeChange: PropTypes.func.isRequired,
		onSearchChange: PropTypes.func.isRequired,
		onSubmit: PropTypes.func.isRequired,
		postOptions: PropTypes.arrayOf( PropTypes.object ),
		postTypeOptions: PropTypes.arrayOf( PropTypes.object ),
		postTypeOptionValue: PropTypes.object,
		postValue: PropTypes.string.isRequired,
		search: PropTypes.string.isRequired,
		title: PropTypes.string.isRequired,
	}

	static defaultProps = {
		title: __( 'Move Ticket Types', 'events-gutenberg' ),
	}

	componentDidMount() {
		this.props.initialize();
	}

	renderPostTypes = () => {
		if ( this.props.isFetchingPosts ) {
			return <Spinner />;
		}

		return (
			this.props.postOptions.length
				? (
					<MenuGroup>
						<MenuItemsChoice
							choices={ this.props.postOptions }
							value={ this.props.postValue }
							onSelect={ this.props.onPostSelect }
						/>
					</MenuGroup>
				)
				: (
					<Notice
						isDismissible={ false }
						status="warning"
					>
						{
							__( 'No posts found', 'events-gutenberg' )
						}
					</Notice>
				)

		);
	}

	render() {
		return (
			<Modal
				title={ this.props.title }
				onRequestClose={ this.props.hideModal }
				className="tribe-editor__tickets__move-modal"
			>
				<label>
					{ __( 'You can optionally focus on a specific post type:', 'events-gutenberg' ) }
					<Select
						options={ this.props.postTypeOptions }
						onChange={ this.props.onPostTypeChange }
						value={ this.props.postTypeOptionValue }
					/>
				</label>

				<label>
					{ __( 'You can also enter keywords to help find the target event by title or description', 'events-gutenberg' ) }
					<Input
						type="text"
						onChange={ this.props.onSearchChange }
						value={ this.props.search }
					/>
				</label>

				<label>
					{ __( 'Select the post you wish to move the ticket type to:', 'events-gutenberg' ) }
					{ this.renderPostTypes() }
				</label>

				<footer>
					<Button
						isPrimary
						disabled={ ! this.props.hasSelectedPost }
						onClick={ this.props.onSubmit }
					>
						{ __( 'Finish!', 'events-gutenberg' ) }
					</Button>
				</footer>
			</Modal>
		);
	}
}
