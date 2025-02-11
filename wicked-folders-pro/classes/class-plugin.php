<?php

namespace Wicked_Folders\Pro;

// Disable direct load
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

use Exception;
use Wicked_Folders;
use Wicked_Folders\Admin as Core_Admin;
use Wicked_Folders\Common;
use Wicked_Folders\Folder;

final class Plugin {

	private $stub_post_types = array();

    public static $plugin_file;

	private static $instance;

	private function __construct() {
		spl_autoload_register( array( $this, 'autoload' ) );

		// Class may exist if core plugin is active
		if ( ! class_exists( 'Wicked_Folders' ) ) {
			require_once( dirname( dirname( __FILE__ ) ) . '/core/wicked-folders.php' );
		}

		$core_admin 		= Core_Admin::get_instance();
		$pro_admin 			= Admin::get_instance();
		$permission_manager = Permission_Manager::get_instance();
		$post_types 		= Wicked_Folders::post_types();
		
		add_filter( 'init', 								array( $this, 'init' ) );
		add_filter( 'wicked_folders_taxonomies', 			array( $this, 'wicked_folders_taxonomies' ) );
		add_filter( 'wicked_folders_get_dynamic_folders', 	array( $this, 'wicked_folders_get_dynamic_folders' ), 10, 2 );
		add_filter( 'wicked_folders_post_type_objects', 	array( $this, 'wicked_folders_post_type_objects' ) );
		add_filter( 'wicked_folders_get_folders', 			array( $this, 'wicked_folders_get_folders' ), 10, 2 );
		add_filter( 'manage_admin_page_wf_attachment_folders_columns', 			array( $pro_admin, 'manage_media_columns' ) );
		add_filter( 'manage_admin_page_wf_attachment_folders_sortable_columns', array( $pro_admin, 'manage_upload_sortable_columns' ) );
		add_filter( 'rest_user_query', 						array( $this, 'rest_user_query' ), 10, 2 );
		add_filter( 'wicked_folders_build_directory_url', 	array( $this, 'override_core_build_directory_url' ) );
		add_filter( 'wicked_folders_settings_post_types', 	array( $this, 'wicked_folders_settings_post_types' ) );

		// WooCommerce filters
		add_filter( 'manage_product_posts_columns', 		array( $core_admin, 'manage_posts_columns' ), 20 );
		add_filter( 'manage_shop_coupon_posts_columns', 	array( $core_admin, 'manage_posts_columns' ), 20 );
		add_filter( 'manage_shop_order_posts_columns', 		array( $core_admin, 'manage_posts_columns' ), 20 );
		add_filter( 'manage_edit-wishlist_columns', 		array( $core_admin, 'manage_posts_columns' ), 20 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', 		array( $core_admin, 'manage_posts_columns' ), 20 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', 	array( $pro_admin, 'woocommerce_hpos_column_content' ), 10, 2 );
		add_filter( 'woocommerce_orders_table_query_clauses', 			array( $pro_admin, 'woocommerce_orders_table_query_clauses' ) );

		// Easy Digital Downloads
		add_filter( 'manage_edit-download_columns', 		array( $core_admin, 'manage_posts_columns' ), 20 );

		// ACF field groups
		add_filter( 'manage_edit-acf_columns', 				array( $core_admin, 'manage_posts_columns' ), 20 );
		add_filter( 'manage_edit-acf-field-group_columns', 	array( $core_admin, 'manage_posts_columns' ), 20 );

		add_filter( 'manage_media_columns', 				array( $pro_admin, 'manage_media_columns' ) );
        //add_filter( 'manage_media_page_wicked_attachment_folders_columns', array( $pro_admin, 'manage_media_columns' ) );

		// Divi Layouts support
		add_filter( 'et_builder_should_load_framework', 	array( $this, 'et_builder_should_load_framework' ) );

		// MailPoet support
		add_filter( 'mailpoet_conflict_resolver_whitelist_script', 	array( $this, 'mailpoet_conflict_resolver_whitelist_script' ) );
		add_filter( 'mailpoet_conflict_resolver_whitelist_style', 	array( $this, 'mailpoet_conflict_resolver_whitelist_style' ) );

		// MemberPress support
		add_filter( 'manage_edit-memberpressproduct_columns', array( $core_admin, 'manage_posts_columns' ) );

		add_action( 'set_object_terms', 					array( $this, 'set_object_terms' ), 10, 6 );
		add_action( 'wpml_after_duplicate_attachment', 		array( $this, 'wpml_after_duplicate_attachment' ), 10, 2 );

        // Work-around to get folders page to work for attachments
        if ( $core_admin::is_folders_page() && 'attachment' == $core_admin::folder_page_post_type() ) {
            $_GET['post_type'] = 'attachment';
        }

		foreach ( $post_types as $post_type ) {
			add_filter( 'rest_' . Wicked_Folders::get_tax_name( $post_type ) . '_query', array( $this, 'filter_rest_term_query_args' ), 10, 2 );
		}

		add_filter(
			'Wicked_Folders\Folder_Factory\get_folder',
			array( 'Wicked_Folders\Pro\Media_Extension_Dynamic_Folder', 'register_factory' ),
			10,
			4
		);

		if ( defined( 'RCP_PLUGIN_FILE' ) ) {
			$this->stub_post_types[] = new \Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Customer();
			$this->stub_post_types[] = new \Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Membership();
			$this->stub_post_types[] = new \Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Membership_Level();
		}

		if ( class_exists( '\Advanced_Form_Integration' ) ) {
			$this->stub_post_types[] = new \Wicked_Folders\Pro\Integrations\Advanced_Form_Integration\Integration();
		}

		new Wicked_Folders\Pro\Integrations\Everest_Forms\Everest_Forms();
		new Wicked_Folders\Pro\Integrations\Formidable_Forms\Formidable_Forms();
	}

