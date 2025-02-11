<?php

namespace Wicked_Folders\Pro;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Applies permissions to a collection of folders.
 */
class Folder_Collection_Policy {

	/**
	 * The name of the security policy post type.
	 *
	 * @var string
	 */
	private static $post_type_name = 'wf_collection_policy';

	/**
	 * Names of taxonomies that the policy is assigned to.
	 *
	 * @var array
	 */
	private $taxonomies = array();

	/**
	 * The permissions assigned to the policy.
	 *
	 * @var Permission_Policy_Collection
	 *  Collection of Permission_Policy objects.
	 */
	private $permissions;

	public function __construct( $id = false ) {
		$this->permissions = new Permission_Policy_Collection();

		if ( $id ) $this->load( $id );
	}

	/**
	 * Determines if the policy allows new folders to be created.
	 *
	 * @param int $user_id
	 *  The ID of the user performing the create.
	 *
	 * @return bool
	 *  True if the policy allows the action, false if not.
     */
	public function can_create( $user_id ) {
		$allowed = $this->permissions->can_create( $user_id );

		return apply_filters( 'wf_collection_policy_can_create', $allowed, $user_id, $this );
	}

	/**
	 * Determines if the term can be viewed based on the policy.
	 *
	 * @param int $term_id
     *  The term ID (i.e. folder ID) being edited.
	 *
	 * @param int $user_id
	 *  The ID of the user performing the edit.
	 *
	 * @return bool
	 *  True if the policy allows the action, false if not.
     */
	public function can_view( $term_id, $user_id ) {
		$allowed 	= false;
		$owner_id 	= get_term_meta( $term_id, 'wf_owner_id', true );

		if ( $owner_id == $user_id ) {
			$allowed = true;
		} else {
			$allowed = $this->permissions->can_view( $user_id );
		}

		return apply_filters( 'wf_collection_policy_can_view', $allowed, $term_id, $user_id, $this );
	}

	/**
     * Determines if the term can be edited based on the policy.
     *
     * @param int $term_id
     *  The term ID (i.e. folder ID) being edited.
	 *
	 * @param int $user_id
	 *  The ID of the user performing the edit.
	 *
	 * @return bool
	 *  True if the policy allows the action, false if not.
     */
    public function can_edit( $term_id, $user_id ) {
		$allowed 	= false;
		$owner_id 	= get_term_meta( $term_id, 'wf_owner_id', true );

		if ( $owner_id == $user_id ) {
			$allowed = true;
		} else {
			$allowed = $this->permissions->can_edit( $user_id );
		}

		return apply_filters( 'wf_collection_policy_can_edit', $allowed, $term_id, $user_id, $this );
    }

    /**
     * Determines if the term can be deleted based on the policy.
     *
     * @param int $term_id
     *  The term ID (i.e. folder ID) being deleted.
	 *
	 * @param int $user_id
	 *  The ID of the user performing the delete.
	 *
	 * @return bool
	 *  True if the policy allows the action, false if not.
     */
    public function can_delete( $term_id, $user_id ) {
		$allowed 	= false;
		$owner_id 	= get_term_meta( $term_id, 'wf_owner_id', true );

		if ( $owner_id == $user_id ) {
			$allowed = true;
		} else {
			$allowed = $this->permissions->can_delete( $user_id );
		}

		return apply_filters( 'wf_collection_policy_can_delete', $allowed, $term_id, $user_id, $this );
    }

	/**
     * Determines if the term can be assigned based on the policy.
     *
     * @param int $term_id
     *  The term ID (i.e. folder ID) being assigned.
	 *
	 * @param int $user_id
	 *  The ID of the user performing the assignment.
	 *
	 * @return bool
	 *  True if the policy allows the action, false if not.
     */
    public function can_assign( $term_id, $user_id ) {
		$allowed 	= false;
		$owner_id 	= get_term_meta( $term_id, 'wf_owner_id', true );

		if ( $owner_id == $user_id ) {
			$allowed = true;
		} else {
			$allowed = $this->permissions->can_assign( $user_id );
		}

		return apply_filters( 'wf_collection_policy_can_assign', $allowed, $term_id, $user_id, $this );
    }

	/**
	 * Determines if the policy allows the specified user to view others' items.
	 *
	 * @param int $user_id
	 *  The ID of the user the check is being requested for.
	 *
	 * @return bool
	 *  True if the policy allows viewing items created by others, false if not.
     */
	public function can_view_others_items( $user_id ) {
		$allowed = $this->permissions->can_view_others_items( $user_id );

		return apply_filters( 'wf_collection_policy_can_view_others_items', $allowed, $user_id, $this );
	}

	/**
	 * Returns the post type name.
	 */
	public static function get_post_type_name() {
		return self::$post_type_name;
	}

	/**
	 * Loads the policy from the database.
	 *
	 * @param int $id
	 *  The policy post ID.
	 */
	public function load( $id ) {
		$permissions = get_post_meta( $id, 'permissions', true );

		foreach ( $permissions as $permission ) {
			$policy = new Permission_Policy();

			$policy->role 				= $permission['role'];
			$policy->create 			= $permission['create'];
			$policy->edit_others 		= $permission['edit_others'];
			$policy->delete_others 		= $permission['delete_others'];
			$policy->assign_others 		= $permission['assign_others'];
			$policy->view_others 		= $permission['view_others'];
			$policy->view_others_items 	= isset( $permission['view_others_items'] ) ? $permission['view_others_items'] : true;

			$this->permissions->add( $policy );
		}
	}

	/**
	 * Sets the policy's permissions collection.
	 *
	 * @param Permission_Policy_Collection $permissions
	 *  The collection of permissions to set.
	 */
	public function set_permissions( $permissions ) {
		$this->permissions = $permissions;
	}

	public static function get_taxonomy_policy( $taxonomy ) {
		$policies = get_option( 'wf_taxonomy_policies', array() );

		if ( isset( $policies[ $taxonomy ] ) ) {
			$id = $policies[ $taxonomy ];

			// Make sure the post exists and is published
			if ( 'publish' != get_post_status( $id ) ) return false;

			return new Folder_Collection_Policy( $id );
		}

		return false;
	}
}
