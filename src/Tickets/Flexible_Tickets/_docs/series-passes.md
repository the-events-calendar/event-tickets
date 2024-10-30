# Flexible Tickets for Event Tickets - Series Passes

## What are Series Passes?

Series Passes are a new type of ticket that allows users to purchase a ticket that will grant them access to multiple
events part of the same Series.
A one-time purchase of a Series Pass will grant access to all events in the Series, regardless of the number of events
in the Series and whether the Series Events are Single or Recurring Events.

Site administrators could sell a Series Pass for a 3-day concert followed by a "Backstage" event; the Series contains
the following Events:

* a Recurring Event happening daily, 3 times 8am to 11pm
* a Single Event happening at 8pm on the 3rd day of the Series

A user purchasing a Series Pass will be able to attend the 3 days of concerts and the Backstage event.

Supported ticket providers for Series Passes are Commerce (both PayPal and Stripe), WooCommerce, and Easy Digital
Downloads.

## Series Passes CRUD operations

The UI to create, edit and delete a Series Pass is almost the same as for any other ticket type.

Code-wise, the Controller responsible for the handling of Series Passes is
the `TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes` one.
The controller will hook on the existing code flow to modify the UI and alter some attributes of the Ticket to stick
with the limits imposed by Series Passes.

Along with the development of the Flexible Tickets feature comes the concept of "ticket type": an explicit attribute
present in the JavasScript and PHP portions of the code to identify the type of ticket being handled.
Series Passes are Tickets of the `series_pass` type.
The ticket type is **not** related to the Provider that is being used to sell it. A Series Pass sold using PayPal will
behave as a Series Pass sold using Easy Digital Downloads.
