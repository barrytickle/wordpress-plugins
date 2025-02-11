<?php

namespace Wicked_Folders\Pro;

use Wicked_Folders;

abstract class Stub_Post_Type {

    public $name;

    public $name_plural;

    public $post_type_name;

    /**
     * Returns the IDs of posts that haven't been assigned to a folder.
     */
    abstract function get_assigned_ids();

    abstract function get_total_count();

    public function get_assigned_count() {
        return count( $this->get_assigned_ids() );
    }

    public function __construct() {
        add_filter( 'wicked_folders_get_current_screen_post_type',              array( $this, 'get_current_screen_post_type' ) );
        add_filter( "wicked_folders_{$this->post_type_name}_total_count",       array( $this, 'get_total_count' ) );
        add_filter( "wicked_folders_{$this->post_type_name}_assigned_count",    array( $this, 'get_assigned_count' ) );        
    }

    public function register_post_type() {
		$args = array(
			'label'					=> _x( $this->name_plural, 'Post type plural name', 'wicked-folders' ),
			'labels'				=> array(
                'name'					=> _x( $this->name_plural, 'Post type plural name', 'wicked-folders' ),
                'singular_name'			=> _x( 'Restrict Content Pro Membership', 'Post type singular name', 'wicked-folders' ),
            ),
			'description'			=> __( "A post type to represent {$this->name_plural} for the purpose of organizing memberships into folders using the Wicked Folders plugin.", 'wicked-folders' ),
			'public'				=> false,
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
			'supports'				=> array(),
		);

		register_post_type( $this->post_type_name, $args );        
    }

    public function get_post_type() {
        return get_post_type_object( $this->post_type_name );
    }

    /**
     * Returns whether or not the current screen is a screen that displays this post type.
     */
    public function is_post_type_screen() {
        return false;
    }

    /**
     * Sets the post type to the stub post type when viewing the screen that displays this post type.
     * 
     * @param string $post_type
     *  The post type name.
     * 
     * @return string
     *  The stub post type if viewing a screen that displays the stub post type.
     */
    public function get_current_screen_post_type( $post_type ) {
        if ( $this->is_post_type_screen() ) {
            $post_type = $this->post_type_name;          
        }

        return $post_type;
    }

    /**
     * Returns whether or not folders are enabled for this post type.
     */
    public function enabled() {
        return Wicked_Folders::enabled_for( $this->post_type_name );
    }
}
