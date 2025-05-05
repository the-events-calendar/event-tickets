<?php return '{
    "0": {
        "type": "html",
        "html": "<div class=\\"tec-tickets__admin-settings-back-link-wrapper tec-settings-form__header-block--horizontal\\">\\n\\t<a class=\\"tec-tickets__admin-settings-back-link\\" href=\\"http:\\/\\/wordpress.test\\/wp-admin\\/admin.php?page=tec-tickets-settings&amp;tab=emails\\" role=\\"link\\">\\n\\t\\t&larr; Back to Email Settings\\t<\\/a>\\n<\\/div>\\n"
    },
    "tec-settings-email-template-header": {},
    "1": {
        "type": "html",
        "html": "<div>"
    },
    "tec-tickets-emails-ticket-enabled": {
        "type": "toggle",
        "label": "Enable Ticket Email",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-ticket-subject": {
        "type": "text",
        "label": "Subject",
        "default": "Your tickets from {site_title}",
        "placeholder": "Your tickets from {site_title}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-heading": {
        "type": "text",
        "label": "Heading",
        "default": "Here&#039;s your tickets, {attendee_name}!",
        "placeholder": "Here&#039;s your tickets, {attendee_name}!",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-additional-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "size": "large",
        "tooltip": "Additional content will be displayed below the tickets in your email.",
        "validation_type": "html",
        "settings": {
            "media_buttons": false,
            "quicktags": false,
            "editor_height": 200,
            "buttons": [
                "bold",
                "italic",
                "underline",
                "strikethrough",
                "alignleft",
                "aligncenter",
                "alignright",
                "link"
            ]
        }
    },
    "2": {
        "type": "html",
        "html": "<\\/div>"
    },
    "tec-tickets-emails-ticket-add-event-links": {
        "type": "checkbox_bool",
        "label": "Calendar links",
        "tooltip": "Include iCal and Google event links in this email.",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-ticket-add-event-ics": {
        "type": "checkbox_bool",
        "label": "Calendar invites",
        "tooltip": "Attach calendar invites (.ics) to the ticket email.",
        "default": true,
        "validation_type": "boolean"
    },
    "3": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_ticket\\" \\/>"
    }
}';