	/**
	 * Plugin activation hook.
	 */
	public static function activate() {

		// Check for multisite
		if ( is_multisite() && is_plugin_active_for_network( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-folders-pro.php' ) ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $id ) {
				switch_to_blog( $id );
				self::get_instance()->activate_site();
				restore_current_blog();
			}
		} else {
			self::get_instance()->activate_site();
		}

	}

	/**
	 * Activates/initalizes plugin for a single site.
	 */
	public function activate_site() {

		$sync_upload_folder_dropdown = get_option( 'wicked_folders_sync_upload_folder_dropdown', null );

		// Wicked Folders is bundled with the pro version so deactivate
		// the core plugin
		deactivate_plugins( 'wicked-folders/wicked-folders.php' );

		// Activate the bundled core plugin
		Wicked_Folders::activate();

		$post_types 		= get_option( 'wicked_folders_post_types', array() );
        $dynamic_post_types = get_option( 'wicked_folders_dynamic_folder_post_types', array() );

        // Enable Media post type by default
        if ( ! in_array( 'attachment', $post_types ) ) {
            $post_types[] = 'attachment';
            update_option( 'wicked_folders_post_types', $post_types );
        }

		// Enable dynamic folders for media by default
		if ( empty( $dynamic_post_types ) && ! in_array( 'attachment', $dynamic_post_types ) ) {
            $dynamic_post_types[] = 'attachment';
            update_option( 'wicked_folders_dynamic_folder_post_types', $dynamic_post_types );
        }

		if ( null === $sync_upload_folder_dropdown ) {
			update_option( 'wicked_folders_sync_upload_folder_dropdown', true );
		}

	}

	public function init() {
		$this->register_post_types();
	}

