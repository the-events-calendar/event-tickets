/**
 * External dependencies
 */
import React, { Fragment, PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { includes } from 'lodash';
import uniqid from 'uniqid';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { constants, options } from '@moderntribe/tickets/data/blocks/ticket';
import { LabeledItem, NumberInput, Select } from '@moderntribe/common/elements';
import { LabelWithTooltip } from '@moderntribe/tickets/elements';
import { ReactSelectOption } from '@moderntribe/common/data/plugins/proptypes';
import './style.pcss';
import {applyFilters} from '@wordpress/hooks';

const { INDEPENDENT, SHARED, TICKET_TYPES, TICKET_LABELS } = constants;
const { CAPACITY_TYPE_OPTIONS } = options;

// Custom input for this type of form
const LabeledNumberInput = ( {
	className,
	id,
	label,
	...props
} ) => (
	<LabeledItem
		className={ classNames(
			'tribe-editor__labeled-number-input',
			className,
		) }
		forId={ id }
		label={ label }
		isLabel={ true }
	>
		<NumberInput { ...props } />
	</LabeledItem>
);

LabeledNumberInput.propTypes = {
	className: PropTypes.string,
	id: PropTypes.string,
	label: PropTypes.string,
};

class Capacity extends PureComponent {
	static propTypes = {
		hasTicketsPlus: PropTypes.bool,
		isDisabled: PropTypes.bool,
		sharedCapacity: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		tempCapacity: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		tempCapacityType: PropTypes.string,
		// Default value, when the Ticket has just been created is an empty object: take that into account.
		tempCapacityTypeOption: PropTypes.oneOfType([ ReactSelectOption, PropTypes.object ]),
		tempSharedCapacity: PropTypes.string,
		onTempCapacityChange: PropTypes.func,
		onTempCapacityNoPlusChange: PropTypes.func,
		onTempCapacityTypeChange: PropTypes.func,
		onTempSharedCapacityChange: PropTypes.func,
		ticketProvider: PropTypes.string,
	};

	static defaultProps = {
		tempCapacityTypeOption: []
	}

	constructor( props ) {
		super( props );
		this.ids = {
			select: uniqid( 'capacity-type-' ),
			capacity: uniqid( 'capacity-' ),
			sharedCapacity: uniqid( 'shared-capacity-' ),
		};
	}

	getInputs = () => {
		const {
			isDisabled,
			sharedCapacity,
			tempCapacityType,
			tempCapacity,
			tempSharedCapacity,
			onTempCapacityChange,
			onTempSharedCapacityChange,
		} = this.props;

		const inputs = [];

		const handleTempSharedCapacityChange = ( e ) => {
			if ( e.target.value === '' || e.target.value > 0 ) {
				onTempSharedCapacityChange( e );
			}
		};

		const handleTempCapacityChange = ( e, max ) => {
			if ( e.target.value === '' || e.target.value > 0 ) {
				if ( max === undefined ) {
					onTempCapacityChange( e );
				} else if ( e.target.value <= max ) {
					onTempCapacityChange( e );
				}
			}
		};

		// If capacity type is shared and does not have shared capacity
		if ( tempCapacityType === TICKET_TYPES[ SHARED ] && sharedCapacity === '' ) {
			inputs.push(
				<LabeledNumberInput
					key="shared-capacity"
					className={ classNames(
						'tribe-editor__ticket__capacity-input-row',
						'tribe-editor__ticket__capacity-input-row--shared-capacity',
					) }
					id={ this.ids.sharedCapacity }
					label={ __( 'Set shared capacity:', 'event-tickets' ) }
					value={ tempSharedCapacity ?? '' }
					onChange={ handleTempSharedCapacityChange }
					disabled={ isDisabled }
					min={ 0 }
					required={ true }
				/>,
			);
		}

		// If capacity type is shared or independent
		if ( includes(
			[ TICKET_TYPES[ SHARED ], TICKET_TYPES[ INDEPENDENT ] ],
			tempCapacityType,
		) ) {
			const extraProps = {};
			const ticketType = tempCapacityType === TICKET_TYPES[ SHARED ] ? SHARED : INDEPENDENT;

			if (
				tempCapacityType === TICKET_TYPES[ SHARED ] &&
					( sharedCapacity || tempSharedCapacity )
			) {
				const max = sharedCapacity ? sharedCapacity : tempSharedCapacity;
				extraProps.max = parseInt( max, 10 ) || 0;
			}

			if ( tempCapacityType === TICKET_TYPES[ INDEPENDENT ] ) {
				extraProps.required = true;
			}

			extraProps.label = tempCapacityType === TICKET_TYPES[ SHARED ]
					? // eslint-disable-next-line no-undef
					  sprintf(
							/* Translators: %s - the singular, lowercase label for a ticket. */
							__('Limit sales of this %s to:', 'event-tickets'),
							TICKET_LABELS.ticket.singularLowercase
					  )
					: // eslint-disable-next-line no-undef
					  sprintf(
							/* Translators: %s - the plural, lowercase label for a ticket. */
							__('Number of %s available', 'event-tickets'),
							TICKET_LABELS.ticket.pluralLowercase
					  );

			inputs.push(
				<LabeledNumberInput
					key="capacity"
					className={ classNames(
						'tribe-editor__ticket__capacity-input-row',
						'tribe-editor__ticket__capacity-input-row--capacity',
						`tribe-editor__ticket__capacity-input-row--capacity-${ ticketType }`,
					) }
					id={ this.ids.capacity }
					value={ tempCapacity ?? '' }
					onChange={ ( e ) => handleTempCapacityChange( e, extraProps?.max ) }
					disabled={ isDisabled }
					min={ 0 }
					{ ...extraProps }
				/>,
			);
		}

		return inputs;
	};

	getCapacityForm = () => {
		const {
			isDisabled,
			tempCapacityTypeOption,
			onTempCapacityTypeChange,
		} = this.props;

		return (
			<Fragment>
				<Select
					id={ this.ids.select }
					className="tribe-editor__ticket__capacity-type-select"
					backspaceRemovesValue={ false }
					value={ tempCapacityTypeOption }
					isSearchable={ false }
					isDisabled={ isDisabled }
					options={ CAPACITY_TYPE_OPTIONS }
					onChange={ onTempCapacityTypeChange }
				/>
				{ this.getInputs() }
			</Fragment>
		);
	};

	getNoPlusCapacityForm = () => {
		const {
			isDisabled,
			tempCapacity,
			onTempCapacityNoPlusChange,
		} = this.props;

		return (
			<Fragment>
				<NumberInput
					className="tribe-editor__ticket__capacity-input"
					id={ this.ids.capacity }
					value={ tempCapacity ?? '' }
					onChange={ onTempCapacityNoPlusChange }
					disabled={ isDisabled }
					min={ 0 }
				/>
				<span className="tribe-editor__ticket__capacity-input-helper-text">
					{ __( 'Leave blank for unlimited', 'event-tickets' ) }
				</span>
			</Fragment>
		);
	};

	render() {
		const { hasTicketsPlus } = this.props;
		let renderForm = hasTicketsPlus ? this.getCapacityForm : this.getNoPlusCapacityForm;

		/**
		 * Filters the function used to render the capacity form.
		 *
		 * By default, the function to render the form is the one used to render the
		 * capacity form depending on Event Tickets Plus being active or not.
		 *
		 * @since 5.16.0
		 *
		 * @param {Function} renderForm The function used to render the capacity form.
		 * @param {Object}   props      The props used to render the Capacity component.
		 */
		renderForm = applyFilters(
			'tec.tickets.blocks.Ticket.Capacity.renderForm',
			renderForm,
			this.props
		);

		return (
			<div className={ classNames(
				'tribe-editor__ticket__capacity',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--capacity',
			) }>
				<LabelWithTooltip
					className="tribe-editor__ticket__capacity-label-with-tooltip"
					forId={ hasTicketsPlus ? this.ids.select : this.ids.capacity }
					isLabel={ true }
					// eslint-disable-next-line no-undef
					label={sprintf(
						/* Translators: %s - the singular label for a ticket. */
						__('%s Capacity', 'event-tickets'),
						TICKET_LABELS.ticket.singular
					)}
				/>
				<div className="tribe-editor__ticket__capacity-form">
					{ renderForm && renderForm() }
				</div>
			</div>
		);
	}
}

export default Capacity;
