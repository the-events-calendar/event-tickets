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
        "html": "<h2>RSVP Email Settings<\\/h2>"
    },
    "3": {
        "type": "html",
        "html": "<p>Registrants will receive an email including their RSVP info upon registration. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {rsvp_name} can be used to pull dynamic content from the RSVP into your email. Learn more about customizing email templates in our Knowledgebase.<\\/p>"
    },
    "tec-tickets-emails-rsvp-enabled": {
        "type": "toggle",
        "label": "Enabled",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-rsvp-use-ticket-email": {
        "type": "toggle",
        "label": "Use Ticket Email",
        "tooltip": "Use the ticket email settings and template.",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-rsvp-add-event-links": {
        "type": "toggle",
        "label": "Include &quot;Add to calendar&quot; links",
        "tooltip": "Include links to add the event to the user&#039;s calendar.",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-rsvp-add-event-ics": {
        "type": "toggle",
        "label": "Attach calendar invites",
        "tooltip": "Attach calendar invites (.ics) to the RSVP email.",
        "default": true,
        "validation_type": "boolean"
    },
    "4": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_rsvp\\" \\/>"
    }
}';
