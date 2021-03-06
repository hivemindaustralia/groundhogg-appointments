<?php

namespace GroundhoggBookingCalendar\Admin\Calendars;

use Exception;
use Groundhogg\Admin\Admin_Page;
use Groundhogg\Email;
use GroundhoggSMS\Classes\SMS;
use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\current_user_is;
use function Groundhogg\get_array_var;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_db;
use function Groundhogg\get_email_templates;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\groundhogg_url;
use GroundhoggBookingCalendar\Classes\Appointment;
use GroundhoggBookingCalendar\Classes\Calendar;
use Groundhogg\Plugin;
use function GroundhoggBookingCalendar\in_between_inclusive;
use function GroundhoggBookingCalendar\is_sms_plugin_active;


// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Class Calendar_Page
 * @package GroundhoggBookingCalendar\Admin\Calendars
 */
class Calendar_Page extends Admin_Page
{

    public function help()
    {
        // TODO: Implement help() method.
    }

    protected function add_additional_actions()
    {
        // TODO: Implement add_additional_actions() method.
    }

    protected function add_ajax_actions()
    {
        add_action('wp_ajax_groundhogg_get_appointments', [$this, 'get_appointments_ajax']);
        add_action('wp_ajax_groundhogg_add_appointments', [$this, 'add_appointment_ajax']);
        add_action('wp_ajax_groundhogg_update_appointments', [$this, 'update_appointment_ajax']);
//        add_action( 'wp_ajax_groundhogg_verify_google_calendar', [ $this, 'verify_code_ajax' ] );


    }

    /**
     * Process AJAX code for fetching appointments.
     */
    public function get_appointments_ajax()
    {

        if (!current_user_can('add_appointment')) {
            wp_send_json_error();
        }

        $ID = absint(get_request_var('calendar'));

        $calendar = new Calendar($ID);

        if (!$calendar->exists()) {
            wp_send_json_error();
        }

        $date = get_request_var('date');

        $slots = $calendar->get_appointment_slots($date);

        if (empty($slots)) {
            wp_send_json_error(__('No slots available.', 'groundhogg-calendar'));
        }

        wp_send_json_success(['slots' => $slots]);
    }


    /**
     * Process AJAX call for adding appointments
     */
    public function add_appointment_ajax()
    {

        if (!current_user_can('add_appointment')) {
            wp_send_json_error();
        }

        $calendar = new Calendar(absint(get_request_var('calendar_id')));
        if (!$calendar->exists()) {
            wp_send_json_error(__('Calendar not found!', 'groundhogg-calendar'));
        }

        $contact = get_contactdata(absint(get_request_var('contact_id')));
        if (!$contact->exists()) {
            wp_send_json_error(__('Contact not found!', 'groundhogg-calendar'));
        }

        $start = absint(get_request_var('start_time'));
        $end = absint(get_request_var('end_time'));

        if (!$start || !$end) {
            wp_send_json_error(__('Please provide a valid date selection.', 'groundhogg-calendar'));
        }


        $appointment = $calendar->schedule_appointment([
            'contact_id' => $contact->get_id(),
            'name' => sanitize_text_field(get_request_var('appointment_name')),
            'start_time' => absint($start),
            'end_time' => absint($end),
            'notes' => sanitize_textarea_field(get_request_var('notes'))
        ]);


        if (!$appointment->exists()) {
            wp_send_json_error(__('Something went wrong while creating appointment.', 'groundhogg-calendar'));
        }

        $response = [
            'appointment' => $appointment->get_full_calendar_event(),
            'msg' => __('Appointment booked successfully.', 'groundhogg-calendar'),
            'url' => admin_page_url('gh_contacts', [
                'action' => 'edit',
                'contact' => $appointment->get_contact_id(),
            ])
        ];

        wp_send_json_success($response);

    }

//    /**
//     * Process AJAX code verification
//     */
//    public function verify_code_ajax()
//    {
//        $calendar_id = absint( get_request_var( 'calendar' ) );
//        $calendar = new Calendar( $calendar_id );
//        $auth_code = get_request_var( 'auth_code' );
//
//        if ( !$auth_code ) {
//            wp_send_json_error( __( 'Please enter a valid code.', 'groundhogg-calendar' ) );
//        }
//
//        $response = Plugin::instance()->proxy_service->request( 'authentication/get', [
//            'code' => $auth_code,
//            'slug' => 'google'
//        ] );
//
//        if ( is_wp_error( $response ) ) {
//            wp_send_json_error( $response->get_error_message() );
//        }
//
//        $access_token = get_array_var( $response, 'token' );
//
//        if ( !$access_token ) {
//            wp_send_json_error( __( 'Could not retrieve access token.', 'groundhogg-calendar' ) );
//        }
//
//        $calendar->update_meta( 'access_token', json_encode( $access_token ) );
//
//        $calendar->add_in_google();
//
//        wp_send_json_success( [ 'msg' => __( 'Your calendar synced successfully!', 'groundhogg-calendar' ) ] );
//
//    }