	public function register_post_types() {
		// Register post type for folder collection security policies
		$args = array(
			'label'					=> _x( 'Folder Collection Policies', 'Post type plural name', 'wicked-folders' ),
			'labels'				=> array(
				'name'					=> _x( 'Folder Collection Policies', 'Post type plural name', 'wicked-folders' ),
				'singular_name'			=> _x( 'Folder Collection Policy', 'Post type singular name', 'wicked-folders' ),
				'add_new_item'			=> __( 'Add New Folder Collection Policy', 'wicked-folders' ),
				'edit_item'				=> __( 'Edit Folder Collection Policy', 'wicked-folders' ),
				'new_item'				=> __( 'New Folder Collection Policy', 'wicked-folders' ),
				'view_item'				=> __( 'View Folder Collection Policy', 'wicked-folders' ),
				'search_items'			=> __( 'Search Folder Collection Policies', 'wicked-folders' ),
				'not_found'				=> __( 'No folder collection policies found.', 'wicked-folders' ),
				'not_found_in_trash'	=> __( 'No folder collection policies found in trash.', 'wicked-folders' ),
			),
			'description'			=> __( 'Folder collection policies are used to control permissions for collections of folders. This post type is added by Wicked Folders Pro.', 'wicked-folders' ),
			'public'				=> false,
			'supports'				=> array( 'title' ),
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
			'show_ui' 				=> true,
			'show_in_menu' 			=> false,
		);

		register_post_type( Folder_Collection_Policy::get_post_type_name(), $args );

		// Just in case, allow registration of stub post types to be disabled
		$register_post_types = apply_filters( 'wicked_folders_register_stub_post_types', true );

		if ( ! $register_post_types ) return;

		// Register post type for users
		$labels = array(
			'name'					=> _x( 'Users', 'Post type plural name', 'wicked-folders' ),
			'singular_name'			=> _x( 'User', 'Post type singular name', 'wicked-folders' ),
			'add_new_item'			=> __( 'Add New User', 'wicked-folders' ),
			'edit_item'				=> __( 'Edit User', 'wicked-folders' ),
			'new_item'				=> __( 'New User', 'wicked-folders' ),
			'view_item'				=> __( 'View User', 'wicked-folders' ),
			'search_items'			=> __( 'Search Users', 'wicked-folders' ),
			'not_found'				=> __( 'No users found.', 'wicked-folders' ),
			'not_found_in_trash'	=> __( 'No users found in trash.', 'wicked-folders' ),
		);

		$args = array(
			'label'					=> _x( 'Users', 'Post type plural name', 'wicked-folders' ),
			'labels'				=> $labels,
			'description'			=> __( 'A post type to represent users for the purpose of organizing users into folders using the Wicked Folders plugin.', 'wicked-folders' ),
			'public'				=> false,
			'supports'				=> array(),
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
		);

		register_post_type( Wicked_Folders::get_user_post_type_name(), $args );

		// Register post type for plugins
		$labels = array(
			'name'					=> _x( 'Plugins', 'Post type plural name', 'wicked-folders' ),
			'singular_name'			=> _x( 'Plugin', 'Post type singular name', 'wicked-folders' ),
			'add_new_item'			=> __( 'Add New Plugin', 'wicked-folders' ),
			'edit_item'				=> __( 'Edit Plugin', 'wicked-folders' ),
			'new_item'				=> __( 'New Plugin', 'wicked-folders' ),
			'view_item'				=> __( 'View Plugin', 'wicked-folders' ),
			'search_items'			=> __( 'Search Plugins', 'wicked-folders' ),
			'not_found'				=> __( 'No plugins found.', 'wicked-folders' ),
			'not_found_in_trash'	=> __( 'No plugins found in trash.', 'wicked-folders' ),
		);

		$args = array(
			'label'					=> _x( 'Plugins', 'Post type plural name', 'wicked-folders' ),
			'labels'				=> $labels,
			'description'			=> __( 'A post type to represent plugins for the purpose of organizing plugins into folders using the Wicked Folders plugin.', 'wicked-folders' ),
			'public'				=> false,
			'supports'				=> array(),
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
		);

		register_post_type( Wicked_Folders::get_plugin_post_type_name(), $args );

		// Register post type for Gravity Forms forms
		$labels = array(
			'name'					=> _x( 'Gravity Forms Forms', 'Post type plural name', 'wicked-folders' ),
			'singular_name'			=> _x( 'Gravity Forms Form', 'Post type singular name', 'wicked-folders' ),
			'add_new_item'			=> __( 'Add New Form', 'wicked-folders' ),
			'edit_item'				=> __( 'Edit Form', 'wicked-folders' ),
			'new_item'				=> __( 'New Form', 'wicked-folders' ),
			'view_item'				=> __( 'View Form', 'wicked-folders' ),
			'search_items'			=> __( 'Search Forms', 'wicked-folders' ),
			'not_found'				=> __( 'No forms found.', 'wicked-folders' ),
			'not_found_in_trash'	=> __( 'No forms found in trash.', 'wicked-folders' ),
		);

		$args = array(
			'label'					=> _x( 'Gravity Forms Forms', 'Post type plural name', 'wicked-folders' ),
			'labels'				=> $labels,
			'description'			=> __( 'A post type to represent forms for the purpose of organizing Gravity Forms forms into folders using the Wicked Folders plugin.', 'wicked-folders' ),
			'public'				=> false,
			'supports'				=> array(),
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
		);

		register_post_type( Wicked_Folders::get_gravity_forms_form_post_type_name(), $args );

		// Register post type for Gravity Entrys entries
		$labels = array(
			'name'					=> _x( 'Gravity Forms Entries', 'Post type plural name', 'wicked-folders' ),
			'singular_name'			=> _x( 'Gravity Forms Entry', 'Post type singular name', 'wicked-folders' ),
			'add_new_item'			=> __( 'Add New Entry', 'wicked-folders' ),
			'edit_item'				=> __( 'Edit Entry', 'wicked-folders' ),
			'new_item'				=> __( 'New Entry', 'wicked-folders' ),
			'view_item'				=> __( 'View Entry', 'wicked-folders' ),
			'search_items'			=> __( 'Search Entries', 'wicked-folders' ),
			'not_found'				=> __( 'No entries found.', 'wicked-folders' ),
			'not_found_in_trash'	=> __( 'No entries found in trash.', 'wicked-folders' ),
		);

		$args = array(
			'label'					=> _x( 'Gravity Forms Entries', 'Post type plural name', 'wicked-folders' ),
			'labels'				=> $labels,
			'description'			=> __( 'A post type to represent entries for the purpose of organizing Gravity Forms entries into folders using the Wicked Folders plugin.', 'wicked-folders' ),
			'public'				=> false,
			'supports'				=> array(),
			'rewrite'				=> false,
			'show_in_rest' 			=> false,
		);

		register_post_type( Wicked_Folders::get_gravity_forms_entry_post_type_name(), $args );

		foreach ( $this->stub_post_types as $post_type ) {
			$post_type->register_post_type();
		}
	}

