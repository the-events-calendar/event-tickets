import PropTypes from 'prop-types';
import { Icon } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';

const getMessage = (serviceStatus, serviceConnectUrl) => {
	const style = {
		fontSize: 'var(--tec-font-size-2)',
		lineHeight: 'var(--tec-line-height-2)',
		marginLeft: 'var(--tec-spacer-1)',
	};

	const anchorStyle = {
		color: 'var(--tec-color-link-accent)',
	};

	switch (serviceStatus) {
		case 'down':
			return (
				<span style={style}>
					{__(
						'The Seating Builder service is down and assigned seating is not available. We are working to restore functionality.',
						'event-tickets'
					)}
				</span>
			);
		case 'not-connected':
			return (
				<span style={style}>
					{__(
						'Your site is not connected to the Seating Builder service.',
						'event-tickets'
					)}{' '}
					<a
						style={anchorStyle}
						href={serviceConnectUrl}
						target="_blank"
						rel="noreferrer noopener"
					>
						{_x(
							'You need to connect your site to use assigned seating.',
							'Connect to the Seating Builder link label',
							'event-tickets'
						)}
					</a>
				</span>
			);
		case 'expired-license':
		case 'invalid-license':
			return (
				<span style={style}>
					{__(
						'Your license for Seating has expired.',
						'event-tickets'
					)}{' '}
					<a
						style={anchorStyle}
						href="https://evnt.is/1bdu"
						target="_blank"
						rel="noreferrer noopener"
					>
						{_x(
							'Renew your license to continue using Seating for Event Tickets.',
							'link label for renewing the license',
							'event-tickets'
						)}
					</a>
				</span>
			);

		case 'no-license':
		default:
			return '';
	}
};

const ServiceError = ({ status, serviceConnectUrl }) => {
	const message = getMessage(status, serviceConnectUrl);

	const wrapperStyle = {
		display: 'flex',
		flexDirection: 'row',
		flexWrap: 'nowrap',
	};

	const iconStyle = {
		color: 'var(--tec-color-icon-error)',
	};

	return (
		<div style={wrapperStyle}>
			<Icon icon="warning" size={24} style={iconStyle} />
			{message}
		</div>
	);
};

ServiceError.propTypes = {
	serviceStatus: PropTypes.oneOf(['down', 'not-connected', 'invalid-license'])
		.isRequired,
};

export default ServiceError;
