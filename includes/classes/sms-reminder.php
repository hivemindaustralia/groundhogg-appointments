<?php

namespace GroundhoggBookingCalendar\Classes;

use Groundhogg\Contact;
use Groundhogg\Email;
use Groundhogg\Event_Process;
use GroundhoggSMS\Classes\SMS;


if ( ! defined( 'ABSPATH' ) ) exit;

class SMS_Reminder implements Event_Process
{

    public $ID;

    /**
     * @var SMS
     */
    public $sms;

    /**
     * @var Appointment
     */
    public $appointment;

    const BOOKED = 'appointment_booked';
    const APPROVED = 'appointment_approved';
    const RESCHEDULED = 'appointment_rescheduled';
    const CANCELLED = 'appointment_cancelled';

    const NOTIFICATION_TYPE = 6;

    /**
     * Reminder constructor.
     * @param int $sms_id
     * @param int $appointment_id
     */
    public function __construct( $appointment_id, $sms_id )
    {
        $this->sms = new SMS( $sms_id );
        $this->appointment = new Appointment( $appointment_id );
    }

    public function get_funnel_title()
    {
        return sprintf( __( 'SMS Reminder: %s', 'groundhogg' ), $this->appointment->get_name() );
    }

    public function get_step_title()
    {
        return $this->sms->get_title();
    }

    public function run( $contact, $event )
    {
        do_action( 'groundhogg/calendar/sms_reminder/run/before', $this, $contact, $event );
        $result = $this->sms->send( $contact, $event );
        do_action( 'groundhogg/calendar/sms_reminder/run/after', $this, $contact, $event );
        return $result;
    }

    public function can_run()
    {
        return true;
    }
}