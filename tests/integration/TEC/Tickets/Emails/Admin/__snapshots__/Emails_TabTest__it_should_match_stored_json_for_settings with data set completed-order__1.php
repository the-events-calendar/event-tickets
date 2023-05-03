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
        "html": "<h2>Completed Order Email Settings<\\/h2>"
    },
    "3": {
        "type": "html",
        "html": "<p>The site admin will receive an email about any orders that were made. Customize the content of this specific email using the tools below. The brackets {event_name}, {event_date}, and {ticket_name} can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our Knowledgebase.<\\/p>"
    },
    "tec-tickets-emails-completed-order-enabled": {
        "type": "toggle",
        "label": "Enabled",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-completed-order-recipient": {
        "type": "text",
        "label": "Recipient(s)",
        "default": "admin@wordpress.test",
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
    "tec-tickets-emails-completed-order-add-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "tooltip": "Additional content will be displayed below the order details.",
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
                "alignright"
            ]
        }
    },
    "4": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_completed_order\\" \\/>"
    }
}';
