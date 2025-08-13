import * as React from 'react';
import { MouseEvent, useCallback } from 'react';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { ChevronUpIcon, ChevronDownIcon } from '../Icons';
import { TicketComponentProps } from '../../types/TicketComponentProps';

type TicketRowMoverProps = {
	canMoveUp?: boolean;
	canMoveDown?: boolean;
	onMoveUp?: () => void;
	onMoveDown?: () => void;
	rowLabel?: string;
	ticketPosition?: number;
} & TicketComponentProps;

/**
 * TicketRowMover component for moving table rows up and down.
 *
 * @since TBD
 *
 * @param {TicketRowMoverProps} props
 * @return {JSX.Element} The rendered ticket row mover component.
 */
export default function TicketRowMover( props: TicketRowMoverProps ): JSX.Element {
	const {
		canMoveUp = true,
		canMoveDown = true,
		onMoveUp,
		onMoveDown,
		rowLabel = __( 'ticket row', 'event-tickets' ),
		ticketPosition = 0,
	} = props;

	const handleMoveUp = useCallback(
		( event: MouseEvent< HTMLButtonElement > ) => {
			event.preventDefault();
			event.stopPropagation();
			if ( canMoveUp && onMoveUp ) {
				onMoveUp();
			}
		},
		[ canMoveUp, onMoveUp ]
	);

	const handleMoveDown = useCallback(
		( event: MouseEvent< HTMLButtonElement > ) => {
			event.preventDefault();
			event.stopPropagation();
			if ( canMoveDown && onMoveDown ) {
				onMoveDown();
			}
		},
		[ canMoveDown, onMoveDown ]
	);

	const getMoveUpLabel = () => {
		if ( ! canMoveUp ) {
			return __( "This row can't be moved up", 'event-tickets' );
		}
		return rowLabel
			? __( 'Move %s up', 'event-tickets' ).replace( '%s', rowLabel )
			: __( 'Move up', 'event-tickets' );
	};

	const getMoveDownLabel = () => {
		if ( ! canMoveDown ) {
			return __( "This row can't be moved down", 'event-tickets' );
		}
		return rowLabel
			? __( 'Move %s down', 'event-tickets' ).replace( '%s', rowLabel )
			: __( 'Move down', 'event-tickets' );
	};

	const ariaUpLabelId = `move-up-disabled-description-${ ticketPosition }`;
	const ariaDownLabelId = `move-down-disabled-description-${ ticketPosition }`;

	return (
		<div className="classy-field__ticket-row__movers">
			<Button
				__next40pxDefaultSize
				className="classy-field__ticket-row__movers__up"
				icon={ <ChevronUpIcon /> }
				label={ getMoveUpLabel() }
				disabled={ ! canMoveUp }
				accessibleWhenDisabled
				size="compact"
				onClick={ handleMoveUp }
				aria-describedby={ canMoveUp ? undefined : ariaUpLabelId }
			/>
			<Button
				__next40pxDefaultSize
				className="classy-field__ticket-row__movers__down"
				icon={ <ChevronDownIcon /> }
				label={ getMoveDownLabel() }
				disabled={ ! canMoveDown }
				accessibleWhenDisabled
				size="compact"
				onClick={ handleMoveDown }
				aria-describedby={ canMoveDown ? undefined : ariaDownLabelId }
			/>
			{ ! canMoveUp && (
				<span id={ ariaUpLabelId } className="screen-reader-text">
					{ __( 'This row is already at the top of the list', 'event-tickets' ) }
				</span>
			) }
			{ ! canMoveDown && (
				<span id={ ariaDownLabelId } className="screen-reader-text">
					{ __( 'This row is already at the bottom of the list', 'event-tickets' ) }
				</span>
			) }
		</div>
	);
}
