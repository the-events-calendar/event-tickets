<?php

namespace TEC\Tickets\Admin\Onboarding;

class Square_Modifiers {

    public function get_supported_countries(): array {
        return [
            'US' => 'United States',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'JP' => 'Japan',
            'GB' => 'United Kingdom',
            'IE' => 'Ireland',
            'FR' => 'France',
            'ES' => 'Spain',
        ];
    }
}