    /**
     * process AJAX request to update an appointment.
     */
    public function update_appointment_ajax()
    {
        if (!current_user_can('edit_appointment')) {
            wp_send_json_error();
        }

        // Handle update appointment
        $appointment = new Appointment(absint(get_request_var('id')));

        $status = $appointment->reschedule([
            'start_time' => Plugin::$instance->utils->date_time->convert_to_utc_0(strtotime(sanitize_text_field(get_request_var('start_time')))),
            'end_time' => Plugin::$instance->utils->date_time->convert_to_utc_0(strtotime(sanitize_text_field(get_request_var('end_time')))),
        ]);

        if (!$status) {
            wp_send_json_error(__('Something went wrong while updating appointment.', 'groundhogg-calendar'));
        }

        wp_send_json_success(['msg' => __('Your appointment updated successfully!', 'groundhogg-calendar')]);

    }

    public function get_slug()
    {
        return 'gh_calendar';
    }

    public function get_name()
    {
        return _x('Calendars', 'page_title', 'groundhogg-calendar');
    }

    public function get_cap()
    {
        return 'view_appointment';
    }

    public function get_item_type()
    {
        return 'calendar';
    }

    public function get_priority()
    {
        return 48;
    }

    public function get_title_actions()
    {
        if (current_user_is('sales_manager')) {
            return [];
        } else {
            return parent::get_title_actions();
        }
    }


    /**
     * enqueue editor scripts for full calendar
     */
    public function scripts()
    {
        if ($this->get_current_action() === 'edit' || $this->get_current_action() === 'edit_appointment') {
            wp_enqueue_script('groundhogg-appointments-admin');
            $calendar = new Calendar(absint(get_request_var('calendar')));

            if ($this->get_current_action() === 'edit_appointment') {
                $appointment = new Appointment(absint(get_request_var('appointment')));
                $calendar = $appointment->get_calendar();
            }

            wp_localize_script('groundhogg-appointments-admin', 'GroundhoggCalendar', [
                'calendar_id' => absint(get_request_var('calendar')),
                'start_of_week' => get_option('start_of_week'),
                'min_date' => $calendar->get_min_booking_period(true),
                'max_date' => $calendar->get_max_booking_period(true),
                'disabled_days' => $calendar->get_dates_no_slots(),
                'tab' => get_request_var('tab', 'view'),
                'action' => $this->get_current_action()
            ]);
        }

        wp_enqueue_script('fullcalendar-moment');
        wp_enqueue_script('fullcalendar-main');

        // STYLES
        wp_enqueue_style('groundhogg-fullcalendar');
        wp_enqueue_style('groundhogg-calender-admin');
        wp_enqueue_style('jquery-ui');
    }

    public function view()
    {
        if (!class_exists('Calendars_Table')) {
            include dirname(__FILE__) . '/calendars-table.php';
        }

        $calendars_table = new Calendars_Table();
        $this->search_form(__('Search Calendars', 'groundhogg-calendar'));
        $calendars_table->prepare_items();
        $calendars_table->display();
    }

