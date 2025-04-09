import React from "react";
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from "@wordpress/data";
import { SETTINGS_STORE_KEY } from "../../../data";
import NextButton from '../../buttons/next';
import SkipButton from '../../buttons/skip';
import ViewCheckbox from './inputs/view-checkbox';
// Import our icons.
import DayViewIcon from './img/day';
import MonthViewIcon from './img/month';
import ListViewIcon from './img/list';
import PhotoViewIcon from './img/photo';
import MapViewIcon from './img/map';
import SummaryViewIcon from './img/summary';
import WeekViewIcon from './img/week';
import BoltIcon from './img/pro-bolt';

const icons = new Map();
icons.set('day', <DayViewIcon />);
icons.set('month', <MonthViewIcon />);
icons.set('list', <ListViewIcon />);
// pro
icons.set('map', <MapViewIcon />);
icons.set('photo', <PhotoViewIcon />);
icons.set('summary', <SummaryViewIcon />);
icons.set('week', <WeekViewIcon />);
const proViews = new Map ();
proViews.set('map', __("Map", "event-tickets"));
proViews.set('photo', __("Photo", "event-tickets"));
proViews.set('summary', __("Summary", "event-tickets"));
proViews.set('week', __("Week", "event-tickets"));

const DisplayContent: React.FC = ({moveToNextTab, skipToNextTab}) => {
	const availableViews = useSelect(select => select(SETTINGS_STORE_KEY).getSetting('availableViews') || [], []);
	const tribeEnableViews = useSelect(select => select(SETTINGS_STORE_KEY).getSetting('tribeEnableViews') || [], []);

	// If we have more than 3 views, we have ECP installed.
	const hasProViews = availableViews.length > 3;

	// Track which views are checked.
	const [checkedViews, setCheckedViews] = useState<string[]>(tribeEnableViews);

	// Check if all views are selected.
	const isAllChecked = availableViews.every(view => checkedViews.includes(view));

	// Update the checked state when a checkbox changes.
	const handleCheckboxChange = (view: string, isChecked: boolean) => {
		if (view === "all") {
			// If "all" is checked, set all views to checked; if unchecked, clear all.
			setCheckedViews(isChecked ? [...availableViews] : []);
		} else {
			// For individual views, update checkedViews accordingly.
			setCheckedViews(prevChecked =>
				isChecked ? [...prevChecked, view] : prevChecked.filter(v => v !== view)
			);
		}
	};

	// Create tabSettings object to pass to NextButton.
	const tabSettings = {
		tribeEnableViews: checkedViews,
		currentTab: 1, // Include the current tab index.
	};

	// Check if any checkboxes are selected.
	const isAnyChecked = checkedViews.length > 0;

	return (
		<>
			<div className="event-tickets-onboarding__tab-header">
				<h1 className="event-tickets-onboarding__tab-heading">
					{__("How do you want people to view your calendar?", "event-tickets")}
				</h1>
				<p className="event-tickets-onboarding__tab-subheader">
					{__("Select how you want to display your events on your site. You can choose more than one.", "event-tickets")}
				</p>
			</div>
			<div className="event-tickets-onboarding__tab-content">
				<div className="event-tickets-onboarding__grid--view-checkbox">
					{/* Individual checkboxes */}
					{availableViews.map((view, key) => (
						<span key={key}>
							<ViewCheckbox
								view={view}
								isChecked={checkedViews.includes(view)} // Pass the checked state to each checkbox.
								onChange={handleCheckboxChange} // Pass the handler for individual views.
								icon={icons.get(view)}
							/>
						</span>
					))}
					{/* "All" Checkbox */}
					<ViewCheckbox
						view="all"
						isChecked={isAllChecked} // "All" checkbox reflects the state of all views.
						onChange={handleCheckboxChange} // Pass the handler for "all".
						icon=""
					/>
				</div>
				{( !hasProViews && (
					<div className="event-tickets-onboarding__view_upsell">
						<p className="event-tickets-onboarding__view_upsell_callout">
							{__("More views available with", "event-tickets")} <BoltIcon
							className="event-tickets-onboarding_pro-icon"/>
							<a href="https://evnt.is/etp" target="_blank" rel="noopener noreferrer">
								{__("Event Tickets Pro", "event-tickets")}
							</a>
						</p>
						<div className="event-tickets-onboarding__view_upsell_list">
							<div className="event-tickets-onboarding__view_upsell-cell">
								{icons.get('map')}
								<span className="event-tickets-onboarding__view_upsell-label">{proViews.get('map')}</span>
							</div>
							<div className="event-tickets-onboarding__view_upsell-cell">
								{icons.get('photo')}
								<span className="event-tickets-onboarding__view_upsell-label">{proViews.get('photo')}</span>
							</div>
							<div className="event-tickets-onboarding__view_upsell-cell">
								{icons.get('summary')}
								<span className="event-tickets-onboarding__view_upsell-label">{proViews.get('summary')}</span>
							</div>
							<div className="event-tickets-onboarding__view_upsell-cell">
								{icons.get('week')}
								<span className="event-tickets-onboarding__view_upsell-label">{proViews.get('week')}</span>
							</div>
						</div>
					</div>

				))}

				{!isAnyChecked && (
					<p className="event-tickets-onboarding__view_required_notice">
						{__("Please select at least one view to continue.", "event-tickets")}
					</p>
				)}

				<NextButton disabled={!isAnyChecked} moveToNextTab={moveToNextTab} tabSettings={tabSettings} />

				<SkipButton skipToNextTab={skipToNextTab} currentTab={1} />
			</div>
		</>
	);
};

export default DisplayContent;
