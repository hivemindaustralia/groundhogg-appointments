<?php

namespace GroundhoggBookingCalendar;

use Groundhogg\Contact;
use function Groundhogg\get_current_contact;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use GroundhoggBookingCalendar\Classes\Appointment;
use GroundhoggBookingCalendar\Classes\Calendar;

$calendar_id = get_query_var('calendar_id');

$calendar = new Calendar($calendar_id);

if (!$calendar->exists()) {
    wp_die('Calendar does not exist.');
}

/**
 * Enqueue Calendar scripts
 */
function enqueue_calendar_scripts()
{
    global $calendar_id, $calendar;

    wp_enqueue_script('groundhogg-appointments-frontend');

    wp_localize_script('groundhogg-appointments-frontend', 'BookingCalendar', [
        'calendar_id' => $calendar_id,
        'start_of_week' => get_option('start_of_week'),
        'min_date' => $calendar->get_min_booking_period(true),
        'max_date' => $calendar->get_max_booking_period(true),
        'disabled_days' => $calendar->get_dates_no_slots(),
        'day_names' => ["SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"],
        'ajaxurl' => admin_url('admin-ajax.php'),
        'invalidDateMsg' => __('Please select a valid time slot.', 'groundhogg-calendar')
    ]);

    do_action('enqueue_groundhogg_calendar_scripts');
}

add_action('wp_enqueue_scripts', '\GroundhoggBookingCalendar\enqueue_calendar_scripts');

/**
 * Enqueue Calendar scripts
 */
function enqueue_calendar_styles()
{
    wp_enqueue_style('jquery-ui-datepicker');
    wp_enqueue_style('groundhogg-calendar-frontend');
    wp_enqueue_style('groundhogg-form');
    wp_enqueue_style('groundhogg-loader');

    do_action('enqueue_groundhogg_calendar_styles');
}

add_action('wp_enqueue_scripts', '\GroundhoggBookingCalendar\enqueue_calendar_styles');

status_header(200);
header('Content-Type: text/html; charset=utf-8');
nocache_headers();

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <base target="_parent">
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <title><?php echo $calendar->get_name(); ?></title>
    <?php wp_head(); ?>
</head>
<body class="groundhogg-calendar-body">
<div id="main" style="padding: 20px">
    <div class="loader-overlay" style="display: none"></div>
    <div class="loader-wrap" style="display: none">
        <p><span class="gh-loader"></span></p>
    </div>
    <div id="calendar-view">
        <div class="groundhogg-calendar">
            <div id="calendar-details" class="clearfix">
                <?php do_action( 'groundhogg/calendar/template/details', $calendar ); ?>
            </div>
            <div id="calendar-date-picker" class="clearfix">
                <?php do_action( 'groundhogg/calendar/template/date_picker', $calendar ); ?>
            </div>
            <div id="calendar-form" class="clearfix">
                <?php do_action( 'groundhogg/calendar/template/form', $calendar ); ?>
            </div>
        </div>
    </div>
</div>
<div class="clear"></div>
</body>
</html>