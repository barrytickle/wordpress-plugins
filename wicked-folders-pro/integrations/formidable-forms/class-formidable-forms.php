<?php

namespace Wicked_Folders\Pro\Integrations\Formidable_Forms;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) die( '-1' );

class Formidable_Forms {

    public function __construct() {
        add_filter( 'wicked_folders_attachment_total_count', array( $this, 'adjust_attachment_count' ) );
    }

    /**
     * Formidable forms sometimes hides media that was uploaded via a form causing the folder count to be off.
     * Adjust it when that's the case.
     * 
     * @see formidable-pro/classes/models/FrmProFileField.php::query_to_exclude_files
     */
    public function adjust_attachment_count( $count ) {
        global $wpdb;

        // Make sure Formidable forms is active
        if ( class_exists( '\FrmAppHelper' ) ) {
            if ( current_user_can( 'frm_edit_entries' ) ) {
                $show = \FrmAppHelper::get_param( 'frm-attachment-filter', '', 'get', 'absint' );
            } else {
                $show = false;
            }

            if ( ! $show ) {
                $hidden = $wpdb->get_var( "SELECT COUNT(pm.meta_id) FROM {$wpdb->postmeta} AS pm WHERE pm.meta_key = '_frm_file'" );

                $count -= $hidden;
            }            
        }

        return $count;
    }
}