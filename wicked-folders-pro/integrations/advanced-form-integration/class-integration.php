<?php

namespace Wicked_Folders\Pro\Integrations\Advanced_Form_Integration;

use Wicked_Folders;
use Wicked_Folders\Admin as Core_Admin;
use Wicked_Folders\Pro\Stub_Post_Type;

class Integration extends Stub_Post_Type {

    public $name            = 'Advanced Form Integration';

    public $name_plural     = 'Advanced Form Integrations';

    public $post_type_name  = 'wf_afi_integration';

    public function __construct() {
        parent::__construct();
        
        add_filter( 'adfoin_integration_table_columns',         array( $this, 'add_move_to_folder_column' ) );
        add_filter( 'adfoin_integration_table_column_value',    array( $this, 'move_to_folder_column_content' ), 10, 3 );
        add_filter( 'query',                                    array( $this, 'query' ) );
        add_filter( 'wicked_folders_after_ajax_scripts',        array( $this, 'after_ajax_scripts' ) );
    }

    public function is_post_type_screen() {
        return isset( $_GET['page'] ) && 'advanced-form-integration' == $_GET['page'] && ! isset( $_GET['id'] );        
    }

    public function add_move_to_folder_column( $columns ) {
        if ( $this->enabled() ) {
            $a = array( 'wicked_move' => '<div class="wicked-move-multiple" title="' . __( 'Move selected items', 'wicked-folders' ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"></div><span class="screen-reader-text">' . __( 'Move to Folder', 'wicked-folders' ) . '</span></div>' );
        
            $columns = $a + $columns;
        }

        return $columns;
    }

    public function move_to_folder_column_content( $value, $item, $column_name ) {
        if ( 'wicked_move' == $column_name ) {
            $value = '<div class="wicked-move-multiple" data-object-id="' . esc_attr( $item['id'] ) . '"><span class="wicked-move-file dashicons dashicons-move"></span><div class="wicked-items"><div class="wicked-item" data-object-id="' . esc_attr( $item['id'] ) . '">Move item</div></div>';
        }

        return $value;
    }

    public function query( $query ) {
        global $wpdb;

        if ( $this->is_post_type_screen() ) {
            // Make sure we have the right query
            if ( false !== strpos( $query, "SELECT * FROM {$wpdb->prefix}adfoin_integration" ) ) {
                $state      = Core_Admin::get_instance()->get_screen_state();
                $taxonomy   = Wicked_Folders::get_tax_name( $this->post_type_name );

                // Only filter if we have a folder
                if ( $state->folder ) {
                    if ( 'unassigned_dynamic_folder' == $state->folder ) {
                        $ids = $this->get_assigned_ids();
  
                        $query = str_replace(
                            "SELECT * FROM {$wpdb->prefix}adfoin_integration",
                            "SELECT * FROM {$wpdb->prefix}adfoin_integration WHERE {$wpdb->prefix}adfoin_integration.id NOT IN (" . implode( ', ', $ids ) . ")",
                            $query
                        );                         
                    } else {
                        $query = str_replace(
                            "SELECT * FROM {$wpdb->prefix}adfoin_integration",
                            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}adfoin_integration LEFT JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = {$wpdb->prefix}adfoin_integration.id LEFT JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '%s' AND tr.term_taxonomy_id = %d", $taxonomy, $state->folder ),
                            $query
                        );                        
                    }
                }
            }
        }

        return $query;
    }

    public function get_assigned_ids() {
        global $wpdb;

		$taxonomy = Wicked_Folders::get_tax_name( $this->post_type_name );

        // Get the IDs of integrations that have been assigned to a folder
        $ids = $wpdb->get_col(
            $wpdb->prepare(
                "
                    SELECT DISTINCT afi.id FROM {$wpdb->prefix}adfoin_integration afi
                    INNER JOIN {$wpdb->term_relationships} AS wf_term_relationships ON wf_term_relationships.object_id = afi.id
                    INNER JOIN {$wpdb->term_taxonomy} AS wf_term_taxonomy ON wf_term_relationships.term_taxonomy_id = wf_term_taxonomy.term_taxonomy_id
                    WHERE wf_term_taxonomy.taxonomy = %s
                ", $taxonomy
            )
        );

        return $ids;       
    }

    public function get_total_count() {
        global $wpdb;

        return $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}adfoin_integration" );
    }

    public function after_ajax_scripts( $scripts ) {
        if ( $this->is_post_type_screen() ) {
            $scripts[] = plugins_url( 'advanced-form-integration/assets/js/script.js' );
        }

        return $scripts;
    }
}