<?php

namespace Wicked_Folders\Pro;

use \Wicked_Folders;

?>

<p><?php _e( 'Use this screen to control who can view and edit folders.  Create policies to control who can do what.  Then select which policy each folder collection should use.', 'wicked-folders' ); ?></p>
<h2><?php _e( 'Policies', 'wicked-folders' ); ?></h2>
<p><?php _e( 'A policy is a set of permissions that control who can do what. Policies can be applied to folders for one or more post types (e.g. media, pages, posts, etc.).', 'wicked-folders' ); ?></p>
<?php if ( ! empty( $policies ) ) : ?>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col">
                    <?php _e( 'Title', 'wicked-folders' ); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $policies as $policy ) : ?>
                <tr>
                    <td class="title column-title has-row-actions column-primary page-title">
                        <strong>
                            <a class="row-title" href="<?php echo get_edit_post_link( $policy->ID ); ?>"><?php echo esc_html( $policy->post_title ); ?></a>
                        </strong>
                        <div class="row-actions">
                            <span>
                                <a href="<?php echo get_edit_post_link( $policy->ID ); ?>"><?php _e( 'Edit' ); ?></a>
                                |
                            </span>
                            <span class="delete">
                                <a href="<?php echo get_delete_post_link( $policy->ID, false, true ); ?>"><?php _e( 'Delete Permanently' ); ?></a>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">
                    <?php _e( 'Title', 'wicked-folders' ); ?>
                </th>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>
<p>
    <a class="button button-primary" href="<?php echo admin_url( 'post-new.php?post_type=' . Folder_Collection_Policy::get_post_type_name() ); ?>">
        <?php _e( 'Add New Policy', 'wicked-folders' ); ?>
    </a>
</p>
<br />
<form action="" method="post">
    <input type="hidden" name="action" value="wicked_folders_save_folder_collection_policy_assignments" />
    <?php wp_nonce_field( 'wicked_folders_save_folder_collection_policy_assignments', 'nonce' ); ?>
    <h2><?php _e( 'Policy Assignments', 'wicked-folders' ); ?></h2>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th scope="col">
                    <?php _e( 'Folder Collection', 'wicked-folders' ); ?>
                </th>
                <th scope="col">
                    <?php _e( 'Policy', 'wicked-folders' ); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $post_types as $post_type ) : $taxonomy = Wicked_Folders::get_tax_name( $post_type->name ); ?>
                <tr>
                    <td>
                        <label for="<?php echo esc_attr( $post_type->name ); ?>_policy">
                            <?php echo esc_html( $post_type->label ); ?>
                        </label>
                    </td>
                    <td>
                        <input type="hidden" name="wf_taxonomy[]" value="<?php echo esc_attr( $taxonomy ); ?>" />
                        <select id="<?php echo esc_attr( $post_type->name ); ?>_policy" name="wf_policy[]">
                            <option value="">(<?php _e( 'none', 'wicked-folders' ); ?>)</option>
                            <?php foreach ( $policies as $policy ) : ?>
                                <option value="<?php echo esc_attr( $policy->ID ); ?>"<?php if ( isset( $taxonomy_policies[ $taxonomy ] ) && $policy->ID == $taxonomy_policies[ $taxonomy ] ) echo ' selected'; ?>>
                                    <?php echo esc_html( $policy->post_title ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col">
                    <?php _e( 'Folder Collection', 'wicked-folders' ); ?>
                </th>
                <th scope="col">
                    <?php _e( 'Policy', 'wicked-folders' ); ?>
                </th>
            </tr>
        </tfoot>
    </table>
    <p class="submit">
        <input name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Policy Assignments' ); ?>" type="submit" />
    </p>
</form>
