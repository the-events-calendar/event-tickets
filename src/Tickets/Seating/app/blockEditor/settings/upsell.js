import { ECP as PlusIcon } from '@moderntribe/tickets/icons';
import { _x } from '@wordpress/i18n';
const Upsell = () => {
	const anchorStyle = {
		color: 'var(--tec-color-link-accent)',
	};

	return (
		<div className="tec-tickets-seating__settings_layout--wrapper">
			<div className={"tec-tickets-seating__settings_layout_upsell--header"}>
				<span className="tec-tickets-seating__settings_layout--title">
					{ _x( 'Seat Layout', 'Seat layout upsell title', 'event-tickets' ) }
				</span>
				<PlusIcon/>
			</div>

			<span className={"tec-tickets-seating__settings_layout--description"}>
				{
					_x(
						'Allow purchasers to select seats with',
						'Seat layout upsell description start',
						'event-tickets'
					)
				}{' '}
				<a
					style={anchorStyle}
					href="https://evnt.is/add-seating"
					target="_blank"
					rel="noreferrer noopener"
				>
					{ _x( 'Seating', 'Seat layout upsell link label', 'event-tickets' ) }
				</a>{' '}
				{
					_x(
						'for Event Tickets.',
						'Seat layout upsell description end',
						'event-tickets'
					)
				}
			</span>
		</div>
	);
}
export default Upsell;