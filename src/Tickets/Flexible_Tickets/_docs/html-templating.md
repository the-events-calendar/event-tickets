# Flexible Tickets for Event Tickets - HTML Templating

Template files are used to render the HTML output of the plugin hydrating them at runtime with dynamic information.
Following the convention of other plugins part of The Events Calendar family, the feature templates are divided into
two categories:

* Admin Views: templates used to render the HTML output of the plugin in the WordPress admin.
* Frontend Views: templates used to render the HTML output of the plugin in the frontend.

Controllers should only render HTML by means of templates.

## Admin Views

The admin views templates are located in the `src/admin-views/flexible-tickets` directory.
The class in charge of managing and rendering the admin views is
the `TEC\Tickets\Flexible_Tickets\Templates\Admin_Views` one.

Admin templates are **not** overrideable by users to make sure that the plugin is not affected by any changes made to
the templates.

## Frontend Views

The frontend views templates are located in the `src/views/flexible-tickets` directory.
The class in charge of managing and rendering the frontend views is
the `TEC\Tickets\Flexible_Tickets\Templates\Frontend_Views` one.

Templates follow the TEC convention and can be overridden at the single template file level by following
the [Knowledge Base article][1].

## Template tags

The Flexible Tickets feature provides a set of template tags that can be used in custom templates to render HTML
provided, or affected, by the feature.

* `tec_tickets_get_series_pass_singular_lowercase` - returns the singular label for a Series Pass; defaults
  to `series pass`; filterable with `tec_tickets_series_pass_singular_lowercase`.
* `tec_tickets_get_series_pass_singular_uppercase` - returns the singular uppercase label for a Series Pass; defaults
  to `Series Pass`; filterable with `tec_tickets_series_pass_singular_uppercase`.

[1]:https://theeventscalendar.com/knowledgebase/k/customizing-template-files-2/

