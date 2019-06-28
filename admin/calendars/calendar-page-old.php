<?php
namespace GroundhoggBookingCalendar\Admin\Calendars;
use Groundhogg\Admin\Admin_Page;
use function Groundhogg\get_request_var;
use GroundhoggBookingCalendar\Admin\Appointments;
use GroundhoggBookingCalendar\Classes\Calendar;

    // Exit if accessed directly
    if (!defined('ABSPATH')) exit;

    /**
     * Class Calendar_Page
     * @package GroundhoggBookingCalendar\Admin\Calendars
     */
    class Calendar_Pagell extends Admin_Page
    {
    protected function add_ajax_actions()
    {
        add_action('wp_ajax_gh_add_appointment', array($this, 'gh_add_appointment'));
        add_action('wp_ajax_gh_update_appointment', array($this, 'gh_update_appointment'));
    //        add_action( 'wp_ajax_gh_verify_code', array( WPGH_APPOINTMENTS()->google_calendar , 'gh_verify_code')); //todo

    }

    protected function add_additional_actions()
    {
        // TODO: Implement add_additional_actions() method.
    }

    public function get_slug()
    {
        return 'gh_calendar';
    }

    public function get_name()
    {
        return _x('Calendars', 'page_title', 'groundhogg');
    }

    public function get_cap()
    {
        return 'view_calendar';
    }

    public function get_item_type()
    {
        return 'calendar';
    }

    public function get_priority()
    {
        return 48;
    }

    /**
     * Help bar
     */
    public function help()
    {
        //todo
    }


    /**
     * enqueue editor scripts for full calendar
     */
    public function scripts()
    {
        wp_enqueue_script('groundhogg-appointments');
        wp_enqueue_script('groundhogg-appointments-shortcode');
        wp_enqueue_script('fullcalendar-moment');
        wp_enqueue_script('fullcalendar-main');


        wp_enqueue_style('groundhogg-fullcalendar');
        wp_enqueue_style('groundhogg-calender-admin');
    }



    public function process_add()
    {
        if (!current_user_can('add_calendar')) {
            $this->wp_die_no_access();
        }
        if (isset($_POST['add'])) {
            $this->add_calendar();
        }
    }

    public function process_edit()
    {

        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }

        $tab = get_request_var('tab', 'view');

        switch ($tab):
            default:
            case 'view':
                // Update actions from View


                ?>
                <script>alert('view');</script>
                <?php


                break;
            case 'settings':
                // Update Settings Page
                $this->update_calendar_settings();
                break;
            case 'availability':

                break;
            case 'list':


                ?>
                <script>alert('list');</script>
                <?php

                break;
        endswitch;
        return true;
    }

    /**
     * Handles post request of updating calendar details.
     *
     */
    //protected function update_calendar_settings()
    //{
    //
    //    $calendar_id = absint(get_request_var('calendar'));
    //
    //    if (!$calendar_id) {
    //        return new \WP_Error('no_calendar_id', __('Calendar id not found.', 'groundhogg'));
    //    }
    //
    //    if (!get_request_var('owner_id') || get_request_var('owner_id') == 0) {
    //        return new \WP_Error('no_owner', __('Please select a valid user.', 'groundhogg'));
    //    }
    //
    //    if (!get_request_var('name') || get_request_var('name') === '') {
    //        return new \WP_Error('no_name', __("Please enter name of calendar.", 'groundhogg'));
    //    }
    //
    //    $calendar = new Calendar($calendar_id);
    //
    //    $args = array(
    //        'user_id' => absint(get_request_var('owner_id', get_current_user_id())),
    //        'name' => sanitize_text_field(get_request_var('name')),
    //        'description' => sanitize_textarea_field(get_request_var('description')),
    //    );
    //
    //    if (!$calendar->update($args)) {
    //        $this->add_notice(new \WP_Error('error', 'Unable to update calendar.'));
    //        return;
    //    }
    //
    //    //update meta
    //
    //    //Update calendar list
    //
    //    $google_calendar_list = (array)$_POST['google_calendar_list'];
    //    $google_calendar_list = array_map('sanitize_text_field', $google_calendar_list);
    //
    //    $calendar->update_meta('google_calendar_list', $google_calendar_list);
    //
    //    // load data from the from or database
    //
    //    if (isset($_POST['time_12hour'])) {
    //        $time_12hour = $_POST['time_12hour'];
    //
    //    } else {
    //        $time_12hour = "0";
    //    }
    //    $calendar->update_meta('time_12hour', sanitize_text_field(stripslashes($time_12hour)));
    //
    //    if (isset($_POST['custom_text_status'])) {
    //        $custom_text_status = $_POST['custom_text_status'];
    //
    //    } else {
    //        $custom_text_status = "0";
    //    }
    //    $calendar->update_meta('custom_text_status', sanitize_text_field(stripslashes($custom_text_status)));
    //
    //    $custom_text = sanitize_text_field(stripslashes($_POST['custom_text']));
    //    if ($custom_text != '') {
    //        $calendar->update_meta('custom_text', $custom_text);
    //    }
    //
    //    if (isset($_POST['redirect_link_status'])) {
    //        $redirect_link_status = $_POST['redirect_link_status'];
    //
    //    } else {
    //        $redirect_link_status = "0";
    //    }
    //    $calendar->update_meta('redirect_link_status', sanitize_text_field(stripslashes($redirect_link_status)));
    //
    //    $redirect_link = sanitize_text_field(stripslashes($_POST['redirect_link']));
    //    if ($redirect_link != '') {
    //        $calendar->update_meta('redirect_link', $redirect_link);
    //    }
    //
    //    // appointment
    //    $hour = intval($_POST['slot_hour']);
    //    $min = intval($_POST['slot_minute']);
    //    // Enter time slot info
    //    if ($min == 0 && $hour == 0) {
    //        $hour = 1;
    //        $min = 0;
    //    }
    //    if ($hour == 0 && ($min < 5)) {
    //        $min = 5;
    //    }
    //    // update meta
    //    $calendar->update_meta('slot_hour', $hour);
    //    $calendar->update_meta('slot_minute', $min);
    //
    //    $buffer_time = intval($_POST['buffer_time']);
    //    $calendar->update_meta('buffer_time', $buffer_time);
    //
    //    $busy_slot = intval($_POST['busy_slot']);
    //    $calendar->update_meta('busy_slot', $busy_slot);
    //
    //    $message = wp_kses_post(stripslashes($_POST['message']));
    //    if ($message != '') {
    //        $calendar->update_meta('message', $message);
    //    }
    //
    //    $title = sanitize_text_field(stripslashes($_POST['slot_title']));
    //    if ($title != '') {
    //        $calendar->update_meta('slot_title', $title);
    //    }
    //
    //    $main_color = sanitize_text_field(stripslashes($_POST['main_color']));
    //    if ($main_color) {
    //        $calendar->update_meta('main_color', $main_color);
    //    }
    //
    //    $slots_color = sanitize_text_field(stripslashes($_POST['slots_color']));
    //    if ($slots_color) {
    //        $calendar->update_meta('slots_color', $slots_color);
    //    }
    //
    //    $font_color = sanitize_text_field(stripslashes($_POST['font_color']));
    //    if ($font_color) {
    //        $calendar->update_meta('font_color', $font_color);
    //    }
    //    $this->add_notice('success', _x('Settings updated.', 'notice', 'groundhogg'), 'success');
    //
    //}

    public function process_view_appointment()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }
        if (isset($_POST['update_appointment'])) {
            $this->update_appointment();
        }
    }

    public function process_delete()
    {
        if (!current_user_can('delete_calendar')) {
            $this->wp_die_no_access();
        }
        $this->delete_calendar();
    }

    public function process_access_code()
    {
        $client = WPGH_APPOINTMENTS()->google_calendar->get_basic_client();
        if (is_wp_error($client)) {
            $this->notices->add('CLIENT_ERROR', __('Please check your google clientId and Secret.'), 'error');
            return;
        }
        $authUrl = $client->createAuthUrl();
        echo "<script>window.open(\"" . $authUrl . "\",\"_self\");</script>";

    }

    public function process_approve()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }
        // manage operation of appointment
        if (isset($_GET['appointment'])) {
            $appointment_id = intval($_GET['appointment']);
            //get appointment
            $appointment = $this->db->get($appointment_id);
            if ($appointment == null) {
                $this->notices->add('NO_APPOINTMENT', __("Appointment not found!", 'groundhogg'), 'error');
                return;
            }
            //update status
            $status = $this->db->update($appointment_id, array('status' => 'approved'));
            if (!$status) {
                wp_die('Something went wrong');
            }
            do_action('gh_calendar_appointment_approved', $appointment_id, 'approved');
            $this->notices->add('success', __('Appointment updated successfully!', 'groundhogg'), 'success');
            wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $appointment->calendar_id));
            die();
        }
    }
    //
    //public function process_delete_appointment()
    //{
    //    if (!current_user_can('edit_appointment')) {
    //        $this->wp_die_no_access();
    //    }
    //
    //    if (isset($_GET['appointment'])) {
    //        $appointment_id = intval($_GET['appointment']);
    //        $appointment = $this->db->get($appointment_id);
    //        $status = $this->delete_appointment($appointment_id);
    //        if ($status && !is_wp_error($status)) {
    //            $this->notices->add('SUCCESS', __('Appointment deleted successfully!', 'groundhogg'), 'success');
    //        } else {
    //            $this->notices->add($status->get_error_code(), $status->get_error_message(), 'error');
    //        }
    //        wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $appointment->calendar_id));
    //        die();
    //    }
    //}

    public function process_google_sync()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }
        if (isset($_GET['calendar'])) {
            $calendar_id = intval($_GET['calendar']);
            $result = WPGH_APPOINTMENTS()->google_calendar->sync($calendar_id);
            if ($result && !is_wp_error($result)) {
                $this->notices->add('SUCCESS', __('Appointments synced successfully!', 'groundhogg'), 'success');
                wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $calendar_id));
            } else {
                $this->notices->add($result->get_error_code(), $result->get_error_message(), 'error');
                wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $calendar_id));
            }
            die();
        }
    }

    public function process_cancel()
    {
        if (!current_user_can('edit_appointment')) {
            $this->wp_die_no_access();
        }
        if (isset($_GET['appointment'])) {
            $appointment_id = intval($_GET['appointment']);
            //get appointment
            $appointment = $this->db->get($appointment_id);
            if ($appointment == null) {
                $this->notices->add('NO_APPOINTMENT', __("Appointment not found!", 'groundhogg'), 'error');
                return;
            }

            //update status
            $status = $this->db->update($appointment_id, array('status' => 'cancelled'));
            if (!$status) {
                wp_die('Something went wrong');
            }

            do_action('gh_calendar_appointment_cancelled', $appointment_id, 'cancelled');
            $this->notices->add('success', __('Appointment updated successfully!', 'groundhogg'), 'success');
            wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $appointment->calendar_id));
            die();
        }
    }

    /**
     * Display the scheduling page
     */
    public function add()
    {
        if (!current_user_can('add_calendar')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/add-calendar.php';
    }

    /**
     * Display the Edit calendar page
     */
    public function edit()
    {
        if (!current_user_can('edit_calendar')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/edit.php';
    }

    /**
     * Display the screen content
     */
    public function view()
    {
        if (!class_exists('Calendars_Table')) {
            include dirname(__FILE__) . '/calendars-table.php';
        }
        $calendars_table = new Calendars_Table();
        $this->search_form(__('Search Calendars', 'groundhogg'));

        $calendars_table->prepare_items();
        $calendars_table->display();
    }

    /**
     * Handles Delete calendar request form list of calendar table
     */
    private function delete_calendar()
    {
        //get the ID of the calendar
        if (isset($_GET['calendar'])) {
            $calendar = intval($_GET['calendar']);
        }
        if (!isset($calendar)) {
            wp_die(__("Please select a calendar to delete.", 'groundhogg'));
        }
        WPGH_APPOINTMENTS()->calendar->delete($calendar);
    }

    /**
     * Schedule a new calendar.
     * handle post request form add calendar
     */
    private function add_calendar()
    {
        // add calendar operation
        if (!isset($_POST['owner_id']) || $_POST['owner_id'] == 0) {
            $this->notices->add('NO_OWNER', __("Please select a valid user.", 'groundhogg'), 'error');
            return;
        }
        if (!isset($_POST['name']) || $_POST['name'] == '') {
            $this->notices->add('NO_NAME', __("Please enter name of calendar.", 'groundhogg'), 'error');
            return;
        }
        // ADD CALENDAR in DATABASE
        $args = array(
            'user_id' => intval($_POST['owner_id']),
            'name' => sanitize_text_field(stripslashes($_POST['name'])),
        );
        if (isset($_POST['description'])) {
            $args['description'] = sanitize_text_field(stripslashes($_POST['description']));
        }
        // ADD OPERATION
        $calendar_id = WPGH_APPOINTMENTS()->calendar->add($args);
        //META OPERATION
        if (!$calendar_id) {
            wp_die(__('Something went wrong', 'groundhogg'));
        }
        // Enter metadata of calendar
        // days
        if (isset($_POST['checkbox'])) {
            $checkbox = (array)$_POST['checkbox'];
            $checkbox = array_map('sanitize_text_field', $checkbox);
            WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'dow', $checkbox);
        }

        // start time
        if (isset($_POST['starttime'])) {
            if (isset($_POST['endtime'])) {
                $end_time = $_POST['endtime'];
            } else {
                $end_time = WPGH_APPOINTMENTS()->calendarmeta->get_meta($calendar_id, 'end_time', true);
            }

            if (strtotime($end_time) < strtotime($_POST['starttime'])) {
                $this->notices->add('INVALID_STARTTIME', __("End time can not be smaller then start time.", 'groundhogg'), 'error');
            } else {
                WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'start_time', sanitize_text_field(stripslashes($_POST['starttime'])));
            }
        }
        //end time
        if (isset($_POST['endtime'])) {

            if (isset($_POST['starttime'])) {
                $start_time = $_POST['starttime'];
            } else {
                $start_time = WPGH_APPOINTMENTS()->calendarmeta->get_meta($calendar_id, 'start_time', true);
            }
            if (strtotime($start_time) < strtotime($_POST['endtime'])) {
                WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'end_time', sanitize_text_field(stripslashes($_POST['endtime'])));
            } else {
                $this->notices->add('INVALID_STARTTIME', __("End time can not be smaller then start time.", 'groundhogg'), 'error');
            }
        }


        $slot1_start = $_POST['slot1_start_time'];
        $slot1_end = $_POST['slot1_end_time'];

        $slot2_start = $_POST['slot2_start_time'];
        $slot2_end = $_POST['slot2_end_time'];

        $slot3_start = $_POST['slot3_start_time'];
        $slot3_end = $_POST['slot3_end_time'];


        if (isset($_POST['slot2_status'])) {
            $slot2_check = $_POST['slot2_status'];

        } else {
            $slot2_check = "0";
        }

        if (isset($_POST['slot3_status'])) {
            $slot3_check = $_POST['slot3_status'];
        } else {
            $slot3_check = "0";
        }


        if ($slot2_check) {
            if (!(strtotime($slot1_end) <= strtotime($slot2_start))) {
                $this->notices->add('INVALID_STARTTIME', __("Slot1 end time needs to be smaller then slot2  start time .", 'groundhogg'), 'error');
                return;
            }

            if (!(strtotime($slot2_start) < strtotime($slot2_end))) {
                $this->notices->add('INVALID_STARTTIME', __("Start time needs to be smaller then end time.", 'groundhogg'), 'error');
                return;
            }
        }


        if ($slot3_check) {
            if (!(strtotime($slot1_end) <= strtotime($slot3_start))) {
                $this->notices->add('INVALID_STARTTIME', __("Slot1 end time needs to be smaller then slot3  start time.", 'groundhogg'), 'error');
                return;
            }

            if ($slot2_check) {
                if (!(strtotime($slot2_end) <= strtotime($slot3_start))) {
                    $this->notices->add('INVALID_STARTTIME', __("Slot2 end time needs to be smaller then slot3  start time.", 'groundhogg'), 'error');
                    return;
                }
            }

            if (!(strtotime($slot3_start) < strtotime($slot3_end))) {
                $this->notices->add('INVALID_STARTTIME', __("Start time needs to be smaller then end time.", 'groundhogg'), 'error');
                return;
            }
        }

        if (!(strtotime($slot1_start) < strtotime($slot1_end))) {
            $this->notices->add('INVALID_STARTTIME', __("Start time needs to be smaller then end time.", 'groundhogg'), 'error');
            return;
        }


        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot1_start_time', sanitize_text_field(stripslashes($slot1_start)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot1_end_time', sanitize_text_field(stripslashes($slot1_end)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot2_start_time', sanitize_text_field(stripslashes($slot2_start)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot2_end_time', sanitize_text_field(stripslashes($slot2_end)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot3_start_time', sanitize_text_field(stripslashes($slot3_start)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot3_end_time', sanitize_text_field(stripslashes($slot3_end)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot2_status', sanitize_text_field(stripslashes($slot2_check)));
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot3_status', sanitize_text_field(stripslashes($slot3_check)));

        if (isset($_POST['time_12hour'])) {
            $time_12hour = $_POST['time_12hour'];

        } else {
            $time_12hour = "0";
        }
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'time_12hour', sanitize_text_field(stripslashes($time_12hour)));


        if (isset($_POST['custom_text_status'])) {
            $custom_text_status = $_POST['custom_text_status'];

        } else {
            $custom_text_status = "0";
        }
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'custom_text_status', sanitize_text_field(stripslashes($custom_text_status)));

        $custom_text = sanitize_text_field(stripslashes($_POST['custom_text']));
        if ($custom_text != '') {
            WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'custom_text', $custom_text);
        }

        if (isset($_POST['redirect_link_status'])) {
            $redirect_link_status = $_POST['redirect_link_status'];

        } else {
            $redirect_link_status = "0";
        }
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'redirect_link_status', sanitize_text_field(stripslashes($redirect_link_status)));

        if (isset($_POST['redirect_link'])) {

            $redirect_link = sanitize_text_field(stripslashes($_POST['redirect_link']));
            if ($redirect_link != '') {
                WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'redirect_link', $redirect_link);
            }

        }


        $hour = intval($_POST['slot_hour']);
        $min = intval($_POST['slot_minute']);
        // Enter time slot info
        if ($min == 0 && $hour == 0) {
            $hour = 1;
            $min = 0;
        }
        if ($hour == 0 && ($min < 5)) {
            $min = 5;
        }
        // add meta
        WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'slot_hour', $hour);
        WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'slot_minute', $min);
        // add custom message
        $message = wp_kses_post($_POST['message']);
        if ($message == '') {
            $message = 'Appointment booked successfully';
        }
        WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'message', $message);
        $title = sanitize_text_field(stripslashes($_POST['slot_title']));
        if ($title == '') {
            $title = 'Time Slot';
        }
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'slot_title', $title);

        $main_color = sanitize_text_field(stripslashes($_POST['main_color']));
        if ($main_color) {
            WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'main_color', $main_color);
        }

        $slots_color = sanitize_text_field(stripslashes($_POST['slots_color']));
        if ($slots_color) {
            WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'slots_color', $slots_color);
        }

        $font_color = sanitize_text_field(stripslashes($_POST['font_color']));
        if ($font_color) {
            WPGH_APPOINTMENTS()->calendarmeta->add_meta($calendar_id, 'font_color', $font_color);
        }
        $buffer_time = intval($_POST['buffer_time']);
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'buffer_time', $buffer_time);

        $busy_slot = intval($_POST['busy_slot']);
        WPGH_APPOINTMENTS()->calendarmeta->update_meta($calendar_id, 'busy_slot', $busy_slot);

        $this->notices->add('success', __('New calendar added!', 'groundhogg'), 'success'); // not working
        wp_redirect(admin_url('admin.php?page=gh_calendar&action=edit&calendar=' . $calendar_id));
        die();
    }

    /**
     * Display the reporting page
     */
    public function view_appointment()
    {
        if (!current_user_can('view_appointment')) {
            $this->wp_die_no_access();
        }
        include dirname(__FILE__) . '/../appointments/edit.php';
    }

    /**
     * Delete appointment form google calendar and Groundhogg calendar
     *
     * @param $appointment_id
     * @return bool|void
     */
    public function delete_appointment($appointment_id)
    {
        if (!current_user_can('delete_appointment')) {
            $this->wp_die_no_access();
        }

        $appointment_id = get_request_var('appointment');

        if (isset($_GET['appointment'])) {
            $appointment_id = intval($_GET['appointment']);
            $appointment = $this->db->get($appointment_id);
            $status = $this->delete_appointment($appointment_id);
            if ($status && !is_wp_error($status)) {
                $this->notices->add('SUCCESS', __('Appointment deleted successfully!', 'groundhogg'), 'success');
            } else {
                $this->notices->add($status->get_error_code(), $status->get_error_message(), 'error');
            }
            wp_redirect(admin_url('admin.php?page=gh_calendar&action=add_appointment&calendar=' . $appointment->calendar_id));
            die();
        }


        $appointment_id = intval($appointment_id);
        //get appointment
        $appointment = $this->db->get($appointment_id);
        if (!$appointment) {
            new WP_Error ('NO_APPOINTMENT', __("Appointment not found!", 'groundhogg'));
            return;
        }

        //DELETE appointment form the google if there are any ..
        WPGH_APPOINTMENTS()->google_calendar->delete_appointment_from_google($appointment_id);
        do_action('gh_calendar_appointment_deleted', $appointment_id, 'deleted');
        $status = $this->db->delete($appointment_id);
        if (!$status) {
            new WP_Error('ERROR', __('Something went wrong! ', 'groundhogg'));
        }
        return true;

    }

    /**
     *  Display add appointment page
     */
    public function add_appointment()
    {
        include dirname(__FILE__) . '/edit.php';
    }

    /**
     * Handles post request form the view appointment to update appointment.
     */
    private function update_appointment()
    {
        if (!isset($_POST['contact_id']) || $_POST['contact_id'] == 0) {
            $this->notices->add('NO_CONTACT', __("Please select a valid contact", 'groundhogg'), 'error');
            return;
        }

        if (!isset($_POST['appointment']) || $_POST['appointment'] == 0) {
            $this->notices->add('NO_APPOINTMENT', __("Please select a valid appointment.", 'groundhogg'), 'error');
            return;
        }

        if (!isset($_POST['calendar']) || $_POST['calendar'] == 0) {
            $this->notices->add('NO_CALENDAR', __("Please select a valid calendar.", 'groundhogg'), 'error');
            return;
        }

        $contact_id = intval($_POST['contact_id']);
        $appointment_id = intval($_POST['appointment']);
        $calendar_id = intval($_POST['calendar']);
        $start_time = strtotime($_POST['start_date'] . ' ' . $_POST['start_time']);
        $end_time = strtotime($_POST['end_date'] . ' ' . $_POST['end_time']);
        if ($start_time > $end_time) {
            //check for times
            $this->notices->add('INVALID_TIMES', __("End time can not be earlier then start time.", 'groundhogg'), 'error');
            return;
        }

        //check for appointment clash.
        global $wpdb;
        $appointments_table_name = WPGH_APPOINTMENTS()->appointments->table_name;
        $appointments = $wpdb->get_results("SELECT * FROM $appointments_table_name as a WHERE a.start_time >= $start_time AND a.end_time <=  $end_time AND a.calendar_id = $calendar_id AND a.ID != $appointment_id ");
        if (count($appointments) > 0) {
            $this->notices->add('APPOINTMENT_CLASH', __("You already have appointment in this time slot.", 'groundhogg'), 'error');
            return;
        }

        $all_appoinments = $this->db->get_appointments();
        foreach ($all_appoinments as $appo) {
            if ((($start_time >= $appo->start_time && $start_time < $appo->end_time) || ($end_time >= $appo->start_time && $end_time < $appo->end_time)) && $appo->ID != $appointment_id) {
                $this->notices->add('APPOINTMENT_CLASH', __("You already have appointment in this time slot.", 'groundhogg'), 'error');
                return;
            }
        }

        // update query
        $status = $this->db->update($appointment_id, array(
            'contact_id' => $contact_id,
            'name' => sanitize_text_field(stripslashes($_POST['appointmentname'])),
            'start_time' => wpgh_convert_to_utc_0($start_time),
            'end_time' => wpgh_convert_to_utc_0($end_time)
        ));
        //update notes
        if (isset($_POST['description'])) {

            $this->meta->update_meta($appointment_id, 'note', sanitize_text_field(stripslashes($_POST['description'])));
        }

        // Add start and end date to contact meta
        WPGH()->contact_meta->update_meta($contact_id, 'appointment_start', date('Y-m-d', wpgh_convert_to_utc_0($start_time)));
        WPGH()->contact_meta->update_meta($contact_id, 'appointment_end', date('Y-m-d', wpgh_convert_to_utc_0($end_time)));

        if ($status) {
            //update google calendar  ..
            $appointment = $this->db->get_appointment($appointment_id);
            $access_token = WPGH_APPOINTMENTS()->calendarmeta->get_meta($appointment->calendar_id, 'access_token', true);
            $google_calendar_id = WPGH_APPOINTMENTS()->calendarmeta->get_meta($appointment->calendar_id, 'google_calendar_id', true);
            if ($access_token && $google_calendar_id) {
                $client = WPGH_APPOINTMENTS()->google_calendar->get_google_client_form_access_token($appointment->calendar_id);
                $service = new Google_Service_Calendar($client);
                if (WPGH_APPOINTMENTS()->google_calendar->is_valid_calendar($calendar_id, $google_calendar_id, $service)) {
                    $contact = WPGH()->contacts->get($appointment->contact_id);
                    $event = new Google_Service_Calendar_Event(array(
                        'id' => 'ghcalendarcid' . $appointment->calendar_id . 'aid' . $appointment->ID,
                        'summary' => $appointment->name,
                        'description' => $this->meta->get_meta($appointment->ID, 'note', true),
    //                        'start' => WPGH_APPOINTMENTS()->google_calendar->get_google_time($appointment->start_time),
    //                        'end' => WPGH_APPOINTMENTS()->google_calendar->get_google_time( $appointment->end_time ),
                        'start' => ['dateTime' => date('Y-m-d\TH:i:s', $appointment->start_time) . 'Z'],
                        'end' => ['dateTime' => date('Y-m-d\TH:i:s', $appointment->end_time) . 'Z'],

                        'attendees' => array(
                            array('email' => $contact->email),
                        ),
                    ));
                    $updatedEvent = $service->events->update($google_calendar_id, 'ghcalendarcid' . $appointment->calendar_id . 'aid' . $appointment_id, $event);
                }
            }
            $this->notices->add('success', __('Appointment updated successfully !', 'groundhogg'), 'success');
        } else {
            $this->notices->add('UPDATE_FAILED', __("Something went wrong while update.", 'groundhogg'), 'error');
        }
        return;
    }







    /**
     * AJAX call to update appointments from admin add appointment section
     *
     * Requested By AJAX
     */
    public function gh_update_appointment()
    {

        if (!current_user_can('edit_appointment')) {
            $response = array('status' => 'failed', 'msg' => __('Your user role does not have the required permissions to Edit appointment.', 'groundhogg'));
            wp_die(json_encode($response));
        }

        // Handle update appointment
        $appointment_id = intval($_POST['id']);
        $start_time = strtotime('+1 seconds', strtotime(sanitize_text_field(stripslashes($_POST['start_time']))));
        $end_time = strtotime(sanitize_text_field(stripslashes($_POST['end_time'])));


        // update appointment detail
        $status = $this->db->update($appointment_id, array(
            'start_time' => $start_time,
            'end_time' => $end_time,
        ));

        $appointment = $this->db->get_appointment($appointment_id);

        // Add start and end date to contact meta
        WPGH()->contact_meta->update_meta($appointment->contact_id, 'appointment_start', date('Y-m-d', $start_time));
        WPGH()->contact_meta->update_meta($appointment->contact_id, 'appointment_end', date('Y-m-d', $end_time));


        if ($status) {
            do_action('gh_calendar_update_appointment_admin', $appointment_id, 'reschedule_admin');
            //update appointment on google
            // retrieve appointment and update its detail on google calendar.
            $access_token = WPGH_APPOINTMENTS()->calendarmeta->get_meta($appointment->calendar_id, 'access_token', true);
            $google_calendar_id = WPGH_APPOINTMENTS()->calendarmeta->get_meta($appointment->calendar_id, 'google_calendar_id', true);
            if ($access_token && $google_calendar_id) {
                $client = WPGH_APPOINTMENTS()->google_calendar->get_google_client_form_access_token($appointment->calendar_id);
                $service = new Google_Service_Calendar($client);
                if (WPGH_APPOINTMENTS()->google_calendar->is_valid_calendar($appointment->calendar_id, $google_calendar_id, $service)) {
                    $event = new Google_Service_Calendar_Event(array(
                        'start' => WPGH_APPOINTMENTS()->google_calendar->get_google_time($appointment->start_time),
                        'end' => WPGH_APPOINTMENTS()->google_calendar->get_google_time($appointment->end_time),
                    ));
                    $updatedEvent = $service->events->patch($google_calendar_id, 'ghcalendarcid' . $appointment->calendar_id . 'aid' . $appointment_id, $event);
                }
            }
            wp_die(json_encode(array(
                'status' => 'success',
                'msg' => __('Appointment reschedule successfully.', 'groundhogg')
            )));
        } else {
            wp_die(json_encode(array(
                'status' => 'failed',
                'msg' => __('Something went wrong !', 'groundhogg')
            )));
        }
    }

    /**
     * AJAX  call to add appointments from admin section
     *
     * Requested by AJAX
     */
    public function gh_add_appointment()
    {

        if (!current_user_can('add_appointment')) {
            $response = array('msg' => __('Your user role does not have the required permissions to add appointment.', 'groundhogg'));
            wp_die(json_encode($response));
        }

        // ADD APPOINTMENTS using AJAX.
        $start = intval($_POST['start_time']);
        $end = intval($_POST['end_time']);
        if (!$start || !$end) {
            $response = array('status' => 'failed', 'msg' => __('PLease provide a valid date selection.', 'groundhogg'));
            wp_die(json_encode($response));
        }


        $contact_id = intval($_POST['id']);
        $note = sanitize_text_field(stripslashes($_POST['note']));
        $appointment_name = sanitize_text_field(stripslashes($_POST ['appointment_name']));
        $calendar_id = sanitize_text_field(stripslashes($_POST ['calendar_id']));
        $start = strtotime('+1 seconds', $start);
        //end minus buffer time
        $buffer_time = intval(WPGH_APPOINTMENTS()->calendarmeta->get_meta($calendar_id, 'buffer_time', true));
        $end = strtotime("- $buffer_time minute", $end);

        // perform insert operation
        $appointment_id = $this->db->add(array(
            'contact_id' => $contact_id,
            'calendar_id' => $calendar_id,
            'name' => $appointment_name,
            'status' => 'pending',
            'start_time' => $start,    //strtotime()
            'end_time' => $end      //strtotime()
        ));

        // Insert meta
        if ($appointment_id === false) {
            $response = array('msg' => __('Something went wrong. Appointment not created !', 'groundhogg'));
            wp_die(json_encode($response));
        }

        // Add start and end date to contact meta
        WPGH()->contact_meta->update_meta($contact_id, 'appointment_start', date('Y-m-d', $start));
        WPGH()->contact_meta->update_meta($contact_id, 'appointment_end', date('Y-m-d', $end));


        if ($note != '') {
            WPGH_APPOINTMENTS()->appointmentmeta->add_meta($appointment_id, 'note', $note);
        }

        // generate array to create event for full calendar to display
        $response = array(
            'msg' => 'Appointment booked successfully.',
            'appointment' => array(
                'id' => $appointment_id,
                'title' => $appointment_name,
                'start' => convert_to_local_time($start) * 1000,//$start,
                'end' => convert_to_local_time($end) * 1000,//$end,
                'constraint' => 'businessHours',
                'editable' => true,
                'allDay' => false,
                'color' => '#0073aa',
                'url' => admin_url('admin.php?page=gh_calendar&action=view_appointment&appointment=' . $appointment_id),// link to view appointment page
            )
        );

        do_action('gh_calendar_add_appointment_admin', $appointment_id, 'create_admin');

        //add appointment inside google calendar //
        $access_token = WPGH_APPOINTMENTS()->calendarmeta->get_meta($calendar_id, 'access_token', true);
        $google_calendar_id = WPGH_APPOINTMENTS()->calendarmeta->get_meta($calendar_id, 'google_calendar_id', true);
        if ($access_token && $google_calendar_id) {
            $client = WPGH_APPOINTMENTS()->google_calendar->get_google_client_form_access_token($calendar_id);
            $service = new Google_Service_Calendar($client);
            if (WPGH_APPOINTMENTS()->google_calendar->is_valid_calendar($calendar_id, $google_calendar_id, $service)) {
                $contact = WPGH()->contacts->get($contact_id);
                $event = new Google_Service_Calendar_Event(array(
                    'id' => 'ghcalendarcid' . $calendar_id . 'aid' . $appointment_id,
                    'summary' => $appointment_name,
                    'description' => $note,
    //                    'start' => WPGH_APPOINTMENTS()->google_calendar->get_google_time($start),
    //                    'end' => WPGH_APPOINTMENTS()->google_calendar->get_google_time( $end ),
                    'start' => ['dateTime' => date('Y-m-d\TH:i:s', $start) . 'Z'],
                    'end' => ['dateTime' => date('Y-m-d\TH:i:s', $end) . 'Z'],
                    'attendees' => array(
                        array('email' => $contact->email),
                    ),
                ));
                $event = $service->events->insert($google_calendar_id, $event);
            }
        }
        wp_die(json_encode($response));
    }


    }