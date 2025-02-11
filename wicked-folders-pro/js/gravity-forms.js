/**
 * Gravity Forms compatibility
 */
(function($){
    $(function(){

        // Gravity Forms hardcodes the column index it uses to wire row actions
        // to. Adding the 'move to folder' column to the beginning of the table
        // on the backend throws off the columns indexes; therefore, the column
        // is added to the end of the table and moved back to the beginning here
        // in JavaScript
        function moveColumnToBeginning() {
            $( '.wp-list-table.gf_entries tr' ).each( function(){
                var $row = $( this ),
                    $folderColumn = $row.find( '.column-wicked_move' );

                $row.prepend( $folderColumn );
            });
        }

        // We need to re-move the column after the page content is re-loaded via AJAX
        $( 'body' ).on( 'wickedfolders:ajaxNavigationDone', moveColumnToBeginning );

        moveColumnToBeginning();
    });
})(jQuery);