    public function edit()
    {
        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/edit.php';
    }

    public function add()
    {
        if (!current_user_can('add_calendar')) {
            $this->wp_die_no_access();
        }

        include dirname(__FILE__) . '/add.php';
    }

    public function edit_appointment()
    {
        if (!current_user_can('view_appointment')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/../appointments/edit.php';
    }


    public function process_delete()
    {
        if (!current_user_can('delete_calendar')) {
            $this->wp_die_no_access();
        }

        $calendar = new Calendar(get_request_var('calendar'));
        if (!$calendar->exists()) {
            return new \WP_Error('failed', __('Operation failed Calendar not Found.', 'groundhogg-calendar'));
        }

        if ($calendar->delete()) {
            $this->add_notice('success', __('Calendar deleted successfully!'), 'success');
        }
        return true;
    }


    /**
     * Process add calendar and redirect to settings tab on successful calendar creation.
     *
     * @return string|\WP_Error
     */
    public function process_add()
    {

        if (!current_user_can('add_calendar')) {
            $this->wp_die_no_access();
        }

        $name = sanitize_text_field(get_request_var('name'));
        $description = sanitize_textarea_field(get_request_var('description'));

        if ((!$name) || (!$description)) {
            return new \WP_Error('no_data', __('Please enter name and description of calendar.', 'groundhogg-calendar'));
        }

        $owner_id = absint(get_request_var('owner_id', get_current_user_id()));
        $calendar = new Calendar([
            'user_id' => $owner_id,
            'name' => $name,
            'description' => $description,
        ]);

        if (!$calendar->exists()) {
            return new \WP_Error('no_calendar', __('Something went wrong while creating calendar.', 'groundhogg-calendar'));
        }

        /* SET DEFAULTS */

        // max booking period in availability
        $calendar->update_meta('max_booking_period_count', absint(get_request_var('max_booking_period_count', 3)));
        $calendar->update_meta('max_booking_period_type', sanitize_text_field(get_request_var('max_booking_period_type', 'months')));

        //min booking period in availability
        $calendar->update_meta('min_booking_period_count', absint(get_request_var('min_booking_period_count', 0)));
        $calendar->update_meta('min_booking_period_type', sanitize_text_field(get_request_var('min_booking_period_type', 'days')));

        //set default settings
        $calendar->update_meta('slot_hour', 1);
        $calendar->update_meta('message', __('Appointment booked successfully!', 'groundhogg-calendar'));

        // Create default emails...
        $templates = get_email_templates();

        // Booked
        $booked = new Email([
            'title' => $templates['booked']['title'],
            'subject' => $templates['booked']['title'],
            'content' => $templates['booked']['content'],
            'status' => 'ready',
            'from_user' => $owner_id,
        ]);

        $approved = new Email([
            'title' => $templates['approved']['title'],
            'subject' => $templates['approved']['title'],
            'content' => $templates['approved']['content'],
            'status' => 'ready',
            'from_user' => $owner_id,
        ]);

        $cancelled = new Email([
            'title' => $templates['cancelled']['title'],
            'subject' => $templates['cancelled']['title'],
            'content' => $templates['cancelled']['content'],
            'status' => 'ready',
            'from_user' => $owner_id,
        ]);

        $rescheduled = new Email([
            'title' => $templates['rescheduled']['title'],
            'subject' => $templates['rescheduled']['title'],
            'content' => $templates['rescheduled']['content'],
            'status' => 'ready',
            'from_user' => $owner_id,
        ]);

        $reminder = new Email([
            'title' => $templates['reminder']['title'],
            'subject' => $templates['reminder']['title'],
            'content' => $templates['reminder']['content'],
            'status' => 'ready',
            'from_user' => $owner_id,
        ]);

        $calendar->update_meta('emails', [
            'appointment_booked' => $booked->get_id(),
            'appointment_approved' => $approved->get_id(),
            'appointment_rescheduled' => $rescheduled->get_id(),
            'appointment_cancelled' => $cancelled->get_id(),
        ]);

        // set one hour before reminder by default

        $calendar->update_meta('reminders', [
            [
                'when' => 'before',
                'period' => 'hours',
                'number' => 1,
                'email_id' => $reminder->get_id()
            ]
        ]);


        //Create default SMS
        if (is_sms_plugin_active()) {
            $sms_booked = new SMS([
                'title' => __('Appointment Booked', 'groundhogg-calendar'),
                'message' => __("Hey {first},\n\nThank you for booking an appointment.\n\nYour appointment will be from {appointment_start_time} to {appointment_end_time}.\n\nThank you!\n\n@ the {business_name} team", 'groundhogg-calendar'),

            ]);

            $sms_approved = new SMS([
                'title' => __('Appointment Approved', 'groundhogg-calendar'),
                'message' => __("Hey {first},\n\nThank you for booking an appointment with us.\n\nYour appointment booking on {appointment_start_time} has been approved.\n\nThank you!\n\n@ the {business_name} team\n\n", 'groundhogg-calendar'),

            ]);

            $sms_cancelled = new SMS([
                'title' => __('Appointment Cancelled', 'groundhogg-calendar'),
                'message' => __("Hey {first},\n\nYour appointment scheduled on {appointment_start_time} has been cancelled.\n\nYou can always book another appointment using our booking page.\n\nThank you!\n\n@ the {business_name} team", 'groundhogg-calendar'),

            ]);

            $sms_rescheduled = new SMS([
                'title' => __('Appointment Rescheduled', 'groundhogg-calendar'),
                'message' => __("Hey {first},\n\nWe successfully rescheduled your appointment. Your new appointment will be from {appointment_start_time} to {appointment_end_time}.\n\nThank you!\n\n@ the {business_name} team", 'groundhogg-calendar'),

            ]);

            $sms_reminder = new SMS([
                'title' => __('Appointment Reminder', 'groundhogg-calendar'),
                'message' => __("Hey {first},\n\nJust a friendly reminder that you have appointment coming up with us on {appointment_start_time} we look forward to seeing you then.\n\nThank you!\n\n@ the {business_name} team", 'groundhogg-calendar'),
            ]);

            $calendar->update_meta('sms', [
                'appointment_booked' => $sms_booked->get_id(),
                'appointment_approved' => $sms_approved->get_id(),
                'appointment_rescheduled' => $sms_rescheduled->get_id(),
                'appointment_cancelled' => $sms_cancelled->get_id(),
            ]);

            // set one hour before reminder by default

            $calendar->update_meta('sms_reminders', [
                [
                    'when' => 'before',
                    'period' => 'hours',
                    'number' => 1,
                    'sms_id' => $sms_reminder->get_id()
                ]
            ]);
        }

        // update meta data to get set sms
        $this->add_notice('success', __('New calendar created successfully!', 'groundhogg-calendar'), 'success');

        return admin_url('admin.php?page=gh_calendar&action=edit&calendar=' . $calendar->get_id() . '&tab=settings');

    }


    /**
     * Handles button click for syncing calendar.
     *
     * @return bool|string|void|WP_Error
     */
    public function process_google_sync()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }
        $calendar = new Calendar(absint(get_request_var('calendar')));

