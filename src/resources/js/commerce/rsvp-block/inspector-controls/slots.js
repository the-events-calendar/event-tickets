/**
 * Slot/Fill definitions for RSVP Block extensibility
 *
 * @since TBD
 */
import { createSlotFill } from '@wordpress/components';

/**
 * Slot for RSVP Settings panel
 * This is where core RSVP settings like capacity, dates, etc. are rendered
 */
export const { 
	Fill: RSVPSettingsFill, 
	Slot: RSVPSettingsSlot 
} = createSlotFill( 'TEC.RSVPBlock.Settings' );

/**
 * Slot for Advanced settings panel
 * This is where advanced options like "Can't go" responses are rendered
 */
export const { 
	Fill: RSVPAdvancedFill, 
	Slot: RSVPAdvancedSlot 
} = createSlotFill( 'TEC.RSVPBlock.Advanced' );

/**
 * Slot for third-party extensions
 * This allows external plugins to add their own controls to the RSVP block
 */
export const { 
	Fill: RSVPExtensionsFill, 
	Slot: RSVPExtensionsSlot 
} = createSlotFill( 'TEC.RSVPBlock.Extensions' );

/**
 * Export all fills together for convenience
 */
export const RSVPBlockFills = {
	Settings: RSVPSettingsFill,
	Advanced: RSVPAdvancedFill,
	Extensions: RSVPExtensionsFill,
};

/**
 * Export all slots together for convenience
 */
export const RSVPBlockSlots = {
	Settings: RSVPSettingsSlot,
	Advanced: RSVPAdvancedSlot,
	Extensions: RSVPExtensionsSlot,
};