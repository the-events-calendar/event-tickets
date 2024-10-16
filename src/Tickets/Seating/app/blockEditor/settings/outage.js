import { _x } from '@wordpress/i18n';
import { Icon } from '@wordpress/components';

const Outage = () => {
	const iconStyle = {
		color: '#FCB900',
		marginRight: '6px',
	};

	const wrapperStyle = {
		display: 'flex',
		flexDirection: 'row',
		alignItems: 'center',
	};

	const messageStyle = {
		color: 'var(--tec-color-icon-primary-alt)',
		fontWeight: 'var(--tec-font-weight-regular)',
		fontSize: 'var(--tec-font-size-2)',
		lineHeight: 'var(--tec-line-height-1)',
	}

	return (
		<div className="tec-tickets-seating__settings_layout--wrapper">
			<div className={"tec-tickets-seating__settings_layout_upsell--header"}>
				<span className="tec-tickets-seating__settings_layout--title">
					{_x('Seat Layout', 'Seat layout upsell title', 'event-tickets')}
				</span>
			</div>
			<div style={wrapperStyle}>
				<Icon icon="warning" size={16} style={iconStyle}/>
				<span style={messageStyle}>
				{_x('The Seating Builder service is down. We are working to restore the functionality.', 'Seating service outage message', 'event-tickets')}
			</span>
			</div>
		</div>
	);
}

export default Outage;