        $status = \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->sync($calendar->get_id());

        if (is_wp_error($status)) {
            return $status;
        }

        $this->add_notice('success', __('Appointments synced successfully!', 'groundhogg-calendar'), 'success');

        return admin_url('admin.php?page=gh_calendar&action=edit&calendar=' . $calendar->get_id() . '&tab=view');

    }

    /**
     * Process update appointment request post by the edit_appointment page.
     *
     * @return bool|\WP_Error
     */
    public function process_edit_appointment()
    {

        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }

        $appointment_id = absint(get_request_var('appointment'));
        if (!$appointment_id) {
            return new \WP_Error('no_appointment', __('Appointment not found!', 'groundhogg-calendar'));
        }

        $appointment = new Appointment($appointment_id);

        $contact_id = absint(get_request_var('contact_id'));

        if (!$contact_id) {
            return new \WP_Error('no_contact', __('Contact with this appointment not found!', 'groundhogg-calendar'));
        }

        if (!(get_request_var('start_date') === get_request_var('end_date'))) {
            return new \WP_Error('different_date', __('Start date and end date needs to be same.', 'groundhogg-calendar'));
        }

        //check appointment is in working hours.....
        $availability = $appointment->get_calendar()->get_todays_available_periods(get_request_var('start_date'));

        if (empty($availability)) {
            return new \WP_Error('not_available', __('This date is not available.', 'groundhogg-calendar'));
        }

