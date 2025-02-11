<?php

namespace Wicked_Folders\Pro\Integrations\Everest_Forms;

use Wicked_Folders;
use Wicked_Folders\Admin as Core_Admin;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) die( '-1' );

class Everest_Forms {

    public $post_type_name = 'everest_form';

    public function __construct() {
        if ( is_admin() && isset( $_GET['page'] ) && 'evf-builder' == $_GET['page'] && empty( $_GET['form_id'] ) ) {
            add_filter( 'pre_get_posts',                                array( $this, 'pre_get_posts' ) );
            add_filter( 'wicked_folders_get_current_screen_post_type',  array( $this, 'get_current_screen_post_type' ) );
            add_filter( 'wicked_folders_construct_screen_state',        array( $this, 'wicked_folders_construct_screen_state' ) );

            add_action( 'admin_enqueue_scripts',                        array( $this, 'admin_enqueue_scripts' ), -1000 );
        }
    }

    public function admin_enqueue_scripts() {
        wp_enqueue_script( 'wicked-folders-everest-forms', plugin_dir_url( dirname( __FILE__ ) ) . '/everest-forms/everest-forms.js', array( 'jquery' ), Wicked_Folders::plugin_version() );
    }

    public function pre_get_posts( $query ) {
        if ( $this->post_type_name != $query->get('post_type') ) return;

        $state      = Core_Admin::get_instance()->get_screen_state();
        $taxonomy   = Wicked_Folders::get_tax_name( $this->post_type_name );

        if ( $state->folder ) {
            if ( 'unassigned_dynamic_folder' == $state->folder ) {
                $folder_ids = get_terms( $taxonomy, array( 'fields' => 'ids' ) );

                $query->set( 'tax_query', array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $folder_ids,
                        'operator' => 'NOT IN',
                    ),
                ) );
            } else {
                $query->set( 'tax_query', array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field'    => 'term_id',
                        'terms'    => $state->folder,
                    ),
                ) );
            }
        }
    }

    public function get_current_screen_post_type( $post_type ) {
        return $this->post_type_name;
    }

    public function wicked_folders_construct_screen_state( $state ) {
        // Everest Forms integration currently isn't compatible with AJAX navigation because
        // 'move to folder' column has to be added via JavaScript
        $state->enable_ajax_nav = false;

        return $state;
    }
}