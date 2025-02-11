<?php

namespace Wicked_Folders\Pro;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Represents folder permissions for a specific role or user.
 */
class Permission_Policy {

	/**
	 * The role the policy applies to.
	 *
	 * @var string
	 */
	public $role = false;

	/**
	 * The user ID the policy applies to.
	 *
	 * @var int
	 */
	public $user_id = false;

	/**
	 * Whether or not folders can be created.
	 *
	 * @var bool
	 */
	public $create = true;

	/**
	 * Whether or not folders owned by others can be viewed.
	 *
	 * @var bool
	 */
	public $view_others = true;

	/**
	 * Whether or not folders owned by others can be edited.
	 *
	 * @var bool
	 */
	public $edit_others = true;

    /**
     * Whether or not folders owned by others can be deleted.
     *
     * @var bool
     */
    public $delete_others = true;

	/**
	 * Whether or not items can be assigned to folders owned by others.
	 *
	 * @var bool
	 */
	public $assign_others = true;

	/**
	 * Whether or not items owned by other users can be viewed.
	 *
	 * @var bool
	 */
	public $view_others_items = true;
}
