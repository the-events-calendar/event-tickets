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
        "html": "<h3>Completed Order Email Settings<\\/h3>"
    },
    "info-box-description": {
        "type": "html",
        "html": "<p class=\\"tec-settings-form__section-description\\">The site admin will receive an email about any orders that were made. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. <a href=\\"https:\\/\\/evnt.is\\/event-tickets-emails\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">Learn more<\\/a>.<\\/p><br\\/>"
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
    "tec-tickets-emails-completed-order-enabled": {
        "type": "toggle",
        "label": "Enable Completed Order",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-completed-order-recipient": {
        "type": "text",
        "label": "Recipient(s)",
        "default": "admin@wordpress.test",
        "tooltip": "Add additional recipient emails separated by commas.",
        "size": "large",
        "validation_type": "email_list"
    },
    "tec-tickets-emails-completed-order-subject": {
        "type": "text",
        "label": "Subject",
        "default": "[{site_title}]: Completed order #{order_number}",
        "placeholder": "[{site_title}]: Completed order #{order_number}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-completed-order-heading": {
        "type": "text",
        "label": "Heading",
        "default": "Completed order: #{order_number}",
        "placeholder": "Completed order: #{order_number}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-completed-order-additional-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "tooltip": "Additional content will be displayed below the order details.",
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
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_completed_order\\" \\/>"
    }
}';
