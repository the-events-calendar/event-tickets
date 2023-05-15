<?php return '{
    "0": {
        "type": "html",
        "html": "<div class=\\"tec-tickets__admin-settings-back-link-wrapper\\">\\n\\t<a class=\\"tec-tickets__admin-settings-back-link\\" href=\\"http:\\/\\/wordpress.test\\/wp-admin\\/admin.php?page=tec-tickets-settings&amp;tab=emails\\" role=\\"link\\">\\n\\t\\t&larr; Back to Email Settings\\t<\\/a>\\n<\\/div>\\n"
    },
    "1": {
        "type": "html",
        "html": "<div class=\\"tribe-settings-form-wrap\\">"
    },
    "2": {
        "type": "html",
        "html": "<h2>Ticket Email Settings<\\/h2>"
    },
    "3": {
        "type": "html",
        "html": "<p>Ticket purchasers will receive an email including their ticket and additional info upon completion of purchase. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. <a href=\\"https:\\/\\/evnt.is\\/event-tickets-emails\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">Learn more<\\/a>.<\\/p>"
    },
    "tec-tickets-emails-ticket-enabled": {
        "type": "toggle",
        "label": "Enabled",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-ticket-subject": {
        "type": "text",
        "label": "Subject",
        "default": "Your ticket from {site_title}",
        "placeholder": "Your ticket from {site_title}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-subject-plural": {
        "type": "text",
        "label": "Subject (plural)",
        "default": "Your tickets from {site_title}",
        "placeholder": "Your tickets from {site_title}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-heading": {
        "type": "text",
        "label": "Heading",
        "default": "Here&#039;s your ticket, {attendee_name}!",
        "placeholder": "Here&#039;s your ticket, {attendee_name}!",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-heading-plural": {
        "type": "text",
        "label": "Heading (plural)",
        "default": "Here are your tickets, {attendee_name}!",
        "placeholder": "Here are your tickets, {attendee_name}!",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-ticket-add-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
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
    "4": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_ticket\\" \\/>"
    }
}';
