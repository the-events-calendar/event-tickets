<?php

/**
 * Modifies the settings for the RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2;
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Tickets\Settings as Tickets_Settings;

/**
 * Class Settings.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2;
 */
class Settings
{
    /**
     * Filters the fields rendered in the Payments tab to replace the toggle to deactivate Tickets Commerce
     * with one that will not allow the user to do that.
     *
     * @since TBD
     *
     * @param array<string,mixed> $fields The fields to render in the tab.
     *
     * @return array<string,mixed> The filtered fields to render in the tab.
     */
    public function change_tickets_commerce_settings(array $fields): array
    {
        if (! isset($fields['tec-settings-payment-enable'])) {
            return $fields;
        }

        $is_tickets_commerce_enabled = tec_tickets_commerce_is_enabled();

        $fields['tec-settings-payment-enable'] = [
            'type' => 'html',
            'html' => '<div>
                  <input
                      type="hidden"
                      name="' . Tickets_Settings::$tickets_commerce_enabled . '"
                      ' . checked($is_tickets_commerce_enabled, true, false) . '
                      id="tickets-commerce-enable-input"
                      class="tribe-dependency tribe-dependency-verified">
              </div>
              <h2 class="tec-tickets__admin-settings-tab-heading">' . esc_html__(
                          'Tickets Commerce',
                          'event-tickets'
                      ) . '</h2>',
        ];

        return $fields;
    }
}
