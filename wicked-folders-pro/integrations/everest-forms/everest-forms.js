/**
 * Everest Forms compatibility
 */
( function( $ ){
    $( function(){

        function setupMoveToFolderColumn() {
            var col = $( '<td class="wicked_move column-wicked_move" />' );

            col.append( $( '<div class="wicked-move-multiple" data-object-id="" />' ).append( '<span class="wicked-move-file dashicons dashicons-move" />' ) ).append( '<div class="wicked-items"><div class="wicked-item" data-object-id=""></div></div>' );

            $( '.wp-list-table.forms tr' ).prepend( col );

            $( '.wp-list-table.forms [name="form_id[]"]' ).each( function(){
                $( this ).parents( 'tr' ).find( '[data-object-id]' ).attr( 'data-object-id', $( this ).val() );
            } );
        }

        // We need to re-setup the column when the AJAX navigation is done
        $( 'body' ).on( 'wickedfolders:ajaxNavigationDone', setupMoveToFolderColumn );

        setupMoveToFolderColumn();
    });
} )( jQuery );
