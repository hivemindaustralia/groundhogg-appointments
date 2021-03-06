(function ($, calendar) {
    $.extend(calendar, {
        date: null,
        bookingData: null,
        init: function () {

            var self = this;

            this.date = $('#date-picker');
            this.bookingData = {};

            this.date.on('change', function () {

                self.refreshTimeSlots();

            });

            if (self.tab === 'view') {
                self.initCalendar( $('#date-picker'));
            }

            if (self.action === 'edit_appointment') {
                self.initCalendar( $('#start_date'));
                self.initCalendar( $('#end_date'));
            }

            /* Submit button */
            $(document).on('click', '#book_appointment', function (e) {
                e.preventDefault();
                self.submit();
            });

            $(document).on('click', '.appointment-time', function (e) {
                e.preventDefault();
                self.selectAppointment(e.target);
            });

            $('#btnalert').on('click', function (e) {
                e.preventDefault();
                self.addAppointment();
            });

            $('#spinner').hide();
        },

        addAppointment: function () {

            if (!$('#contact-id').val()) {
                alert('Please select contact.');
                return;
            }

            if (!$('#appointmentname').val()) {
                alert('Please enter appointment name.');
                return;
            }

            if (!calendar.valueOf().bookingData.start_date) {
                alert('Please select an appointment.');
                return;
            }

            if (!calendar.valueOf().bookingData.end_date) {
                alert('Please select an appointment.');
                return;
            }

            adminAjaxRequest(
                {
                    action: 'groundhogg_add_appointments',
                    start_time: calendar.valueOf().bookingData.start_date,
                    end_time: calendar.valueOf().bookingData.end_date,
                    contact_id: $('#contact-id').val(),
                    notes: $('#notes').val(),
                    calendar_id: $('#calendar_id').val(),
                    appointment_name: $('#appointmentname').val()

                },
                function callback(response) {
                    // Handler

                    if (response.success) {
                        $('#calendar').fullCalendar('renderEvent', response.data.appointment, 'stick');
                        alert(response.data.msg);
                        calendar.clearData();
                        var url_string = window.location.href;
                        var url = new URL(url_string);
                        var c = url.searchParams.get("contact");
                        if(c) {
                            location.replace(response.data.url);
                        }
                    } else {
                        alert(response.data);
                    }
                }
            );
        },

        initCalendar: function (c) {
            var self = this;
            c.datepicker({
                firstDay: self.start_of_week,
                minDate: self.min_date,
                maxDate: self.max_date,
                changeMonth: false,
                changeYear: false,
                dateFormat: 'yy-mm-dd',
                dayNamesMin: self.day_names,
                // constrainInput: true,
                /**
                 *
                 * @param date Date utc 0
                 * @returns {*[]}
                 */
                beforeShowDay: function (date) {
                    if ($.inArray(self.formatDate(date), self.disabled_days) != -1) {
                        return [false, "", "unavailable"];
                    } else {
                        return [true, "", "available"];
                    }
                }
            });

        },

        formatDate: function (date) {
            var d = date,
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-');
        },

        clearData: function () {

            $('#notes').val('');
            $('#appt-calendar').val('');
            $('#appointmentname').val('');
            $('#select_time').children().remove();
            $('#contact-id').val(null).trigger('change');
            calendar.bookingData.start_date = null;
            calendar.bookingData.end_date = null;

        },

        refreshTimeSlots: function () {

            var self = this;
            self.hideTimeSlots();
            self.removeTimeSlots();

            adminAjaxRequest(
                {action: 'groundhogg_get_appointments', date: this.date.val(), calendar: $('#calendar_id').val()},
                function callback(response) {
                    // Handler
                    if (Array.isArray(response.data.slots)) {

                        self.setTimeSlots(response.data.slots);
                        self.showTimeSlots();
                        self.hideErrors();
                    } else {

                        self.setErrors(response.data);
                        self.hideTimeSlots();
                        self.showErrors();
                    }
                },
            );
        },

        showErrors: function () {
            this.hideTimeSlots();
            $('#appointment-errors').removeClass('hidden');
        },

        hideErrors: function () {
            $('#appointment-errors').addClass('hidden');
        },

        setErrors: function (html) {
            $('#appointment-errors').html(html);
        },

        setTimeSlots: function (slots) {
            $.each(slots, function (i, d) {
                $('#select_time').append(
                    '<input type="button" ' +
                    'class="appointment-time" ' +
                    'name="appointment_time" ' +
                    'id="gh_appointment_' + d.start + '" ' +
                    'data-start_date="' + d.start + '" ' +
                    'data-end_date="' + d.end + '" value="' + d.display + '"/>'
                );
            });
        },

        removeTimeSlots: function (slots) {
            $('#select_time').html('');
            //remove data form variable
            this.bookingData = {
                start_date: null,
                end_date: null,
            };
        },

        showTimeSlots: function () {
            $('#appointment-errors').addClass('hidden');
            $('.time-slots').removeClass('hidden');
        },

        hideTimeSlots: function () {
            $('.time-slots').addClass('hidden');
        },

        /**
         * @param e Node
         */
        selectAppointment: function (e) {
            // get data from hidden field
            var $e = $(e);
            this.bookingData = {
                start_date: $e.data('start_date'),
                end_date: $e.data('end_date'),
            };

            /* Remove selected class from all buttons */
            $('.appointment-time').removeClass('selected');
            $e.addClass('selected');
        }
    });

    $(function () {
        calendar.init();
    });
})(jQuery, GroundhoggCalendar);