//        $flag = false;
//        foreach ($availability as $appoi) {
//            if (in_between_inclusive(strtotime(get_request_var('start_time')), strtotime($appoi[0]), strtotime($appoi[1])) && in_between_inclusive(strtotime(get_request_var('end_time')), strtotime($appoi[0]), strtotime($appoi[1]))) {
//                $flag = true;
//            }
//        }
//
//        if (!$flag) {
//            return new \WP_Error('not_available', __('Appointment is out of availability.', 'groundhogg-calendar'));
//        }

        $start_time = Plugin::$instance->utils->date_time->convert_to_utc_0(strtotime(get_request_var('start_date') . ' ' . get_request_var('start_time')));
        $end_time = Plugin::$instance->utils->date_time->convert_to_utc_0(strtotime(get_request_var('end_date') . ' ' . get_request_var('end_time')));

        //check for times
        if ($start_time > $end_time) {
            return new \WP_Error('no_contact', __('End time can not be smaller then start time.', 'groundhogg-calendar'));
        }

        /**
         * @var $appointments_table \GroundhoggBookingCalendar\DB\Appointments;
         */
        $appointments_table = get_db('appointments');

        if ($appointments_table->appointments_exist_in_range_except_same_appointment($start_time, $end_time, $appointment->get_calendar_id(), $appointment->get_id())) {
            return new \WP_Error('appointment_clash', __('You already have an appointment in this time slot.', 'groundhogg-calendar'));
        }

        // updates current appointment with the updated details and updates google appointment
        $status = $appointment->reschedule([
            'contact_id' => $contact_id,
            'name' => sanitize_text_field(get_request_var('appointmentname')),
            'start_time' => $start_time,
            'end_time' => $end_time,
            'notes' => sanitize_textarea_field(get_request_var('notes'))
        ]);

        if (!$status) {
            $this->add_notice(new \WP_Error('error', 'Something went wrong...'));
        } else {
            $this->add_notice('success', __("Appointment updated!", 'groundhogg-calendar'), 'success');
        }

        return true;
    }

    /**
     * process approved button click from edit page of appointment.
     *
     * @return string|void|WP_Error
     */
    public function process_approve_appointment()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }

        $appointment_id = get_request_var('appointment');
        if (!$appointment_id) {
            return new \WP_Error('no_appointment_id', __('Appointment ID not found', 'groundhogg-calendar'));
        }
        $appointment = new Appointment($appointment_id);
        if (!$appointment->exists()) {
            wp_die(__("Appointment not found!", 'groundhogg-calendar'));
        }

        $status = $appointment->approve();
        if (!$status) {
            return new \WP_Error('update_failed', __('Status not updated.', 'groundhogg-calendar'));
        } else {
            $this->add_notice('success', __('Appointment status changed!', 'groundhogg-calendar'), 'success');
        }

        return admin_url('admin.php?page=gh_calendar&calendar=' . $appointment->get_calendar_id() . '&action=edit');
    }

    /**
     * Process Cancel button click from appointment edit page.
     *
     * @return string|void|WP_Error
     */
    public function process_cancel_appointment()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }

        $appointment_id = get_request_var('appointment');
        if (!$appointment_id) {
            return new \WP_Error('no_appointment_id', __('Appointment ID not found', 'groundhogg-calendar'));
        }

        $appointment = new Appointment($appointment_id);
        if (!$appointment->exists()) {
            wp_die(__("Appointment not found!", 'groundhogg-calendar'));
        }

        $status = $appointment->cancel();
        if (!$status) {
            return new \WP_Error('update_failed', __('Status not updated.', 'groundhogg-calendar'));
        } else {
            $this->add_notice('success', __('Appointment status changed!', 'groundhogg-calendar'), 'success');
        }
        return admin_url('admin.php?page=gh_calendar&calendar=' . $appointment->get_calendar_id() . '&action=edit');
    }

    /**
     *  Delete appointment from database and google calendar if connected.
     *
     * @return string|\WP_Error
     */
    public function process_delete_appointment()
    {
        if (!current_user_can('delete_appointment')) {
            $this->wp_die_no_access();
        }

        $appointment_id = get_request_var('appointment');
        if (!$appointment_id) {
            return new \WP_Error('no_appointment_id', __('Appointment ID not found', 'groundhogg-calendar'));
        }

        $appointment = new Appointment($appointment_id);
        if (!$appointment->exists()) {
            wp_die(__("Appointment not found!", 'groundhogg-calendar'));
        }

        $calendar_id = $appointment->get_calendar_id();

        $status = $appointment->delete();
        if (!$status) {
            return new \WP_Error('delete_failed', __('Something went wrong while deleting appointment.', 'groundhogg-calendar'));
        } else {
            $this->add_notice('success', __('Appointment deleted!', 'groundhogg-calendar'), 'success');
        }

        return admin_url('admin.php?page=gh_calendar&calendar=' . $calendar_id . '&action=edit&tab=list');
    }

    /**
     * manage tab's post request by calling appropriate function.
     *
     * @return bool
     */
    public function process_edit()
    {
        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }

        $tab = get_request_var('tab', 'view');

        switch ($tab) {

            default:
            case 'view':
                // Update actions from View
                break;
            case 'settings':
                // Update Settings Page
                $this->update_calendar_settings();
                break;
            case 'availability':
                // Update Availability
                $this->update_availability();
                break;
            case 'emails':
                $this->update_emails();
                break;
            case 'sms' :
                $this->update_sms();
                break;
            case 'list':
                break;
        }

        return true;
    }

    protected function update_sms()
    {
        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }

        $calendar = new Calendar(get_request_var('calendar'));


        if (get_request_var('sms_notification')) {
            $calendar->update_meta('sms_notification', true);
        } else {
            $calendar->delete_meta('sms_notification');
        }

        $calendar->update_meta('sms', [
            'appointment_booked' => absint(get_request_var('appointment_booked')),
            'appointment_approved' => absint(get_request_var('appointment_approved')),
            'appointment_rescheduled' => absint(get_request_var('appointment_rescheduled')),
            'appointment_cancelled' => absint(get_request_var('appointment_cancelled'))
        ]);


        $reminders = get_request_var('sms_reminders');

        $operation = get_array_var($reminders, 'when');
        $number = get_array_var($reminders, 'number');
        $period = get_array_var($reminders, 'period');
        $sms_id = get_array_var($reminders, 'sms_id');

        $reminder = [];
        if (empty($operation)) {
            $calendar->update_meta('sms_reminders', '');
        } else {

            foreach ($operation as $i => $op) {
                $temp_reminders = [];
                $temp_reminders['when'] = $operation [$i];
                $temp_reminders['number'] = $number[$i];
                $temp_reminders['period'] = $period[$i];
                $temp_reminders['sms_id'] = $sms_id [$i];
                $reminder[] = $temp_reminders;
            }
            $calendar->update_meta('sms_reminders', $reminder);
        }

    }

    protected function update_emails()
    {

        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }

        $calendar = new Calendar(get_request_var('calendar'));
        $calendar->update_meta('emails', [
            'appointment_booked' => absint(get_request_var('appointment_booked')),
            'appointment_approved' => absint(get_request_var('appointment_approved')),
            'appointment_rescheduled' => absint(get_request_var('appointment_rescheduled')),
            'appointment_cancelled' => absint(get_request_var('appointment_cancelled'))
        ]);


        $reminders = get_request_var('reminders');

        $operation = get_array_var($reminders, 'when');
        $number = get_array_var($reminders, 'number');
        $period = get_array_var($reminders, 'period');
        $email_id = get_array_var($reminders, 'email_id');

        $reminder = [];
        if (empty($operation)) {
            $calendar->update_meta('reminders', '');
        } else {

            foreach ($operation as $i => $op) {
                $temp_reminders = [];
                $temp_reminders['when'] = $operation [$i];
                $temp_reminders['number'] = $number[$i];
                $temp_reminders['period'] = $period[$i];
                $temp_reminders['email_id'] = $email_id [$i];
                $reminder[] = $temp_reminders;
            }
            $calendar->update_meta('reminders', $reminder);
        }
    }


    /**
     * Update calendar availability
     */
    protected function update_availability()
    {

        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }

        $calendar_id = absint(get_request_var('calendar'));

        $calendar = new Calendar($calendar_id);

        $rules = get_request_var('rules');

