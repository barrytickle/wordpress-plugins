( function( $, api ){
	$( function(){

		var api = window.wickedFolderPaneAPI;

		var AttachmentsBrowserFolderPane = Backbone.View.extend( {
			id: 'wicked-folder-pane',
			className: 'wicked-folder-pane wicked-media-modal-folder-pane',
		} );

		var Attachments = wp.media.view.Attachments,
			AttachmentsBrowser = wp.media.view.AttachmentsBrowser,
			UploaderInline = wp.media.view.UploaderInline;

		// Extend WordPress attachment browser
		wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend({
			initialize: function() {
				var attachments = this.controller.state().get( 'library' ),
					allAttachments = wp.media.model.Attachments.all;

				AttachmentsBrowser.prototype.initialize.apply( this, arguments );

				this.filterAttachmentsDebounced = _.debounce( this.filterAttachments, 10 );

				this.model.frame.on( 'open', function(){
					this.filterAttachmentsDebounced();
				}, this );

				this.controller.state().on( 'change:wickedSelectedFolder', function(){
					this.filterAttachmentsDebounced();
				}, this );

				this.createFolderPane();

				this.collection.props.on( 'change:query', this.filterAttachments, this );
				this.controller.state().get( 'library' ).on( 'reset', this.filterAttachments, this );
				this.controller.states.on( 'activate', this.filterAttachmentsDebounced, this );

				wp.media.model.Attachments.all.on( 'change:uploading', this.filterAttachmentsDebounced, this );

				// TODO: For some reason, calling filterAttachmentsDebounced
				// here is causing main 'select' button in media modal to be
				// disabled after upload
				wp.media.model.Attachments.all.on( 'add remove', this.filterAttachmentsDebounced, this );

				// Ensure the attachments that have been queried for the currently
				// active library are part of the allAttachments collection (which
				// may not include the current view's attachments yet)
				attachments.on( 'add', function( model ){
					allAttachments.add( model );
				}, this );

				wp.hooks.addAction( 'unassignFolders', 'wicked-plugins/wicked-folders', function( postIdsToMove ){
					var attachmentsToUpdate = allAttachments.filter( function( attachment ){
						return postIdsToMove.includes( attachment.id );
					} );		
					
					attachmentsToUpdate.map( function( attachment ){
						attachment.set( 'wickedFolders', [] );
					} );
				} );

				wp.hooks.addAction( 'movePostsToFolder', 'wicked-plugins/wicked-folders', function( postIdsToMove, toFolderId, fromFolderId, copy ){
					var attachmentsToUpdate = allAttachments.filter( function( attachment ){
						return postIdsToMove.includes( attachment.id );
					} );

					// Loop through attachments being moved
					attachmentsToUpdate.map( function( attachment ){
						// Get the attachent's current folders
						var folders = _.clone( attachment.get( 'wickedFolders' ) );

						// If we're not copying the attachment, remove
						// the attachment from the current folder
						if ( ! copy ) folders = _.without( folders, fromFolderId );

						// Add the destination folder
						folders = _.union( folders, [ toFolderId ] );

						// Update the attachment's folders
						attachment.set( 'wickedFolders', folders );
					});
				} );
			},

			createFolderPane: function(){
				var stateId = this.controller.state().id,
					folderPane = new AttachmentsBrowserFolderPane();

				this.views.add( folderPane );

				if ( 'gallery-edit' == stateId || 'dg-edit' == stateId ) {
					this.$el.removeClass( 'wicked-folder-pane-enabled' );
					this.$el.removeClass( 'wicked-folder-pane-expanded' );
				} else {
					this.$el.addClass( 'wicked-folder-pane-enabled' );

					//if ( this.folderPaneController.get( 'isFolderPaneVisible' ) ) {
						this.$el.addClass( 'wicked-folder-pane-expanded' );
					//}
				}		
				
				api.initMediaFolderPane( folderPane.el, this );
			},

			createSingle: function() {
				var sidebar = this.sidebar,
					single = this.options.selection.single();

				AttachmentsBrowser.prototype.createSingle.call( this );
return;

				sidebar.set( 'wicked-folders', new wickedfolders.views.AttachmentFolders({
					controller: 			this.controller,
					rerenderOnModelChange: 	true,
					model: 					single,
					priority:   			120,
					wickedFolders: 			folders,
					browser: 				this,
					showItemCount: 			this.controller.options.modal ? false : this.folderPaneController.get( 'showItemCount' )
				}) );

			},

			disposeSingle: function() {
				var sidebar = this.sidebar;

				AttachmentsBrowser.prototype.disposeSingle.call( this );

				sidebar.unset( 'wicked-folders' );
			},

			filterAttachments: function(){
				var state = this.controller.state(),
					toolbar = this.controller.views.first( '.media-frame-toolbar' ),
					attachments = this.controller.state().get( 'library' ),
					allAttachments = wp.media.model.Attachments.all,
					api = this.controller.wickedFoldersAPI,
					folder = api.getSelectedFolder(), //this.controller.state().get( 'wickedSelectedFolder' ),
					includeChildren = api.includeChildren,
					folders = [],
					remove = []
					add = [],
					year = false
					month = false;


				if ( ! folder ) return;

				// Don't filter gallery edit state
				if ( 'gallery-edit' == state.id ) return;

				// Don't filter Document Gallery edit gallery screen
				if ( 'dg-edit' == state.id ) return;

				if ( 'dg-library' == state.id ) {
					// The Document Gallery includes a validator that filters
					// out attachments that have already been added to the
					// gallery; however, this causes a lot of confusion,
					// especially when the folder counts don't match the number
					// of items shown due to the validator filtering out
					// attachments so delete the validator
					delete this.controller.state().get( 'library' ).validator;
				}

				// Only filter term folders (except for 'All Folders' and the unassinged dynamic folder)
				if ( 'Wicked_Folders\Term_Folder' != folder.type ) {
					if ( '0' != folder.id && 'unassigned_dynamic_folder' != folder.id ) return false;
				};

				// Not entirely clear on how the order filter works when filtering
				// by the native WordPress date filter but it prevents items that
				// have recently been drug to the folder from showing up so I'm
				// deleting it here. Logic has been added below to filter
				// attachments by date so that the date filter still works.
				// See Query.initialize for order filter function.

				// Update 10-12-2021: This is causing problems with the 'load
				// more' functionality added in WordPress 5.8. Unable to
				// reproduce the issue noted above so removing for now.
				//delete attachments.mirroring.filters.order;

				if ( ! _.isUndefined( attachments.mirroring.props ) ) {
					year = attachments.mirroring.props.get( 'year' );
					month = attachments.mirroring.props.get( 'monthnum' );
				}

				allAttachments.each( function( attachment ){
					// Respect the collection's filters (e.g. date, mime, etc.)
					var valid = true;
					if ( attachments.mirroring ) {
						valid = attachments.mirroring.validator( attachment );
					}
					// Since we deleted the order filter earlier, we need to
					// filter by date so the WordPress date filter still works
					if ( year && month ) {
						if ( ! ( year == attachment.get( 'date' ).getFullYear() && month == ( attachment.get( 'date' ).getMonth() + 1 ) ) ) {
							valid = false;
						}
					}
					// Include all items in 'all folders'
					if ( '0' == folder.id ) {
						if ( valid ) {
							add.push( attachment );
						} else {
							remove.push( attachment );
						}
					} else if ( 'unassigned_dynamic_folder' == folder.id ) {
						if ( _.size( attachment.get( 'wickedFolders' ) ) > 0  || ! valid ) {
							remove.push( attachment );
						} else {
							add.push( attachment );
						}
					} else {
						var folders = attachment.get( 'wickedFolders' );

						// If include children is enabled, check if attachment
						// is assigned to any child folder as well
						if ( includeChildren ) {
							if ( _.isArray( folders ) ) {
								if ( folders.length ) {
									var descendants = allFolders.descendantIds( folder.id ),
										result = _.intersection( folders, descendants );

									if ( result.length ) {
										folders = folders.concat( [ folder.id ] );
									}
								}
							}
						}

						if ( _.isArray( folders ) && folders.length ) {
							if ( -1 == folders.indexOf( folder.id ) ) {
								remove.push( attachment );
							} else if ( valid ) {
								add.push( attachment );
							}
						} else if ( attachment.get( 'uploading' ) ) {
							add.push( attachment );
						} else {
							remove.push( attachment );
						}
					}
				});

				attachments.remove( remove );
				attachments.add( add );

				// The media library only queries 40 attachments initially.  When
				// the page is loaded with a folder already selected and the user
				// navigates to another folder such as 'All Folders', the browser
				// doesn't query for more attachments automatically since it has
				// already been 'set up'.  Call 'more' here to fix that problem

				// Update 3-20-2021: can no longer seem to reproduce above issue
				// but a user has reported the media library continually loading
				// all items which is causing the browser to hang.  Removing
				// this statement for now
				//this.collection.more();

				// Refresh the frame's toolbar to ensure that the insert button
				// is in the correct state
				if ( toolbar ) toolbar.refresh();
			}
		});

		// Extend WordPress Attachments view
		wp.media.view.Attachments = Attachments.extend({
			initialize: function(){
				Attachments.prototype.initialize.apply( this, arguments );

				// TODO: determine if this impacts performance and, if so,
				// find a more efficient approach
				this.collection.on( 'add', this.initDragDrop, this );
			},

			render: function(){
				Attachments.prototype.render.apply( this, arguments );

				this.initDragDrop();
			},

			initDragDrop: function(){
				var view = this,
					collection = this.collection,
					orderby = collection.props.get('orderby'),
					sortingEnabled = 'menuOrder' === orderby || ! collection.comparator,
					folder = this.controller.state().get( 'wickedSelectedFolder' ),
					folderId = ( typeof folder == 'undefined' ) ? false : folder.id;

				// Don't interfere when sorting the attachment selection is enabled
				if ( sortingEnabled ) return;

				this.$( '.attachment' ).draggable( {
					revert: 'invalid',
					cursor: 'default',
					delay: 100,
					zIndex: 200,
					cursorAt: {
						top: -5,
						left: -5
					},
					helper: function( e ){
						var count 	= view.options.selection.length || 1,
							ids 	= 1 == count ? [ parseInt( $( e.currentTarget ).attr( 'data-id' ) ) ] : view.options.selection.models.pluck( 'id' ),
							api 	= view.controller.wickedFoldersAPI,
							helper 	= $( '<div id="wicked-drag-helper"></div>' );

						api.setPostIdsToMove( ids );						

						helper.appendTo( view.$el );

						api.renderDragHelper( count );

						return helper;
					},
					start: function(){
						view.$el.addClass( 'wicked-dragging-attachment' );
						view.$el.parent().addClass( 'wicked-dragging-attachment' );
					},
					stop: function(){
						view.$el.removeClass( 'wicked-dragging-attachment' );
						view.$el.parent().removeClass( 'wicked-dragging-attachment' );
					}
				} );

				/*
				if ( wickedfolders.util.isRtl() ) {
					this.$( '.attachment' ).draggable( 'option', {
						cursorAt: {
							right: -5
						}
					} );
				}
				*/
			}
		});

		// Extend WordPress inline uploader
		wp.media.view.UploaderInline = UploaderInline.extend({
			render: function(){

				UploaderInline.prototype.render.apply( this, arguments );
/*
				var folder = this.options.controller.state().get( 'wickedSelectedFolder' ),
					folderId = '0';

				if ( WickedFoldersProData.syncUploadFolderDropdown && ! _.isUndefined( folder ) ) {
					folderId = folder.id;
				}

				var folderSelect = new wickedfolders.views.UploaderFolderSelect({
					el: 						this.$( '#wicked-upload-folder' ),
					controller: 				this.options.controller,
					collection:					folders,
					defaultText:				wickedFoldersL10n.assignToFolder,
					selected:					folderId,
					syncUploadFolderDropdown: 	WickedFoldersProData.syncUploadFolderDropdown,
					hideUnassignable: 			true
				});

				folderSelect.render();
*/
			}
		});

		// TwoColumn is used by media grid view and isn't always loaded
		/*
		if ( wp.media.view.Attachment.Details.TwoColumn ) {
			var TwoColumn = wp.media.view.Attachment.Details.TwoColumn;

			wp.media.view.Attachment.Details.TwoColumn = TwoColumn.extend({
				render: function() {
					TwoColumn.prototype.render.apply( this, arguments );

					this.$( '.settings' ).append( '<div class="wicked-folders" />' );

					this.views.add( '.wicked-folders', new wickedfolders.views.AttachmentFolders({
						controller: 			this.controller,
						rerenderOnModelChange: 	true,
						model: 					this.model,
						priority:   			120,
						wickedFolders: 			folders,
						browser: 				this.controller.controller.browserView,
						showItemCount: 			folderPaneController.get( 'showItemCount' )
					}) );
				}
			});
		}
		*/

		// var frame = new wp.media.view.MediaFrame.Select({
		// 	// Modal title
		// 	title: 'Select profile background',

		// 	// Enable/disable multiple select
		// 	multiple: true,

		// 	// Library WordPress query arguments.
		// 	library: {
		// 		order: 'ASC',

		// 		// [ 'name', 'author', 'date', 'title', 'modified', 'uploadedTo',
		// 		// 'id', 'post__in', 'menuOrder' ]
		// 		orderby: 'title',

		// 		// mime type. e.g. 'image', 'image/jpeg'
		// 		//type: 'image',

		// 		// Searches the attachment title.
		// 		search: null,

		// 		// Attached to a specific post (ID).
		// 		uploadedTo: null
		// 	},

		// 	button: {
		// 		text: 'Set profile background'
		// 	}
		// });
		// frame.open();
	});
} )( jQuery );
