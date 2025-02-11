<?php

namespace Wicked_Folders\Pro;

use Wicked_Folders\Object_Collection;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Holds a collection of conditions.
 */
class Permission_Policy_Collection extends Object_Collection {

    /**
     * Add a permisison policy.
     *
     * @param Permission_Policy
     *  The policy to add.
     */
    public function add( $item ) {
        $this->add_if( $item, 'Wicked_Folders\Pro\Permission_Policy' );
    }

	/**
	 * Checks all policies to determine if the user has the permission.
	 */
	private function can( $permission, $user_id ) {
		$allowed 	= false;
		$user 		= get_userdata( $user_id );

		if ( ! $user ) return false;

		foreach ( $this->items as $policy ) {
			// Check if policy applies based on role
			if ( in_array( $policy->role, $user->roles ) ) {
				if ( $policy->{$permission} ) {
					return true;
				}
			}

			// Check if policy applies based on user ID
			if ( $user_id == $policy->user_id ) {
				if ( $policy->{$permission} ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Determines if policies allow creating.
	 */
	public function can_create( $user_id ) {
		return $this->can( 'create', $user_id );
	}

	/**
	 * Determines if policies allow editing.
	 */
	public function can_edit( $user_id ) {
		return $this->can( 'edit_others', $user_id );
	}

	/**
	 * Determines if policies allow deleting.
	 */
	public function can_delete( $user_id ) {
		return $this->can( 'delete_others', $user_id );
	}

	/**
	 * Determines if policies allow viewing.
	 */
	public function can_view( $user_id ) {
		return $this->can( 'view_others', $user_id );
	}

	/**
	 * Determins if policies allow assigning.
	 */
	public function can_assign( $user_id ) {
 		return $this->can( 'assign_others', $user_id );
 	}

	/**
	 * Determines if policies allow viewing items created by other users.
	 */
	public function can_view_others_items( $user_id ) {
		return $this->can( 'view_others_items', $user_id );
	}	
}
