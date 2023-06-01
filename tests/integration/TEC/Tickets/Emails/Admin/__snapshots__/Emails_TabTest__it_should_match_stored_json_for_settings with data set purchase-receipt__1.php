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
        "html": "<h2>Purchase Receipt Email Settings<\\/h2>"
    },
    "3": {
        "type": "html",
        "html": "<p>The ticket purchaser will receive an email about the purchase that was completed. Customize the content of this specific email using the tools below. You can also use email placeholders and customize email templates. <a href=\\"https:\\/\\/evnt.is\\/event-tickets-emails\\" target=\\"_blank\\" rel=\\"noopener noreferrer\\">Learn more<\\/a>.<\\/p>"
    },
    "tec-tickets-emails-purchase-receipt-enabled": {
        "type": "toggle",
        "label": "Enable Purchase Receipt",
        "default": true,
        "validation_type": "boolean"
    },
    "tec-tickets-emails-purchase-receipt-subject": {
        "type": "text",
        "label": "Subject",
        "default": "Your purchase receipt for #{order_number}",
        "placeholder": "Your purchase receipt for #{order_number}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-purchase-receipt-heading": {
        "type": "text",
        "label": "Heading",
        "default": "Your purchase receipt for #{order_number}",
        "placeholder": "Your purchase receipt for #{order_number}",
        "size": "large",
        "validation_callback": "is_string"
    },
    "tec-tickets-emails-purchase-receipt-additional-content": {
        "type": "wysiwyg",
        "label": "Additional content",
        "default": "",
        "tooltip": "Additional content will be displayed below the purchase receipt details in the email.",
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
    "4": {
        "type": "html",
        "html": "<input type=\\"hidden\\" name=\\"tec_tickets_emails_current_section\\" id=\\"tec_tickets_emails_current_section\\" value=\\"tec_tickets_emails_purchase_receipt\\" \\/>"
    }
}';