	public static function activate_license() {

		$api_url 		= trailingslashit( Common::wicked_plugins_url() ) . 'index.php';
		$plugin_data 	= get_plugin_data( Plugin::$plugin_file );
		$api_params 	= array(
			'edd_action' => 'activate_license',
			'license'    => get_site_option( 'wicked_folders_pro_license_key', false ),
			'item_name'  => urlencode( $plugin_data['Name'] ),
			'url'        => home_url()
		);

		$response = wp_remote_post( $api_url, array( 'body' => $api_params, 'timeout' => 10, 'sslverify' => false ) );

		// Make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			} else {
				throw new Exception( __( 'An error occurred while attempting to activate your license. Please try again.', 'wicked-folders' ) );
			}
		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {
					case 'expired' :
						$message = sprintf(
							__( 'License key expired on %s.' , 'wicked-folders' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;
					case 'revoked' :
						$message = __( 'License key has been disabled.', 'wicked-folders' );
						break;
					case 'missing' :
						$message = __( 'The license key entered is invalid.', 'wicked-folders' );
						break;
					case 'invalid' :
					case 'site_inactive' :
						$message = __( 'License key is not active for this URL.', 'wicked-folders' );
						break;
					case 'item_name_mismatch' :
						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wicked-folders' ), $plugin_data['Name'] );
						break;
					case 'no_activations_left':
						$message = __( 'Your license key has reached its activation limit.', 'wicked-folders' );
						break;
					default :
						$message = __( 'An error occurred while attempting to activate your license. Please try again.', 'wicked-folders' );
						break;
				}

				throw new Exception( $message );

			}
		}