//        wp_send_json_error( $rules );

        $days = get_array_var($rules, 'day');
        $starts = get_array_var($rules, 'start');
        $ends = get_array_var($rules, 'end');

        $availability = [];

        if (!$days) {
            $this->add_notice(new \WP_Error('error', 'Please add at least one availability slot'));
            return;
        }

        foreach ($days as $i => $day) {

            $temp_rule = [];
            $temp_rule['day'] = $day;
            $temp_rule['start'] = $starts[$i];
            $temp_rule['end'] = $ends[$i];

            $availability[] = $temp_rule;

        }

        $calendar->update_meta('max_booking_period_count', absint(get_request_var('max_booking_period_count', 3)));
        $calendar->update_meta('max_booking_period_type', sanitize_text_field(get_request_var('max_booking_period_type', 'months')));

        $calendar->update_meta('min_booking_period_count', absint(get_request_var('min_booking_period_count', 0)));
        $calendar->update_meta('min_booking_period_type', sanitize_text_field(get_request_var('min_booking_period_type', 'days')));


        $calendar->update_meta('rules', $availability);

        $this->add_notice('updated', __('Availability updated.'));
    }

    /**
     *  Updates the calendar settings.
     */
    protected function update_calendar_settings()
    {

        $calendar_id = absint(get_request_var('calendar'));
        $calendar = new Calendar($calendar_id);

        $args = array(
            'user_id' => absint(get_request_var('owner_id', get_current_user_id())),
            'name' => sanitize_text_field(get_request_var('name', $calendar->get_name())),
            'description' => sanitize_textarea_field(get_request_var('description')),
        );

        if (!$calendar->update($args)) {
            $this->add_notice(new \WP_Error('error', 'Unable to update calendar.'));
            return;
        }

        // Save 12 hour
        if (get_request_var('time_12hour')) {
            $calendar->update_meta('time_12hour', true);
        } else {
            $calendar->delete_meta('time_12hour');
        }

        // Save appointment length
        $calendar->update_meta('slot_hour', absint(get_request_var('slot_hour', 0)));
        $calendar->update_meta('slot_minute', absint(get_request_var('slot_minute', 0)));

        // Save buffer time
        $calendar->update_meta('buffer_time', absint(get_request_var('buffer_time', 0)));

        // Save make me look busy
        $calendar->update_meta('busy_slot', absint(get_request_var('busy_slot', 0)));

        // save success message
        $calendar->update_meta('message', wp_kses_post(get_request_var('message')));

        //save default note
        $calendar->update_meta('default_note', sanitize_textarea_field(get_request_var('default_note')));

        // save thank you page
        $calendar->update_meta('redirect_link_status', absint(get_request_var('redirect_link_status')));
        $calendar->update_meta('redirect_link', esc_url(get_request_var('redirect_link')));

        $form_override = absint(get_request_var('override_form_id', 0));
        $calendar->update_meta('override_form_id', $form_override);

        // save gcal
        $google_calendar_list = get_request_var('google_calendar_list', []);
        $google_calendar_list = array_map('sanitize_text_field', $google_calendar_list);
        $calendar->update_meta('google_calendar_list', $google_calendar_list);

        //save Zoom Meeting settings

        if (get_request_var('zoom_enable')) {
            $calendar->update_meta('zoom_enable', true);
        } else {
            $calendar->delete_meta('zoom_enable');
        }

        $this->add_notice('success', _x('Settings updated.', 'notice', 'groundhogg-calendar'), 'success');
    }

    /**
     * Redirects users to GOOGLE oauth authentication URL with all the details.
     *
     * @return string
     */
    public function process_access_code()
    {
        $return = add_query_arg([
            'page' => 'gh_calendar',
            'action' => 'verify_google_code',
            'calendar' => get_url_var('calendar'),
            '_wpnonce' => wp_create_nonce()
        ], admin_url('admin.php'));

        $url = add_query_arg(['return' => urlencode(base64_encode($return))], 'https://proxy.groundhogg.io/oauth/google/start/');

        return $url;

    }

    /**
     * Redirects users to ZOOM oauth authentication URL with all the details.
     *
     * @return string
     */
    public function process_access_code_zoom()
    {
        $return = add_query_arg([
            'page' => 'gh_calendar',
            'action' => 'verify_zoom_code',
            'calendar' => get_url_var('calendar'),
            '_wpnonce' => wp_create_nonce()
        ], admin_url('admin.php'));

        $url = add_query_arg(['return' => urlencode(base64_encode($return))], 'https://proxy.groundhogg.io/oauth/zoom/start/');

        return $url;
    }


    /**
     * Retrieves authentication code from the response url and creates authentication token for the GOOGLE.
     *
     * @return bool|WP_Error
     */
    public function process_verify_google_code()
    {

        if (!get_request_var('code')) {
            return new \WP_Error('no_code', __('Authentication code not found!', 'groundhogg-calendar'));
        }

        $auth_code = get_request_var('code');
        $calendar_id = absint(get_request_var('calendar'));
        $calendar = new Calendar($calendar_id);

        $response = Plugin::instance()->proxy_service->request('authentication/get', [
            'code' => $auth_code,
            'slug' => 'google'
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('failed', $response->get_error_message());
        }

        $access_token = get_array_var($response, 'token');

        if (!$access_token) {
            return new \WP_Error('failed', $response->get_error_message());
        }

        $calendar->update_meta('access_token', json_encode($access_token));

        $calendar->add_in_google();

        $this->add_notice('success', __('Connection to Google calendar successfully completed!', 'groundhogg-calendar'), 'success');

        return admin_page_url( 'gh_calendar', [ 'action' => 'edit', 'calendar' => $calendar_id, 'tab' => 'settings' ] );
    }


    /**
     * Retrieves authentication code from the response url and creates authentication token for the ZOOM.
     *
     * @return bool|WP_Error
     */
    public function process_verify_zoom_code()
    {

        if (!get_request_var('code')) {
            return new \WP_Error('no_code', __('Authentication code not found!', 'groundhogg-calendar'));
        }

        $auth_code = get_request_var('code');
        $calendar_id = absint(get_request_var('calendar'));
        $calendar = new Calendar($calendar_id);

        $response = Plugin::instance()->proxy_service->request('authentication/get', [
            'code' => $auth_code,
            'slug' => 'zoom'
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('failed', $response->get_error_message());
        }

        $access_token = get_array_var($response, 'token');

        if (!$access_token) {
            return new \WP_Error('failed', $response->get_error_message());
        }

        $calendar->update_meta('access_token_zoom', json_encode($access_token));

        $this->add_notice('success', __('Connection to zoom successfully completed!', 'groundhogg-calendar'), 'success');

        return admin_page_url( 'gh_calendar', [ 'action' => 'edit', 'calendar' => $calendar_id, 'tab' => 'settings' ] );
    }
}