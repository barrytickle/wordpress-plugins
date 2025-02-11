<?php

namespace Wicked_Folders\Pro;

use Wicked_Folders\Dynamic_Folder;

/**
 * Represents a media extension (e.g. .jpg, .pdf, etc.) dynamic folder.
 */
class Media_Extension_Dynamic_Folder extends Dynamic_Folder {

    private $extension = false;

    public function __construct( $args = array() ) {
        parent::__construct( $args );
    }

    public function pre_get_posts( $query ) {

        $this->parse_id();

        if ( $this->extension ) {

            $meta_query = $query->get( 'meta_query' );

            if ( ! $meta_query ) $meta_query = array();

            $meta_query[] = array(
                'key'       => '_wp_attached_file',
                'value'     => '.' . $this->extension,
                'compare'   => 'LIKE',
            );

            $query->set( 'meta_query', $meta_query );

        }

    }

    /**
     * Parses the folder's ID to determine the extension the folder should
     * filter by.
     */
    private function parse_id() {

        $this->extension = substr( $this->id, 24 );

    }

    /**
     * Uses the Wicked_Folders\Folder_Factory\get_folder filter to register
     * this dynamic folder in the factory.
     */
    public static function register_factory( $folder, $id, $post_type, $taxonomy ) {
        if ( 0 === strpos( $id, 'dynamic_media_extension' ) ) {
            $folder = new Media_Extension_Dynamic_Folder( array(
                'id'        => $id,
                'post_type' => $post_type,
                'taxonomy'  => $taxonomy,
            ) );
        }

        return $folder;
    }
}
