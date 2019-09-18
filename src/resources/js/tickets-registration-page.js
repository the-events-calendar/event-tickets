// For compatibility purposes we add this
if ('undefined' === typeof tribe) {
    tribe = {};
}

if ('undefined' === typeof tribe.tickets) {
    tribe.tickets = {};
}

tribe.tickets.registration = {};

(function($, obj) {
    'use strict';

    obj.hasChanges = {};

    obj.formClasses = {
        woo: 'tribe-tickets__item__attendee__fields__form--woo',
        edd: 'tribe-tickets__item__attendee__fields__form--edd',
    }

    obj.selector = {
        container: '.tribe-tickets__registration__event',
        fields: '.tribe-tickets__item__attendee__fields',
        fieldsError: '.tribe-tickets__item__attendee__fields__error',
        fieldsErrorRequired: '.tribe-tickets__item__attendee__fields__error--required',
        fieldsErrorAjax: '.tribe-tickets__item__attendee__fields__error--ajax',
        fieldsSuccess: '.tribe-tickets__item__attendee__fields__success',
        loader: '.tribe-tickets__item__attendee__fields__loader',
        form: '.tribe-tickets__item__attendee__fields__form',
        toggler: '.tribe-tickets__registration__toggle__handler',
        status: '.tribe-tickets__registration__status',
        field: {
            text: '.tribe-tickets__item__attendee__field__text',
            checkbox: '.tribe-tickets__item__attendee__field__checkbox',
            select: '.tribe-tickets__item__attendee__field__select',
            radio: '.tribe-tickets__item__attendee__field__radio',
        },
        checkout: '.tribe-tickets__registration__checkout',
        checkoutButton: '.tribe-tickets__registration__checkout__submit'
    };

    var $tribe_registration = $(obj.selector.container);

    // Bail if there are no tickets on the current event/page/post
    if (0 === $tribe_registration.length) {
        return;
    }

    /**
     * Handle the toggle for each event
     *
     * @since 4.9
     *
     * @return void
     */
    $(obj.selector.container).on(
        'click',
        obj.selector.toggler,
        function(e) {
            e.preventDefault();

            var $this = $(this);
            var $event = $this.closest(obj.selector.container);

            $event.find(obj.selector.fields).toggle();
            $this.toggleClass('open');

        });

    /**
     * Check if the required fields have data
     *
     * @since 4.9
     *
     * @return void
     */
    obj.validateEventAttendees = function($form) {
        var is_valid = true;
        var $fields = $form.find('.tribe-tickets-meta-required');

        $fields.each(function() {
            var $field = $(this);
            var val = '';

            if (
                $field.is(obj.selector.field.radio) ||
                $field.is(obj.selector.field.checkbox)
            ) {
                val = $field.find('input:checked').length ? 'checked' : '';
            } else if ($field.is(obj.selector.field.select)) {
                val = $field.find('select').val();
            } else {
                val = $field.find('input, textarea').val().trim();
            }

            if (0 === val.length) {
                is_valid = false;
            }

        });

        return is_valid;
    };

    /**
     * Update container status to complete
     *
     * @since 4.10.1
     *
     * @return void
     */
    obj.updateStatusToComplete = function($event) {
        $event.find(obj.selector.status).removeClass('incomplete');
        $event.find(obj.selector.status).find('i').removeClass('dashicons-edit');
        $event.find(obj.selector.status).find('i').addClass('dashicons-yes');
    };

    /**
     * Update container status to incomplete
     *
     * @since 4.10.1
     *
     * @return void
     */
    obj.updateStatusToIncomplete = function($event) {
        $event.find(obj.selector.status).addClass('incomplete');
        $event.find(obj.selector.status).find('i').addClass('dashicons-edit');
        $event.find(obj.selector.status).find('i').removeClass('dashicons-yes');
    };

    obj.handleTppSaveSubmission = function(e) {
        var $form = $(this);
        var $fields = $form.closest(obj.selector.fields);

        // hide all messages
        $fields.find(obj.selector.fieldsErrorRequired).hide();

        if (!obj.validateEventAttendees($form)) {
            e.preventDefault();
            $fields.find(obj.selector.fieldsErrorRequired).show();
        }
    };

    /**
     * Handle save attendees info form submission via ajax.
     * Display a message if there are required fields missing.
     *
     * @since 4.9
     *
     * @return void
     */
    obj.handleSaveSubmission = function(e) {
        e.preventDefault();
        var $form = $(this);
        var $fields = $form.closest(obj.selector.fields);
        var $event = $fields.closest(obj.selector.container);

        // hide all messages
        $fields.find(obj.selector.fieldsErrorRequired).hide();
        $fields.find(obj.selector.fieldsErrorAjax).hide();
        $fields.find(obj.selector.fieldsSuccess).hide();

        if (!obj.validateEventAttendees($form)) {
            $fields.find(obj.selector.fieldsErrorRequired).show();
            obj.updateStatusToIncomplete($event)
        } else {
            $fields.find(obj.selector.loader).show();

            var ajaxurl = '';
            var nonce = '';

            if (typeof TribeTicketsPlus === 'object') {
                ajaxurl = TribeTicketsPlus.ajaxurl;
                nonce = TribeTicketsPlus.save_attendee_info_nonce;
            }

            var eventId = $event.data('event-id');
            var params = $form.serializeArray();
            params.push({ name: 'event_id', value: eventId });
            params.push({ name: 'action', value: 'tribe-tickets-save-attendee-info' });
            params.push({ name: 'nonce', value: nonce });

            $.post(
                ajaxurl,
                params,
                function(response) {
                    if (response.success) {
                        obj.updateStatusToComplete($event)
                        obj.hasChanges[eventId] = false;
                        $fields.find(obj.selector.fieldsSuccess).show();

                        if (response.data.meta_up_to_date) {
                            $(obj.selector.checkoutButton).removeAttr('disabled');
                        }
                    }
                }
            ).fail(function() {
                $fields.find(obj.selector.fieldsErrorAjax).show();
            }).always(function() {
                $fields.find(obj.selector.loader).hide();
            });
        }
    };

    /**
     * Handle checkout form submission.
     * Display a confirm if there are any changes to the attendee info that have not been saved
     *
     * @since 4.9
     *
     * @return void
     */
    obj.handleCheckoutSubmission = function(e) {
        var eventIds = Object.keys(obj.hasChanges);
        var hasChanges = eventIds.reduce(function(hasChanges, eventId) {
            return hasChanges || obj.hasChanges[eventId];
        }, false);

        if (hasChanges && !confirm(tribe_l10n_datatables.registration_prompt)) {
            e.preventDefault();
            return;
        }
    };

    /**
     * Sets hasChanges flag to true for given eventId
     *
     * @since 4.9
     *
     * @return void
     */
    obj.setHasChanges = function(eventId) {
        return function() {
            obj.hasChanges[eventId] = true;
        };
    };

    /**
     * Bind event handlers to each form field
     *
     * @since 4.9
     *
     * @return void
     */
    obj.bindFormFields = function($event) {
        // set up hasChanges flag for event
        var eventId = $event.data('event-id');
        obj.hasChanges[eventId] = false;

        var $fields = [
            $event.find(obj.selector.field.text),
            $event.find(obj.selector.field.checkbox),
            $event.find(obj.selector.field.radio),
            $event.find(obj.selector.field.select),
        ];

        $fields.forEach(function($field) {
            var $formElement;

            if (
                $field.is(obj.selector.field.radio) ||
                $field.is(obj.selector.field.checkbox)
            ) {
                $formElement = $field.find('input');
            } else if ($field.is(obj.selector.field.select)) {
                $formElement = $field.find('select');
            } else {
                $formElement = $field.find('input, textarea');
            }

            $formElement.change(obj.setHasChanges(eventId));
        });
    };

    /**
     * Bind event handlers to checkout form
     */
    obj.bindCheckout = function() {
        $(obj.selector.checkout).submit(obj.handleCheckoutSubmission);
    };

    /**
     * Bind event handlers
     *
     * @since 4.9
     *
     * @return void
     */
    obj.bindEvents = function() {
        obj.bindCheckout();
    };

    /**
     * Init containers for each event
     *
     * @since 4.10.1
     *
     * @return void
     */
    obj.initContainers = function() {
        $(obj.selector.container).each(function() {
            var $event = $(this);
            var allRequired = obj.validateEventAttendees($event);

            allRequired
                ?
                obj.updateStatusToComplete($event) :
                obj.updateStatusToIncomplete($event);

            // bind submission handler to each form
            var $form = $event.find(obj.selector.form);
            if ($form.hasClass(obj.formClasses.woo) || $form.hasClass(obj.formClasses.edd)) {
                $($form).on('submit', obj.handleSaveSubmission);
            } else {
                $($form).on('submit', obj.handleTppSaveSubmission);
            }

            // bind form fields to update hasChanges flag
            obj.bindFormFields($event);
        });
    };

    /**
     * Init the page, set a flag for those events that need to fill inputs
     * Toggle down those who are ready
     *
     * @since 4.9
     *
     * @return void
     */
    obj.initPage = function() {
        obj.initContainers();
        obj.bindEvents();
    };

    /**
     * Init the tickets registration script
     *
     * @since 4.9
     *
     * @return void
     */
    obj.init = function() {
        obj.initPage();
    }

    obj.init();


})(jQuery, tribe.tickets.registration);
