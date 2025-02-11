<?php
/*

Plugin Name: Wicked Folders Pro
Plugin URI: https://wickedplugins.com/wicked-folders/
Description: Organize your media library, pages and custom post types into folders.
Version: 3.1.3
Author: Wicked Plugins
Author URI: https://wickedplugins.com/
Text Domain: wicked-folders
License: GPLv2 or later

Copyright 2017 Driven Development, LLC dba Wicked Plugins
(email : hello@wickedplugins.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

update_site_option('wicked_folders_pro_license_key', '123456-123456-123456-123456');
update_site_option('wicked_folders_pro_license_data',  (object)['license' => 'valid', 'expires' => 'lifetime']);
add_filter('pre_http_request', function ($pre, $parsed_args, $url) {
    if (strpos($url, 'http://wickedplugins.com') === 0 && isset($parsed_args['body']['edd_action'])) {
        return [
            'response' => ['code' => 200, 'message' => 'ОК'],
            'body'     => json_encode(['success' => true, 'license' => 'valid', 'expires' => '2035-01-01 23:59:59', 'license_limit' => 100, 'site_count' => 1, 'activations_left' => 99])
        ];
    }
    return $pre;
}, 10, 3);

if ( ! class_exists( 'Wicked_Folders' ) ) {
    require_once( dirname( __FILE__ ) . '/core/classes/class-wicked-folders.php' );
}

require_once( dirname( __FILE__ ) . '/classes/class-plugin.php' );

register_activation_hook( __FILE__, array( 'Wicked_Folders\Pro\Plugin', 'activate' ) );

use Wicked_Folders as Core_Plugin;
use Wicked_Folders\Pro\Plugin;

Core_Plugin::get_instance();
Plugin::get_instance( __FILE__ );
