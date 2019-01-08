<?php
/**
 * Appointments Table.
 *
 * Store meta information about Appointments.
 *
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_DB_Calendar_Meta Class
 *
 * @since 2.1
 */
class WPGH_DB_Appointment_Meta extends WPGH_DB  {

    /**
     *
     * @var string
     */
    public $cache_group = 'appointment_meta';

    /**
     * WPGH_DB_Appointment_Meta constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->table_name  = $wpdb->prefix . 'gh_appointmentmeta';
        $this->primary_key = 'meta_id';
        $this->version     = '1.0';

        add_action( 'plugins_loaded', array( $this, 'register_table' ), 11 );

        add_action( 'wpgh_delete_appointment', array( $this, 'delete_appointment_meta' ) );
    }

    /**
     * returns list of column
     *
     * @return array
     */
    public function get_columns() {
        return array(
            'meta_id'           => '%d',
            'appointment_id'    => '%s',
            'meta_key'          => '%s',
            'meta_value'        => '%s',

        );
    }

    /**
     * Register table in list of database table
     */
    public function register_table() {
        global $wpdb;
        $wpdb->appointmentmeta = $this->table_name;
    }

    /**
     *  Retrieve meta data form database
     *
     * @param int $appointment_id
     * @param string $meta_key
     * @param bool $single
     * @return bool|mixed
     */
    public function get_meta( $appointment_id = 0, $meta_key = '', $single = false ) {
        $appointment_id = $this->sanitize_appointment_id( $appointment_id );
        if ( false === $appointment_id ) {
            return false;
        }
        return get_metadata( 'appointment', $appointment_id, $meta_key, $single );
    }

    /**
     * Adds meta data in database.
     *
     * @param int $appointment_id
     * @param string $meta_key
     * @param $meta_value
     * @param bool $unique
     * @return bool|false|int
     */

    public function add_meta( $appointment_id = 0, $meta_key = '', $meta_value, $unique = false ) {
        $appointment_id = $this->sanitize_appointment_id( $appointment_id );
        if ( false === $appointment_id ) {
            return false;
        }
        return add_metadata( 'appointment', $appointment_id, $meta_key, $meta_value, $unique );
    }

    /**
     * Update meta value in data base
     *
     * @param int $appointment_id
     * @param string $meta_key
     * @param $meta_value
     * @param string $prev_value
     * @return bool|int
     */
    public function update_meta( $appointment_id = 0, $meta_key = '', $meta_value, $prev_value = '' ) {
        $appointment_id = $this->sanitize_appointment_id( $appointment_id );
        if ( false === $appointment_id ) {
            return false;
        }

        return update_metadata( 'appointment', $appointment_id, $meta_key, $meta_value, $prev_value );
    }

    /**
     *  Delete meta data from database
     *
     * @param int $appointment_id
     * @param string $meta_key
     * @param string $meta_value
     * @return bool
     */
    public function delete_meta( $appointment_id = 0, $meta_key = '', $meta_value = '' ) {
        return delete_metadata( 'appointment', $appointment_id, $meta_key, $meta_value );
    }


    /**
     * Delete appointment meta
     *
     * @param $id
     * @return false|int
     */
    public function delete_appointment_meta( $id ){
        global $wpdb;
        $result = $wpdb->delete( $this->table_name, array( 'appointment_id' => $id ), array( '%d' ) );
        return $result;
    }

    /**
     * clean appointment field
     *
     * @param $appointment_id
     * @return bool|int
     */
    private function sanitize_appointment_id( $appointment_id ) {
        if ( ! is_numeric( $appointment_id ) ) {
            return false;
        }
        $appointment_id = (int) $appointment_id;
        if ( absint( $appointment_id ) !== $appointment_id ) {
            return false;
        }
        if ( empty( $appointment_id ) ) {
            return false;
        }
        return absint( $appointment_id );
    }



    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $sql = "CREATE TABLE " . $this->table_name . " (
        meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        appointment_id bigint(20) unsigned NOT NULL,
        meta_key varchar(255) DEFAULT NULL,
        meta_value longtext,
        PRIMARY KEY (meta_id),
        KEY appointment_id (appointment_id),
        KEY meta_key (meta_key)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );
        update_option( $this->table_name . '_db_version', $this->version );
    }

}