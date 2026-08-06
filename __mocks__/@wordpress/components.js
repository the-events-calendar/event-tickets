/**
 * External dependencies
 */
import { noop } from 'lodash';

export const withAPIData = () => noop;
export const Spinner = () => "🏃‍♂️";
export const Modal = ( { title, children } ) => (
	<div>
		<span>{ title }</span>
		<span>{ children }</span>
	</div>
);
export const Dashicon = ( { className, icon } ) => <span className={ className }>{ icon }</span>;
export const Dropdown = () => <span>Dropdown</span>;
export const Tooltip = () => <span>Tooltip</span>;
export const PanelBody = ({ children }) => <span className="PanelBody">{ children }</span>;
export const RadioControl = () => <input type="radio" value="" />;
export const ToggleControl = ( { label, checked, help } ) => (
	<span className="ToggleControl">
		{ label }
		{ !! checked }
		{ help }
	</span>
);
