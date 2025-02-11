/**
 * TablePress compatibility
 */
(function( $, data ){
    $(function(){
        function setupMoveToFolderColumn() {
            var pane = false;

            if ( typeof wickedFolderPane != 'undefined' ) pane = wickedFolderPane;

            $( '.wp-list-table.tablepress-all-tables tr' ).each( function(){
                var $row = $( this ),
                    $moveColumn = $( '<td />' ),
                    $handle = $( '<div class="wicked-move-multiple" />' ),
                    $items = $( '<div class="wicked-items" />' ),
                    tableId = $row.find( '[name="table[]"]' ).val(),
                    tableName = $row.find( '.row-title' ).text(),
                    postId = lookupPostId( tableId );

                $handle.append( '<span class="wicked-move-file dashicons dashicons-move" />' );
                $handle.append( $items );

                if ( tableId ) {
                    $items.append( '<div class="wicked-item" data-object-id="' + postId + '">' + tableName + '</div>' );

                    $handle.attr( 'data-object-id', postId );

                    $row.attr( 'data-wf-post-id', postId );
                }

                $moveColumn.attr( 'class', 'wicked_move column-wicked_move' );
                $moveColumn.append( $handle );

                $row.prepend( $moveColumn );
            });

            if ( pane ) pane.makePostsDraggable();
        }

        function lookupPostId( tableId ) {
            return data.tables[ tableId ];
        }

        // We need to set up the column after changing folders
        $( 'body' ).on( 'wickedfolders:ajaxNavigationDone', setupMoveToFolderColumn );

        setupMoveToFolderColumn();
    });
})( jQuery, wickedFoldersTablePressData );
