<?php

namespace Wicked_Folders\Pro\Integrations\Restrict_Content_Pro;

use Wicked_Folders;
use Wicked_Folders\Admin as Core_Admin;
use Wicked_Folders\Pro\Stub_Post_Type;

class Customer extends Stub_Post_Type {

    public $name            = 'Restrict Content Pro Customer';

    public $name_plural     = 'Restrict Content Pro Customers';

    public $post_type_name  = 'wf_rcp_customer';

    public function __construct() {
        parent::__construct();
        
        add_filter( 'rcp_customers_list_table_columns',               array( $this, 'add_move_to_folder_column' ) );
        add_filter( 'rcp_customers_list_table_column_wicked_move',    array( $this, 'move_to_folder_column_content' ), 10, 2 );
        add_filter( 'rcp_customers_query_clauses',                    array( $this, 'customers_query_clauses' ) );
    }

    public function is_post_type_screen() {
        return isset( $_GET['page'] ) && 'rcp-customers' == $_GET['page'] && ! isset( $_GET['view'] );        
    }

    public function add_move_to_folder_column( $columns ) {
        if ( $this->enabled() ) {
            $a = array( 'wicked_move' => '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div><span class="screen-reader-text">' . __( 'Move to Folder', 'wicked-folders' ) . '</span></div>' );
        
            $columns = $a + $columns;
        }

        return $columns;
    }

    public function move_to_folder_column_content( $value, $membership ) {
		return '<div class="wicked-move-multiple" data-object-id="' . esc_attr( $membership->get_id() ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div class="wicked-item" data-object-id="' . esc_attr( $membership->get_id() ) . '">Move item</div></div>';
    }

    public function customers_query_clauses( $clauses ) {
        global $wpdb;

        // Only run this in the admin area.  Also ignore AJAX requests as the `get_current_screen()`
        // function used by get_screen_state() below is not available in AJAX requests.
        if ( ! is_admin() || wp_doing_ajax() ) {
            return $clauses;
        }
                
        $state = Core_Admin::get_instance()->get_screen_state();
        
        if ( $state->folder ) {
            if ( 'unassigned_dynamic_folder' == $state->folder ) {
                $ids = $this->get_assigned_ids();
                
                if ( ! empty( $ids ) ) {
                    $clauses['where'] .= " AND rcp_c.id NOT IN (" . implode( ',', $ids ) . ") ";
                }
            } else {
                $clauses['join']    .= " INNER JOIN {$wpdb->term_relationships} AS wf_tr ON wf_tr.object_id = rcp_c.id ";
                $clauses['where']   .= ( empty( $clauses['where'] ) ?  '' : ' AND ' ) . $wpdb->prepare( "wf_tr.term_taxonomy_id = %d", $state->folder );
            }
        }

        return $clauses;
    }

    public function get_assigned_ids() {
        global $wpdb;

		$taxonomy = Wicked_Folders::get_tax_name( $this->post_type_name );

        // Get the IDs of members that have been assigned to a folder
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "
                    SELECT DISTINCT rcp_c.id FROM {$wpdb->prefix}rcp_customers rcp_c
                    INNER JOIN {$wpdb->term_relationships} AS wf_term_relationships ON wf_term_relationships.object_id = rcp_c.id
                    INNER JOIN {$wpdb->term_taxonomy} AS wf_term_taxonomy ON wf_term_relationships.term_taxonomy_id = wf_term_taxonomy.term_taxonomy_id
                    WHERE wf_term_taxonomy.taxonomy = %s
                ", $taxonomy
            )
        );

        return $ids;       
    }

    public function get_total_count() {
        global $wpdb;

        return $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}rcp_customers" );
    }
}