		return true;

	}

	public static function fetch_license_data() {

		$api_url 		= trailingslashit( Common::wicked_plugins_url() ) . 'index.php';
		$plugin_data 	= get_plugin_data( Plugin::$plugin_file );
		$api_params 	= array(
			'edd_action' 	=> 'check_license',
			'license' 		=> get_site_option( 'wicked_folders_pro_license_key', false ),
			'item_name' 	=> $plugin_data['Name'],
			'url' 			=> home_url()
		);

		$response = wp_remote_post( $api_url, array( 'body' => $api_params, 'timeout' => 10, 'sslverify' => false ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );

	}

	/**
	 * Returns an HTML string with a friendly description of the plugin's
	 * license status. Used by the plugin's site and network settings pages.
	 */
	public static function get_license_status_text() {

		$license_data 		= get_site_option( 'wicked_folders_pro_license_data' );
		$license_status 	= '';

		if ( $license_data ) {
			if ( 'valid' == $license_data->license ) {
				$expiration = strtotime( $license_data->expires );
				if ( 'lifetime' == $license_data->expires ) {
					$license_status = __( 'Valid', 'wicked-folders' );
				} else if ( time() > $expiration ) {
					$license_status = __( 'Expired', 'wicked-folders' );
				} else {
					$license_status = sprintf( __( 'Valid. Expires %1$s.', 'wicked-folders' ), date( 'F j, Y', $expiration ) );
				}
			} else if ( 'expired' == $license_data->license ) {
				$license_status = __( 'Expired', 'wicked-folders' );
			} else {
				$license_status = __( 'Invalid', 'wicked-folders' );
			}
		}

		return $license_status;

	}

	public static function is_license_expired() {
		$expired 		= false;
		$license_data 	= get_site_option( 'wicked_folders_pro_license_data' );

		if ( $license_data ) {
			// We need to check even when the license is valid because it may
			// have expired since the license data was last updated
			if ( 'valid' == $license_data->license ) {
				$expiration = strtotime( $license_data->expires );

				if ( 'lifetime' != $license_data->expires && time() > $expiration ) {
					$expired = true;
				}
			} else if ( 'expired' == $license_data->license ) {
				$expired = true;
			}
		}

		return $expired;
	}

    public static function autoload( $class ) {

        $file 	= false;
        $files  = array(
            'Wicked_Folders\Pro\Admin'       				                        => 'classes/class-admin.php',
			'Wicked_Folders\Pro\Media_Extension_Dynamic_Folder'                     => 'classes/class-media-extension-dynamic-folder.php',
			'Wicked_Folders\Pro\Folder_Collection_Policy' 	                        => 'classes/class-folder-collection-policy.php',
			'Wicked_Folders\Pro\Permission_Policy' 				                    => 'classes/class-permission-policy.php',
			'Wicked_Folders\Pro\Permission_Policy_Collection'                       => 'classes/class-permission-policy-collection.php',
			'Wicked_Folders\Pro\Permission_Manager' 		                        => 'classes/class-permission-manager.php',
			'Wicked_Folders\Pro\Stub_Post_Type' 		    				        => 'classes/class-stub-post-type.php',
			'Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Customer'         => 'integrations/restrict-content-pro/class-customer.php',
			'Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Membership'   	=> 'integrations/restrict-content-pro/class-membership.php',
			'Wicked_Folders\Pro\Integrations\Restrict_Content_Pro\Membership_Level' => 'integrations/restrict-content-pro/class-membership-level.php',
			'Wicked_Folders\Pro\Integrations\Advanced_Form_Integration\Integration' => 'integrations/advanced-form-integration/class-integration.php',
			'Wicked_Folders\Pro\Integrations\Everest_Forms\Everest_Forms' 			=> 'integrations/everest-forms/class-everest-forms.php',
			'Wicked_Folders\Pro\Integrations\Formidable_Forms\Formidable_Forms' 	=> 'integrations/formidable-forms/class-formidable-forms.php',
			'Wicked_Folders\Pro\EDD_SL_Plugin_Updater'                              => 'vendor/edd/EDD_SL_Plugin_Updater.php',
        );

        if ( array_key_exists( $class, $files ) ) {
            $file = dirname( dirname( __FILE__ ) ) . '/' . $files[ $class ];
        }

        if ( $file ) {
            $file = str_replace( '/', DIRECTORY_SEPARATOR, $file );
            include_once( $file );
        }

	}

    public static function get_instance( $plugin_file = false ) {

		if ( empty( self::$instance ) ) {
			self::$instance = new Plugin();
		}

		if ( $plugin_file ) {
			self::$plugin_file = $plugin_file;
		}

		return self::$instance;

	}

	/**
	 * Hooks into core plugin's taxonomy filter to include attachments.
	 */
	public function wicked_folders_taxonomies( $taxonomies ) {
		$post_types = Wicked_Folders::post_types();

		if ( in_array( 'attachment', $post_types ) ) {
			$taxonomies[] = 'wf_attachment_folders';
		}

		return $taxonomies;
	}

	/**
	 * wicked_folders_get_dynamic_folders filter.
	 */
	public function wicked_folders_get_dynamic_folders( $dynamic_folders, $args ) {

		$post_type 	= $args['post_type'];
		$taxonomy 	= $args['taxonomy'];

		if ( 'attachment' == $post_type ) {
			$extension_folders = $this->get_media_extension_dynamic_folders( $post_type, $taxonomy );

			$dynamic_folders = array_merge( $dynamic_folders, $extension_folders );
		}

		if ( Wicked_Folders::get_user_post_type_name() == $post_type ) {
			$user_role_folders = $this->get_user_role_dynamic_folders();

			$dynamic_folders = array_merge( $dynamic_folders, $user_role_folders );
		}

		return $dynamic_folders;

	}

	/**
	 * Returns a dynamically generated collection of folders for all media file
	 * extensions (e.g. .jpg, .pdf, etc.).
	 *
	 * @param string $post_type
	 *  The post type to generate folders for.
	 *
	 * @return array
	 *  Array of Media_Extension_Dynamic_Folder objects.
	 */
	public function get_media_extension_dynamic_folders( $post_type, $taxonomy ) {
		global $wpdb;

		$extensions = array();
		$folders 	= array();

		// Fetch post dates
		$results = $wpdb->get_results( "SELECT pm.meta_value FROM {$wpdb->prefix}posts p INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'attachment' AND pm.meta_key = '_wp_attached_file' ORDER BY pm.meta_value ASC" );

		foreach ( $results as $row ) {
			$a = explode( '.', $row->meta_value );
			$extension = end( $a );
			$extensions[] = strtolower( $extension );
		}

		$extensions = array_unique( $extensions );

		asort( $extensions );

		if ( ! empty( $extensions ) ) {

			$folders[] = new Media_Extension_Dynamic_Folder( array(
					'id' 		=> 'dynamic_media_extension',
					'name' 		=> __( 'All Extensions', 'wicked-folders' ),
					'parent' 	=> 'dynamic_root',
					'post_type' => $post_type,
					'taxonomy' 	=> $taxonomy,
				)
			);

			foreach ( $extensions as $extension ) {

				$folders[] = new Media_Extension_Dynamic_Folder( array(
						'id' 		=> 'dynamic_media_extension_' . $extension,
						'name' 		=> '.' . $extension,
						'parent' 	=> 'dynamic_media_extension',
						'post_type' => $post_type,
						'taxonomy' 	=> $taxonomy,
					)
				);

			}

		}

		return $folders;

	}

	/**
	 * Returns folders for user roles.
	 *
	 * @return array
	 *  Array of Wicked_Folders\Folder objects.
	 */
	public function get_user_role_dynamic_folders() {
		global $wp_roles;

		$folders 	= array();
		$post_type 	= Wicked_Folders::get_user_post_type_name();
		$taxonomy 	= Wicked_Folders::get_tax_name( $post_type );

		$folders[] = new Folder( array(
			'id' 		=> 'dynamic_user_role',
			'name' 		=> __( 'All Roles', 'wicked-folders' ),
			'parent' 	=> 'dynamic_root',
			'taxonomy' 	=> $taxonomy,
			'post_type' => $post_type,
		) );

		foreach ( $wp_roles->roles as $role_id => $role ) {
			$folders[] = new Folder( array(
				'id' 		=> 'dynamic_user_role_' . $role_id,
				'name' 		=> $role['name'],
				'parent' 	=> 'dynamic_user_role',
				'taxonomy' 	=> $taxonomy,
				'post_type' => $post_type,
			) );
		}

		return apply_filters( 'wicked_folders_user_role_dynamic_folders', $folders );
	}

	/**
	 * Returns the plugin's version.
	 */
	public static function plugin_version() {
		static $version = false;

		if ( ! $version && function_exists( 'get_plugin_data' ) ) {
			$plugin_data 	= get_plugin_data( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'wicked-folders-pro.php' );
			$version 		= $plugin_data['Version'];
		}

		return $version;

	}

	/**
	 * Prints Javascript templates for media.
	 */
	public static function print_media_templates() {
		static $done = false;

		// Ensure the templates are only printed once per request
		if ( $done ) return;
		?>
			<script type="text/template" id="tmpl-wicked-attachment-browser-folder-pane">
				<div class="wicked-resizer">
			        <div class="wicked-splitter-handle ui-resizable-handle ui-resizable-e ui-resizable-w">
			        </div>
			    </div>
			    <div class="wicked-content">
			        <div class="wicked-title"><?php _e( 'Folders', 'wicked-folders' ); ?></div>
			        <div class="wicked-toolbar-container"></div>
					<div class="wicked-folder-details-container"></div>
			        <div class="wicked-folder-pane-settings-container"></div>
			        <div class="wicked-folder-tree-container">
						<?php if ( get_option( 'wicked_folders_show_folder_search', true ) ) : ?>
							<div class="wicked-folder-search-container">
								<div class="wicked-folder-search">
									<label for="wicked-folder-search-input" class="screen-reader-text">Search folders</label>
									<input id="wicked-folder-search-input" name="wicked_folder_search" type="text" value="" placeholder="<?php _e( 'Search folders...', 'wicked-folders' ); ?>" />
								</div>
							</div>
						<?php endif; ?>
					</div>
			    </div>
			</script>
			<script type="text/template" id="tmpl-wicked-attachment-browser-drag-details">
				<div class="title">
					<?php _e( 'Move', 'wicked-folders' ); ?> {{ data.count }}
					<# if ( 1 == data.count ) { #>
						<?php _e( 'File', 'wicked-folders' ); ?>
					<# } else { #>
						<?php _e( 'Files', 'wicked-folders' ); ?>
					<# } #>
				</div>
				<# if ( data.enableCopy) { #>
					<?php _e( 'Hold SHIFT key to copy file(s)', 'wicked-folders' ); ?>
				<# } #>
			</script>
			<script type="text/template" id="tmpl-wicked-attachment-browser-folder-details">
				<header>
					<h2>{{ data.title }}</h2>
					<span class="wicked-spinner"></span>
					<a class="wicked-close" href="#" title="<?php _e( 'Close', 'wicked-folders' ); ?>"><span class="screen-reader-text"><?php _e( 'Close', 'wicked-folders' ); ?></span></a>
				</header>
				<div>
	                <div class="wicked-messages wicked-errors"></div>
	                <# if ( 'delete' == data.mode ) { #>
	                    <p>{{ data.deleteFolderConfirmation }}</p>
	                <# } else { #>
	                    <div class="wicked-folder-name"><input type="text" name="wicked_folder_name" placeholder="<?php _e( 'Folder name', 'wicked-folders' ); ?>" value="{{ data.folderName }}" /></div>
	                    <div class="wicked-folder-parent"></div>
	                <# } #>
					<# if ( 'edit' == data.mode ) { #>
						<fieldset>
			                <legend>
			                    {{ data.cloneFolderLink }}
			                    <span class="dashicons dashicons-editor-help" title="{{ data.cloneFolderTooltip }}"></span>
			                </legend>
			                <p>
			                    <label>
			                        <input type="checkbox" name="wicked_clone_children" />
			                        {{ data.cloneChildFolders }}
			                    </label>
								<span class="dashicons dashicons-editor-help" title="{{ data.cloneChildFoldersTooltip }}"></span>
			                </p>
			                <p><a class="button wicked-clone-folder" href="#">{{ data.cloneFolderLink }}</a></p>
			            </fieldset>
						<p class="wicked-folder-owner">
							<label for="wicked-folder-owner-id">{{ data.ownerLabel }}</label>
							<select id="wicked-folder-owner-id" name="wicked_folder_owner_id">
								<# if ( data.ownerId ) { #>
									<option value="{{ data.ownerId }}" selected="selected">{{ data.ownerName }}</option>
								<# } #>
							</select>
						</p>
					<# } #>
	            </div>
	            <footer>
	                <a class="button wicked-cancel" href="#"><?php _e( 'Cancel', 'wicked-folders' ); ?></a>
	                <button class="button-primary wicked-save" type="submit">{{ data.saveButtonLabel }}</button>
	            </footer>
		    </script>
			<script type="text/template" id="tmpl-wicked-folder-pane-settings">
			    <header>
			        <h2><?php _e( 'Settings', 'wicked-folders' ); ?></h2>
			        <span class="wicked-spinner"></span>
			        <a class="wicked-close" href="#" title="<?php _e( 'Close', 'wicked-folders' ); ?>"><span class="screen-reader-text"><?php _e( 'Close', 'wicked-folders' ); ?></span></a>
			    </header>
				<div>
			        <div class="wicked-field">
			            <div class="wicked-field-label">
			                <?php _e( 'Organization mode:', 'wicked-folders' ); ?>
			                <span class="dashicons dashicons-editor-help" title="<?php _e( "Controls what happens when you drag and drop folders. Use 'Normal' to arrange your folder hierarchy by dragging and dropping folders into other folders. Use 'Sort' to change the order of the folders.", 'wicked-folders' ); ?>"></span>
			            </div>
			            <div class="wicked-field-options">
			                <div>
			                    <label>
			                        <input type="radio" name="wicked_organization_mode" value="organize" <# if ( 'organize' == data.mode ) { #>checked<# } #> />
			                        <?php _e( 'Normal', 'wicked-folders' ); ?>
			                    </label>
			                </div>
			                <div>
			                    <label>
			                        <input type="radio" name="wicked_organization_mode" value="sort" <# if ( 'sort' == data.mode ) { #>checked<# } #> />
			                        <?php _e( 'Sort', 'wicked-folders' ); ?>
			                    </label>
			                </div>
			            </div>
			        </div>
			        <div class="wicked-field">
			            <div class="wicked-field-label">
			                <?php _e( 'Folder sort order:', 'wicked-folders' ); ?>
			                <span class="dashicons dashicons-editor-help" title="<?php _e( "Controls how your folders are sorted. Select 'Custom' to display the folders in the specific order you specify.", 'wicked-folders' ); ?>"></span>
			            </div>
			            <div class="wicked-field-options">
			                <div>
			                    <label>
			                        <input type="radio" name="wicked_sort_mode" value="alpha" <# if ( 'alpha' == data.sortMode ) { #>checked<# } #> />
			                        <?php _e( 'Alphabetical', 'wicked-folders' ); ?>
			                    </label>
			                </div>
			                <div>
			                    <label>
			                        <input type="radio" name="wicked_sort_mode" value="custom" <# if ( 'custom' == data.sortMode ) { #>checked<# } #> />
			                        <?php _e( 'Custom', 'wicked-folders' ); ?>
			                    </label>
			                </div>
			            </div>
			        </div>
			    </div>
			</script>
			<script type="text/template" id="tmpl-wicked-folders-notification">
			    <div class="wicked-notification-message">
			        <div class="wicked-notification-title">{{ data.title }}</div>
			        {{ data.message }}
			    </div>
			    <# if ( data.dismissible ) { #>
			        <button class="wicked-dismiss" type="button">
			            <span class="screen-reader-text">Close</span>
			        </button>
			    <# } #>
			</script>
			<div id="wicked-folders-select2-dropdown"></div>
		<?php

		$done = true;
	}

	/**
	 * Divi only loads framework when needed.  This filter is used to load the
	 * framework on the Wicked Folders settings page to get Divi to register the
	 * 'Layouts' post type so that it can be selected from the list of post types
	 * to enable folders for.
	 */
	public function et_builder_should_load_framework( $should_load ) {
		global $pagenow;

		if ( isset( $pagenow ) && 'options-general.php' == $pagenow && isset( $_GET['page'] ) && 'wicked_folders_settings' == $_GET['page'] ) {
			$should_load = true;
		}

		return $should_load;
	}

	/**
	 * MailPoet filter
	 */
	public function mailpoet_conflict_resolver_whitelist_script( $permitted_asset_locations ) {
		$permitted_asset_locations[] = 'wicked-folders';
		$permitted_asset_locations[] = 'wicked-folders-pro';

		return $permitted_asset_locations;
	}

	/**
	 * MailPoet filter
	 */
	public function mailpoet_conflict_resolver_whitelist_style( $permitted_asset_locations ) {
		$permitted_asset_locations[] = 'wicked-folders';
		$permitted_asset_locations[] = 'wicked-folders-pro';

		return $permitted_asset_locations;
	}

	/**
	 * Hooks into the 'wicked_folders_post_type_objects' filter in the core plugin.
	 */
	public function wicked_folders_post_type_objects( $post_types ) {
		$enabled_post_types 	= Wicked_Folders::post_types();
		$user_post_type 		= Wicked_Folders::get_user_post_type_name();
		$plugin_post_type 		= Wicked_Folders::get_plugin_post_type_name();
		$gform_form_post_type 	= Wicked_Folders::get_gravity_forms_form_post_type_name();
		$gform_entry_post_type 	= Wicked_Folders::get_gravity_forms_entry_post_type_name();

		if ( in_array( $user_post_type, $enabled_post_types ) ) {
			$post_types[] = get_post_type_object( $user_post_type );
		}

		if ( in_array( $plugin_post_type, $enabled_post_types ) ) {
			$post_types[] = get_post_type_object( $plugin_post_type );
		}

		if ( in_array( $gform_form_post_type, $enabled_post_types ) ) {
			$post_types[] = get_post_type_object( $gform_form_post_type );
		}

		if ( in_array( $gform_entry_post_type, $enabled_post_types ) ) {
			$post_types[] = get_post_type_object( $gform_entry_post_type );
		}

		foreach ( $this->stub_post_types as $post_type ) {
			if ( in_array( $post_type->post_type_name, $enabled_post_types ) ) {
				$post_types[] = get_post_type_object( $post_type->post_type_name );
			}
		}

		return $post_types;
	}

	/**
	 * WordPress 'set_object_terms' action.
	 */
	public function set_object_terms( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
		// Keep Gravity Forms entry meta in sync with terms
		$entry_post_type 	= Wicked_Folders::get_gravity_forms_entry_post_type_name();
		$entry_taxonomy 	= Wicked_Folders::get_tax_name( $entry_post_type );

		if ( Wicked_Folders::enabled_for( $entry_post_type ) ) {
			// Just in case...make sure function actualy exists so we don't
			// cause an error later
			if ( function_exists( 'gform_update_meta' ) ) {
				if ( $taxonomy == $entry_taxonomy ) {
					$prefixed_ids 	= array();
					$folder_ids 	= wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );

					// Prefix the folder IDs so that we can safley fetch entries
					// by folder ID using Gravity Forms' API
					foreach ( $folder_ids as $folder_id ) {
						$prefixed_ids[] = 'f-' . $folder_id;
					}

					gform_update_meta( $object_id, 'wf_folders', $prefixed_ids );

					if ( empty( $prefixed_ids ) ) {
						gform_update_meta( $object_id, 'wf_has_folders', 'false' );
					} else {
						gform_update_meta( $object_id, 'wf_has_folders', 'true' );
					}
				}
			}
		}
	}

	/**
	 * Filters the folders returned by the core plugin.
	 */
	public function wicked_folders_get_folders( $folders, $args ) {
		$taxonomy 	= $args['taxonomy'];
		$policy 	= Folder_Collection_Policy::get_taxonomy_policy( $taxonomy );
		$user_id 	= get_current_user_id();

		// If the taxonomy has a policy, apply it to the folders
		if ( $policy ) {
			for ( $i = count( $folders ) - 1; $i > -1; $i-- ) {
				$folder = $folders[ $i ];

				if ( is_a( $folder, 'Wicked_Folders\Term_Folder' ) ) {
					$folders[ $i ]->editable 	= $policy->can_edit( $folder->id, $user_id );
					$folders[ $i ]->deletable 	= $policy->can_delete( $folder->id, $user_id );
					$folders[ $i ]->assignable 	= $policy->can_assign( $folder->id, $user_id );

					// Remove the folder from the collection if viewing is not
					// allowed
					if ( ! $policy->can_view( $folder->id, $user_id ) ) {
						unset( $folders[ $i ] );
					}
				}
			}
		}

		return $folders;
	}

	/**
	 * Filters terms when queried from the rest API to only include folders that
	 * are viewable.
	 */
	public function filter_rest_term_query_args( $args, $request ) {
		$user_id 	= get_current_user_id();
		$policy 	= Folder_Collection_Policy::get_taxonomy_policy( $args['taxonomy'] );

		if ( $policy ) {
			$ids = get_terms( array(
				'taxonomy' 		=> $args['taxonomy'],
				'hide_empty' 	=> false,
				'fields' 		=> 'ids',
			) );

			foreach ( $ids as $id ) {
				if ( ! $policy->can_view( $id, $user_id ) ) {
					$args['exclude'][] = $id;
				}
			}
		}

		return $args;
	}

	/**
	 * WordPress 'rest_user_query' filter.  By default, WordPress only includes
	 * users who have published posts.  Checks the request params and removes
	 * the published post requirement if needed so anyone can be selected from
	 * the folder owner dropdown.
	 */
	public function rest_user_query( $args, $request ) {
		if ( 'true' == $request->get_param( 'wf_include_users_without_posts' ) ) {
			 unset( $args['has_published_posts'] );
		}

		return $args;
	}

	/**
	 * This action is run after WPML duplicates an attachment.  Copy the folder
	 * assignments from the source attachment over to the duplicated
	 * attachment.
	 */
	public function wpml_after_duplicate_attachment( $attachment_id, $duplicated_attachment_id ) {
		try {
			$taxonomy 	= 'wf_attachment_folders';
			$folder_ids = wp_get_object_terms( $attachment_id, $taxonomy, array(
			    'fields' => 'ids',
			) );

			$result = wp_set_object_terms( $duplicated_attachment_id, $folder_ids, $taxonomy, true );

			if ( is_wp_error( $result ) ) {
				throw new Exception( $result->get_error_message() );
			}
		} catch ( Exception $e ) {
			error_log(
				sprintf(
					__( 'Error duplicating attachment to folders: %s', 'wicked-folders' ),
            		$e->getMessage()
				)
			);
		}
	}

	/**
	 * Changes the build directory path used by the core plugin to the pro plugin's
	 * build directory.
	 */
	public function override_core_build_directory_url( $url ) {
		return plugin_dir_url( dirname( __FILE__ ) ) . 'dist';
	}

	/**
	 * Adds stub post types to the list of post types that the core plugin displays
	 * on the settings page.
	 */
	public function wicked_folders_settings_post_types( $post_types ) {
		foreach ( $this->stub_post_types as $type ) {
			$post_types[] = $type->get_post_type();
		}

		return $post_types;
	}
}
