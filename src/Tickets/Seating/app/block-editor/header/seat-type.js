import './style.pcss';

const SeatType = ({ name } ) => {
	return (
		<span className="tec-tickets-block__container_header-seat-type">
			{name}
		</span>
	);
}

export default SeatType;