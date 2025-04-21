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
    "tec-tickets-emails-rsvp-not-going-enabled": {
        "type": "toggle",
        "label": "Enable RSVP &quot;Not Going&quot; Email",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-rsvp-not-going-subject": {
        "type": "text",
        "label": "Subject",
        "default": "You confirmed you will not be attending",
        "placeholder": "You confirmed you will not be attending",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-rsvp-not-going-heading": {
        "type": "text",
        "label": "Heading",
        "default": "You confirmed you will not be attending",
        "placeholder": "You confirmed you will not be attending",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-rsvp-not-going-additional-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "tooltip": "Additional content will be displayed below the information in your email.",
        "size": "large",
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
    "3": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_rsvp_not_going\\" \\/>"
    }
}';
