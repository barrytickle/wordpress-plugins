<?php

namespace Wicked_Folders\Pro;

use Wicked_Folders;
use Wicked_Folders\Singleton;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Intercepts filters from the core plugin and applies permissions.
 */
class Permission_Manager extends Singleton {

    /**
	 * Holds the singleton instance of the class.
	 *
     * @var Permission_Manager
     */
    protected static $instance;

    protected function __construct() {
        add_filter( 'wicked_folders_can_create_folders',            array( $this, 'can_create_folders' ), 10, 3 );
        add_filter( 'wicked_folders_can_view_folder',               array( $this, 'can_view_folder' ), 10, 4 );
        add_filter( 'wicked_folders_can_edit_folder',               array( $this, 'can_edit_folder' ), 10, 4 );
        add_filter( 'wicked_folders_can_delete_folder',             array( $this, 'can_delete_folder' ), 10, 4 );
        add_filter( 'wicked_folders_can_assign_items_to_folder',    array( $this, 'can_assign_items_to_folder' ), 10, 4 );
        add_filter( 'wicked_folders_can_view_others_items',         array( $this, 'can_view_others_items' ), 10, 3 );
    }

    public function can_create_folders( $allowed, $user_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_create( $user_id );
        }

        return $allowed;
    }

    public function can_view_folder( $allowed, $user_id, $term_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_view( $term_id, $user_id );
        }

        return $allowed;
    }

    public function can_view_others_items( $allowed, $user_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_view_others_items( $user_id );
        }

        return $allowed;
    }

    public function can_edit_folder( $allowed, $user_id, $term_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_edit( $term_id, $user_id );
        }

        return $allowed;
    }

    public function can_delete_folder( $allowed, $user_id, $term_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_delete( $term_id, $user_id );
        }

        return $allowed;
    }

    public function can_assign_items_to_folder( $allowed, $user_id, $term_id, $taxonomy ) {
        $policy = Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );

        // If there's a security policy, enforce it
        if ( $policy ) {
            $allowed = $policy->can_assign( $term_id, $user_id );
        }

        return $allowed;
    }
}