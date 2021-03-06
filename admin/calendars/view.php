<?php
namespace GroundhoggBookingCalendar\Admin\Appointments;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\get_db;
use GroundhoggBookingCalendar\Classes\Appointment;
use GroundhoggBookingCalendar\Classes\Calendar;
use Groundhogg\Plugin;

if (!defined('ABSPATH')) exit;

$calendar_id = $_GET['calendar'];
$calendar = new Calendar($calendar_id);

$appointments = get_db('appointments')->query(['calendar_id' => $calendar_id]);
$display_data = [];
foreach ($appointments as $appo) {

    $appointment = new Appointment($appo->ID);
    $display_data[] = $appointment->get_full_calendar_event();
}
$json = json_encode($display_data);
$start_time = '00:00';
$end_time = '23:59';
$access_token = $calendar->get_access_token();
$google_calendar_id = $calendar->get_google_calendar_id();

?>
<div id="col-container" class="wp-clearfix">
    <div id="col-left">
        <div class="col-wrap">
            <div class="form-wrap">
                <div class="form-field term-contact-wrap">
                    <label><?php _e('Select Contact') ?></label>
                    <?php
                    $contact_details = [
                        'name' => 'contact_id',
                        'id' => 'contact-id',
                    ];
                    $contact_id = absint(get_request_var('contact'));
                    if ($contact_id) {
                        $contact_details ['selected'] = [$contact_id];
                        $contact_details ['disabled'] = true;
                        echo "<input type='hidden' name='redirect' value='true' />";
                    }
                    echo html()->dropdown_contacts($contact_details); ?>
                    <p class="description"><?php _e('Please select client contact from contact list.', 'groundhogg-calendar') ?></p>
                </div>
                <div class="form-field term-calendar-name-wrap">
                    <label><?php _e('Appointment Name') ?></label>
                    <?php echo html()->input([
                        'name' => 'name',
                        'id' => 'appointmentname',
                        'type' => 'text',
                        'placeholder' => 'Appointment Name'
                    ]); ?>

                    <!--                                    <input name="name" id="appointmentname"type="text"  size="40" aria-required="true" placeholder="Appointment Name">-->
                    <p class="description"><?php _e('Give nice name for your appointment.', 'groundhogg-calendar') ?></p>
                </div>
                <div class="form-field term-calendar-description-wrap">
                    <label><?php _e('Note', 'groundhogg-calendar'); ?></label>
                    <?php echo html()->textarea([
                        'name' => 'notes',
                        'id' => 'notes',
                        'placeholder' => 'Any information that might be important.'
                    ]); ?>
                    <p class="description"><?php _e('Additional information about appointment.', 'groundhogg-calendar') ?></p>
                </div>
                <div class="form-field">
                    <label><?php _e('Date', 'groundhogg-calendar'); ?></label>
                    <?php echo html()->date_picker([
                        'type' => 'text',
                        'id' => 'date-picker',
                        'placeholder' => 'Y-m-d'
                    ]);
                    ?>
                </div>
                <div class="time-slots form-field hidden">
                    <label><?php _e('Time', 'groundhogg-calendar'); ?></label>
                    <div style="text-align: center;" id="spinner">
                        <span class="spinner" style="float: none; visibility: visible"></span>
                    </div>
                    <div id="time-slots" class="select-time">
                        <div id="select_time"></div>
                    </div>
                    <div id="appointment-errors" class="appointment-errors hidden"></div>
                </div>
                <div class="submit-wrap">
                    <input type="button" name="btndisplay" id="btnalert" value="Book appointment" class="button button-primary"/>
                </div>
                <?php if ($access_token && $google_calendar_id) : ?>
                    <div class="alert alert-success">
                        <p><b><?php _e('Google sync is enabled.', 'groundhogg-calendar'); ?></b>
                            <a class="button"
                               href="<?php echo wp_nonce_url(admin_url('admin.php?page=gh_calendar&action=google_sync&calendar=' . $_GET['calendar'])); ?>"><?php _e('Sync Now'); ?></a>
                        </p>
                    </div>
                <?php endif; ?>
                <?php if ($calendar->is_zoom_enabled() && (!is_wp_error($calendar->get_access_token_zoom()))) : ?>
                    <div class="alert alert-success">
                        <p><b><?php _e('Zoom sync is enabled.', 'groundhogg-calendar'); ?></b></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div id="col-right">
        <div class="col-wrap">
            <div class="postbox" style="margin-top: 10px;">
                <div class="inside">
                    <div id='calendar' class=""></div>
                    <table class="status-colors">
                        <tr>
                            <td class="pending"><b><?php _e('Pending', 'groundhogg-calendar'); ?></b></td>
                            <td class="approved"><b><?php _e('Approved', 'groundhogg-calendar'); ?></b></td>
                            <td class="canceled"><b><?php _e('Canceled', 'groundhogg-calendar'); ?></b></td>
                        </tr>
                    </table>
                    <input type="hidden" id="calendar_id" value="<?php echo $_GET['calendar']; ?>">
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
    jQuery(function ($) {

        $('#external-events .fc-event').each(function () {
            // make the event draggable using jQuery UI
            $(this).draggable({
                zIndex: 999,
                revert: true,      // will cause the event to go back to its
                revertDuration: 0  //  original position after the drag
            });
        });

        var display = [];
        <?php
        $availability = $calendar->get_meta('rules', true);
        if (!empty($availability)) :
        foreach ( $availability as $avail ) :
        ?>
        display.push({
            dow: [<?php echo $calendar->get_day_number($avail['day']); ?>],
            start: '<?php echo $avail['start']; ?>',
            end: '<?php echo $avail['end']; ?>'
        });
        <?php
        endforeach;
        else :
        ?>
        display.push({
            dow: [1, 2, 3, 4, 5, 6, 0],
            start: '9:00',
            end: '17:00'
        });
        <?php endif; ?>


        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay,listMonth'
            },
            businessHours: display,
            defaultView: 'month', //agendaWeek
            slotDuration: '00:15:00',
            editable: true,
            eventLimit: true, // allow "more" link when too many events
            selectable: false,
            firstDay: <?php echo get_option('start_of_week'); ?>,
            minTime: '<?php echo $start_time;  ?>',
            maxTime: '<?php echo $end_time; ?>',
            navLinks: true,
            droppable: true,
            allDaySlot: false,
            nowIndicator: true,
            dayRender: function (date, cell) {
                if (date < new Date()) {
                    cell.css("background-color", "");
                }
            },
            eventOverlap: false,
            eventDrop: function (event, delta, revertFunc) {
                // disable booking previous date
                if ((event.start / 1000) < <?php echo current_time('timestamp');?>   ) {
                    revertFunc();
                    alert('You can not book past Date.');
                } else {
                    if (event.id != 'booking_event') {
                        // make a cll if event is not a booking event
                        // make AJAX request to handle reschedule
                        adminAjaxRequest(
                            {
                                action: 'groundhogg_update_appointments',
                                start_time: moment(event.start).format('YYYY-MM-DD HH:mm:00'),
                                end_time: moment(event.end).format('YYYY-MM-DD HH:mm:00'),
                                id: event.id,
                            },
                            function callback(response) {
                                // Handler
                                if (response.success) {
                                    alert(response.data.msg);
                                } else {
                                    alert(response.data);
                                }
                            }
                        );
                    }
                }
            },
            eventResize: function (event, delta, revertFunc) {
                // handle resizing of event
                if (event.id != 'booking_event') {
                    // make a cll if event is not a booking event
                    // make AJAX request to handle reschedule
                    adminAjaxRequest(
                        {
                            action: 'groundhogg_update_appointments',
                            start_time: moment(event.start).format('YYYY-MM-DD HH:mm:00'),
                            end_time: moment(event.end).format('YYYY-MM-DD HH:mm:00'),
                            id: event.id,
                        },
                        function callback(response) {
                            // Handler
                            if (response.success) {
                                alert(response.data.msg);
                            } else {
                                alert(response.data);
                                revertFunc();
                            }
                        }
                    );
                }
            },
            events: <?php echo $json; ?>
        });

        function isOverlapping(start, end) {
            var arrCalEvents = $('#calendar').fullCalendar('clientEvents');
            for (i in arrCalEvents) {
                if ((end >= arrCalEvents[i].start && start <= arrCalEvents[i].end) || (end == null && (event.start >= arrCalEvents[i].start && start <= arrCalEvents[i].end))) {//!(Date(arrCalEvents[i].start) >= Date(event.end) || Date(arrCalEvents[i].end) <= Date(event.start))
                    return true;
                }
            }
            return false;
        }
    })
    ;
</script>
