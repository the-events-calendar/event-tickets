# Flexible Tickets for Event Tickets - Series Passes

Series Passes are a new type of ticket that allows users to purchase a ticket that will grant them access to multiple
events part of the same Series.
A one-time purchase of a Series Pass will grant access to all events in the Series, regardless of the number of events
in the Series
and whether the Series Events are Single or Recurring Events.

Site administrators could sell a Series Pass for a 3-day concert followed by a "Backstage" event; the Series contains
the following Events:

* a Recurring Event happening daily, 3 times 8am to 11pm
* a Single Event happening at 8pm on the 3rd day of the Series

A user purchasing a Series Pass will be able to attend the 3 days of concerts and the Backstage event.

Supported ticket providers for Series Passes are Commerce (both PayPal and Stripe), WooCommerce, and Easy Digital
Downloads.

## Series Passes CRUD operations

The UI to create, edit and delete a Series Pass is the almost the same as for any other ticket type.

Code-wise, the Controller responsible for the handling of Series Passes is
the `TEC\Tickets\Flexible_Tickets\Repository` one.
The controller will hook on the existing AJAX actions to handle the CRUD operations to insert, update and remove the
information stored in the custom tables.

Along with the development of the Flexible Tickets feature comes the concept of "ticket type": an explicit attribute present in teh JavasScript and PHP portions of the code to identify the type of ticket being handled.

The existing Event Tickets code has been modified
