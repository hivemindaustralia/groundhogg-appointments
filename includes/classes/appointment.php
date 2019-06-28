<?php

namespace GroundhoggBookingCalendar\Classes;


use Groundhogg\Base_Object_With_Meta;
use function Groundhogg\get_contactdata;
use Groundhogg\Plugin;
use GroundhoggBookingCalendar\DB\Calendar_Meta;
use \Google_Service_Calendar_Event;
use \Google_Service_Calendar;
use \Exception;

class Appointment extends Base_Object_With_Meta
{
    protected function get_meta_db()
    {
        return Plugin::$instance->dbs->get_db( 'appointmentmeta' );
    }

    protected function post_setup()
    {
        // TODO: Implement post_setup() method.
    }

    protected function get_db()
    {
        return Plugin::$instance->dbs->get_db( 'appointments' );
    }

    protected function get_object_type()
    {
        return 'appointment';
    }

    /**
     * Returns appointment id
     * @return int
     */
    public function get_id()
    {
        return absint( $this->ID );
    }

    /**
     * Return contact id
     * @return int
     */
    public function get_contact_id()
    {
        return absint( $this->contact_id );
    }

    /**
     * @return int
     */
    public function get_owner_id()
    {
        return $this->get_calendar()->get_user_id();
    }

    /**
     * Return calendar id
     * @return int
     */
    public function get_calendar_id()
    {
        return absint( $this->calendar_id );
    }

    protected $calendar = null;

    /**
     * @return Calendar
     */
    public function get_calendar()
    {
        if ( $this->calendar ) {
            return $this->calendar;
        }

        $this->calendar = new Calendar( $this->get_calendar_id() );
        return $this->calendar;
    }

    /**
     * Return name of appointment
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }


    public function get_status()
    {
        return $this->status;
    }

    /**
     * Return start time of appointment
     * @return int
     */
    public function get_start_time()
    {
        return absint( $this->start_time );
    }


    /**
     * Return end time of appointment
     * @return int
     */
    public function get_end_time()
    {
        return absint( $this->end_time );
    }

    /**
     * Update google as well.
     *
     * @param array $data
     * @return bool
     */
    public function update( $data = [] )
    {
        $status = parent::update( $data );

        if ( !$status ) {
            return $status;
        }

        if ( $this->get_calendar()->google_enabled() ) {
            $google_status = $this->update_in_google();
            if ( !$google_status ) {
                return false;
            }
        }

        return true;
    }


    protected function update_in_google()
    {

        $access_token = $this->get_calendar()->get_access_token();
        $google_calendar_id = $this->get_calendar()->get_google_calendar_id();
        if ( $access_token && $google_calendar_id ) {

//             create google client
            $client = \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->get_google_client_form_access_token( $this->get_calendar_id() );
            $service = new Google_Service_Calendar( $client );
            if ( \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->is_valid_calendar( $this->get_calendar_id(), $google_calendar_id, $service ) ) {

                $contact = get_contactdata( $this->get_contact_id() );
                $google_appointment_id = $this->get_google_appointment_id();
                $event = new Google_Service_Calendar_Event( array(
                    'id' => $google_appointment_id,
                    'summary' => $this->get_name(),
                    'description' => $this->get_meta( 'note', true ),
                    'start' => [ 'dateTime' => date( 'Y-m-d\TH:i:s', $this->get_start_time() ) . 'Z' ], //Date and time in UTC+0 and convert google required format.
                    'end' => [ 'dateTime' => date( 'Y-m-d\TH:i:s', $this->get_end_time() ) . 'Z' ],
                    'attendees' => array(
                        array( 'email' => $contact->get_email() ),
                    ),
                ) );

                try {
                    $updatedEvent = $service->events->update( $google_calendar_id, $google_appointment_id, $event );
                } catch ( \Exception $exception ) {
                    return false;
                }
            }
        }
        return true;
    }


    public function add_in_google()
    {

        $access_token = $this->get_calendar()->get_access_token();
        $google_calendar_id = $this->get_calendar()->get_google_calendar_id();
        if ( $access_token && $google_calendar_id ) {

            $client = \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->get_google_client_form_access_token( $this->get_calendar_id() );
            $service = new Google_Service_Calendar( $client );
            if ( \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->is_valid_calendar( $this->get_calendar_id(), $google_calendar_id, $service ) ) {

                $contact = get_contactdata( $this->get_contact_id() );
                $event = new Google_Service_Calendar_Event( array(
                    'id' => $this->get_google_appointment_id(),
                    'summary' => $this->get_name(),
                    'description' => $this->get_meta( 'note' ),
                    'start' => [ 'dateTime' => date( 'Y-m-d\TH:i:s', $this->get_start_time() ) . 'Z' ],
                    'end' => [ 'dateTime' => date( 'Y-m-d\TH:i:s', $this->get_end_time() ) . 'Z' ],
                    'attendees' => [
                        [ 'email' => $contact->get_email() ],
                    ],
                ) );
                $event = $service->events->insert( $google_calendar_id, $event );
            }
        }
    }

    /**
     * create appointment id for google events.
     *
     * @return string
     */
    protected function get_google_appointment_id()
    {
        return 'ghcalendarcid' . $this->get_calendar_id() . 'aid' . $this->get_id();
    }


    public function delete()
    {
        $status = $this->get_db()->delete( $this->get_id() );

        if ( !$status ) {
            return $status;
        }

        if ( $this->get_calendar()->google_enabled() ) {
            $google_status = $this->delete_in_google();
            if ( !$google_status ) {
                return false;
            }
        }
        return true;
    }


    /**
     * Delete Appointment from google calendar if it exist.
     *
     */
    public function delete_in_google()
    {

        $access_token = $this->get_calendar()->get_access_token();
        $google_calendar_id = $this->get_calendar()->get_google_calendar_id();
        if ( $access_token && $google_calendar_id ) {

            $client = \GroundhoggBookingCalendar\Plugin::$instance->google_calendar->get_google_client_form_access_token( $this->get_calendar_id() );
            $service = new Google_Service_Calendar( $client );
            try {
                $service->events->delete( $google_calendar_id, $this->get_google_appointment_id() );
            } catch ( Exception $e ) {
                return false;
            }
        }

        return true;
    }


    public function get_full_calendar_event()
    {

        if ( $this->get_status() === 'cancelled' ) {
            $color = '#dc3545';
        } else if ( $this->get_status() == 'approved' ) {
            $color = '#28a745';
        } else {
            $color = '#0073aa';
        }

        return [
            'id' => $this->get_id(),
            'title' => $this->get_name(),
            'start' => Plugin::$instance->utils->date_time->convert_to_local_time( (int) $this->get_start_time() ) * 1000,
            'end' => Plugin::$instance->utils->date_time->convert_to_local_time( (int) $this->get_end_time() ) * 1000,
            'constraint' => 'businessHours',
            'editable' => true,
            'allDay' => false,
            'color' => $color,
            'url' => admin_url( 'admin.php?page=gh_calendar&action=edit_appointment&appointment=' . $this->get_id() )
        ];

    }

}