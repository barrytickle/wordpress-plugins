<div class="wicked-permissions-meta-box">
    <table class="wicked-permissions">
        <thead>
            <tr>
                <th class="all">
                    &nbsp;
                </th>
                <th class="name">
                    <?php _e( 'Role', 'wicked-folders' ); ?>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-create">
                        <?php _e( "Create Folders", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to create folders. Users can edit, delete, and assign items to folders they create.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-create" type="checkbox" name="create_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'create' ); ?> />
                    </div>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-view-others">
                        <?php _e( "View Other's Folders", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to view folders that are owned by other users.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-view-others" type="checkbox" name="view_others_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'view_others' ); ?> />
                    </div>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-edit-others">
                        <?php _e( "Edit Other's Folders", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to rename, clone, or move folders that are owned by other users.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-edit-others" type="checkbox" name="edit_others_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'edit_others' ); ?> />
                    </div>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-delete-others">
                        <?php _e( "Delete Other's Folders", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to delete folders that are owned by other users.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-delete-others" type="checkbox" name="delete_others_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'delete_others' ); ?> />
                    </div>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-assign-others">
                        <?php _e( "Assign Other's Folders", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to assign (or unassign) items to/from folders that are owned by other users.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-assign-others" type="checkbox" name="assign_others_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'assign_others' ); ?> />
                    </div>
                </th>
                <th class="permission">
                    <label for="wf-all-roles-view-others-items">
                        <?php _e( "View Other's Items", 'wicked-folders' ); ?>
                    </label>
                    <span class="dashicons dashicons-editor-help" title="<?php _e( "Allows users to view items that are owned by other users. When left unchecked, users will only be able to see items they own.", 'wicked-folders' ); ?>"></span>
                    <div>
                        <input id="wf-all-roles-view-others-items" type="checkbox" name="view_others_items_all" <?php echo _wicked_folders_all_roles_checkbox_helper( $permissions, 'view_others_items' ); ?> />
                    </div>
                </th>                
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $roles->roles as $key => $role ) : ?>
                <tr>
                    <td class="all">
                        <input type="hidden" name="role[]" value="<?php echo esc_attr( $key ); ?>" />
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-all" type="checkbox" name="all[]" value="1" <?php echo _wicked_folders_all_permissions_checkbox_helper( $permissions, $key ); ?> title="<?php _e( 'Select all permissions for this row', 'wicked-folders' ); ?>" />
                    </td>
                    <td class="name">
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-all">
                            <?php echo esc_html( $role['name'] ); ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-create" type="checkbox" name="<?php echo esc_attr( $key ); ?>_create" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'create' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-create" class="screen-reader-text">
                            <?php _e( sprintf( '%s: Create Folders', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-view" type="checkbox" name="<?php echo esc_attr( $key ); ?>_view_others" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'view_others' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-view" class="screen-reader-text">
                            <?php _e( sprintf( '%s: View Others Folders', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-edit" type="checkbox" name="<?php echo esc_attr( $key ); ?>_edit_others" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'edit_others' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-edit" class="screen-reader-text">
                            <?php _e( sprintf( '%s: Edit Others Folders', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-delete" type="checkbox" name="<?php echo esc_attr( $key ); ?>_delete_others" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'delete_others' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-delete" class="screen-reader-text">
                            <?php _e( sprintf( '%s: Delete Others Folders', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-assign" type="checkbox" name="<?php echo esc_attr( $key ); ?>_assign_others" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'assign_others' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-assign" class="screen-reader-text">
                            <?php _e( sprintf( '%s: Assign Others Folders', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>
                    <td class="permission">
                        <input id="wf-permission-<?php echo esc_attr( $key ); ?>-view-others-items" type="checkbox" name="<?php echo esc_attr( $key ); ?>_view_others_items" value="1" <?php echo _wicked_folders_permission_checkbox_helper( $permissions, $key, 'view_others_items' ); ?> />
                        <label for="wf-permission-<?php echo esc_attr( $key ); ?>-view-others-items" class="screen-reader-text">
                            <?php _e( sprintf( '%s: View Others Items', $role['name'] ), 'wicked-folders' ) ?>
                        </label>
                    </td>                    
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
    (function($){
        $(function(){
            $( '.wicked-permissions' ).on( 'change', '[type="checkbox"]', function(){
                var checked = $( this ).prop( 'checked' );
                var name = $( this ).attr( 'name' );

                if ( 'all[]' == name ) {
                    $( this ).parents( 'tr' ).find( '[type="checkbox"]' ).prop( 'checked', checked );
                } else if ( -1 != name.indexOf( '_all' ) ) {
                    $( '[name$="_' + name.replace( '_all', '' ) + '"]' ).prop( 'checked', checked );
                }

                toggleAll();
            } );

            function toggleAll() {
                $( '.wicked-permissions tbody tr' ).each( function(){
                    var count = $( this ).find( '.permission [type="checkbox"]' ).length;
                    var checkedCount = $( this ).find( '.permission [type="checkbox"]:checked' ).length;

                    $( this ).find( '[name="all[]"]' ).prop( 'checked', count == checkedCount );
                } );

                $( '.wicked-permissions tbody tr' ).eq( 0 ).find( '.permission [type="checkbox"]' ).each( function(){
                    var name = $( this ).attr( 'name' );
                    var roleCount = $( '.wicked-permissions [name="' + name + '"]' ).length;
                    var roleCheckedCount = $( '.wicked-permissions [name="' + name + '"]:checked' ).length;

                    $( '[name="' + name.replace( '[]', '_all' ) + '"]' ).prop( 'checked', roleCount == roleCheckedCount );
                } );
            }
        });
    })(jQuery);
</script>

<?php

function _wicked_folders_all_roles_checkbox_helper( $permissions, $permission ) {
    if ( empty( $permissions ) ) return '';

    foreach ( $permissions as $permission_policy ) {
        if ( isset( $permission_policy[ $permission ] ) && ! $permission_policy[ $permission ] ) {
            return '';
        }
    }

    return 'checked';
}

function _wicked_folders_all_permissions_checkbox_helper( $permissions, $role ) {
    if ( empty( $permissions ) ) return '';

    foreach ( $permissions as $permission_policy ) {
        if ( $role == $permission_policy['role'] ) {
            if (
                ! $permission_policy['create'] ||
                ! $permission_policy['view_others'] ||
                ! $permission_policy['edit_others'] ||
                ! $permission_policy['delete_others'] ||
                ! $permission_policy['assign_others'] ||
                ! $permission_policy['view_others_items']
            ) return '';
        }
    }

    return 'checked';
}

function _wicked_folders_permission_checkbox_helper( $permissions, $role, $permission ) {
    foreach ( $permissions as $permission_policy ) {
        if ( $role == $permission_policy['role'] ) {
            if ( isset( $permission_policy[ $permission ] ) && $permission_policy[ $permission ] ) {
                return 'checked';
            }
        }
    }

    return '';
}
