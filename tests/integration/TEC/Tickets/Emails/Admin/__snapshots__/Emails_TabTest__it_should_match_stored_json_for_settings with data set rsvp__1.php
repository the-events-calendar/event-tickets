<?php return '{
    "0": {
        "type": "html",
        "html": "<div class=\\"tec-tickets__admin-settings-back-link-wrapper tec-settings-form__header-block--horizontal\\">\\n\\t<a class=\\"tec-tickets__admin-settings-back-link\\" href=\\"http:\\/\\/wordpress.test\\/wp-admin\\/admin.php?page=tec-tickets-settings&amp;tab=emails\\" role=\\"link\\">\\n\\t\\t&larr; Back to Email Settings\\t<\\/a>\\n<\\/div>\\n"
    },
    "tec-settings-email-template-wrapper_start": {
        "type": "html",
        "html": "<div class=\\"tec-settings-form__header-block--horizontal\\">"
    },
    "tec-settings-email-template-header": {
        "type": "html",
        "html": "<h3>RSVP Email Settings<\\/h3>"
    },
    "info-box-description": {
        "type": "html",
        "html": "<p class=\\"tec-settings-form__section-description\\">Registrants will receive an email including their RSVP info upon registration. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. <a href=\\"https:\\/\\/evnt.is\\/event-tickets-emails\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">Learn more<\\/a>.<\\/p>"
    },
    "1": {
        "type": "html",
        "html": "<\\/div>"
    },
    "tec-settings-email-template-settings-wrapper-start": {
        "type": "html",
        "html": "<div class=\\"tec-settings-form__content-section\\">"
    },
    "tec-settings-email-template-settings": {
        "type": "html",
        "html": "<h3 class=\\"tec-settings-form__section-header tec-settings-form__section-header--sub\\">Settings<\\/h3>"
    },
    "tec-settings-email-template-settings-wrapper-end": {
        "type": "html",
        "html": "<\\/div>"
    },
    "tec-tickets-emails-rsvp-enabled": {
        "type": "toggle",
        "label": "Enable RSVP Email",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-rsvp-use-ticket-email": {
        "type": "toggle",
        "label": "Use Ticket Email",
        "tooltip": "Use the ticket email settings and template.",
        "default": true,
        "validation_type": "boolean",
        "attributes": {
            "id": "tec-tickets-emails-rsvp-use-ticket-email"
        }
    },
    "tec-tickets-emails-rsvp-subject": {
        "type": "text",
        "label": "Subject",
        "default": "Your tickets from {site_title}",
        "placeholder": "Your tickets from {site_title}",
        "size": "large",
        "validation_type": "textarea",
        "fieldset_attributes": {
            "data-depends": "#tec-tickets-emails-rsvp-use-ticket-email",
            "data-condition-is-not-checked": true
        },
        "class": "tribe-dependent"
    },
    "tec-tickets-emails-rsvp-heading": {
        "type": "text",
        "label": "Heading",
        "default": "Here&#039;s your tickets, {attendee_name}!",
        "placeholder": "Here&#039;s your tickets, {attendee_name}!",
        "size": "large",
        "validation_type": "textarea",
        "fieldset_attributes": {
            "data-depends": "#tec-tickets-emails-rsvp-use-ticket-email",
            "data-condition-is-not-checked": true
        },
        "class": "tribe-dependent"
    },
    "tec-tickets-emails-rsvp-additional-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "size": "large",
        "tooltip": "Additional content will be displayed below the RSVP information in your email.",
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
        },
        "fieldset_attributes": {
            "data-depends": "#tec-tickets-emails-rsvp-use-ticket-email",
            "data-condition-is-not-checked": true
        },
        "class": "tribe-dependent"
    },
    "tec-tickets-emails-rsvp-add-event-links": {
        "type": "checkbox_bool",
        "label": "Calendar links",
        "tooltip": "Include iCal and Google event links in this email.",
        "default": true,
        "validation_type": "boolean",
        "fieldset_attributes": {
            "data-depends": "#tec-tickets-emails-rsvp-use-ticket-email",
            "data-condition-is-not-checked": true
        }
    },
    "tec-tickets-emails-rsvp-add-event-ics": {
        "type": "checkbox_bool",
        "label": "Calendar invites",
        "tooltip": "Attach calendar invites (.ics) to the RSVP email.",
        "default": true,
        "validation_type": "boolean",
        "fieldset_attributes": {
            "data-depends": "#tec-tickets-emails-rsvp-use-ticket-email",
            "data-condition-is-not-checked": true
        }
    },
    "2": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_rsvp\\" \\/>"
    }
}';
