<?php
/**
 * Mythic WP Management
 *
 * @package     mythic-wp-management
 * @author      Mythic Beasts
 * @copyright   2024 Mythic Beasts
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Mythic Beasts WordPress Management
 * Description: This plugin enables data collection as part of the Mythic Beasts Managed WordPress Hosting service, and provides relevant notices.
 * Version:     1.7.0
 * Author:      Mythic Beasts
 * Author URI:  https://www.mythic-beasts.com
 * License:     GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * Prevent direct access.
 */

if(!defined('ABSPATH')) {
    die;
}


/**
 * Disable larger core upgrades, but allow security.
 */

if( !defined( 'WP_AUTO_UPDATE_CORE' )) {
    define( 'WP_AUTO_UPDATE_CORE', 'minor' );
}


/**
 * Disable automatic plugin and theme upgrade functionality.
 */

add_filter( 'plugins_auto_update_enabled', '__return_false' );
add_filter( 'themes_auto_update_enabled', '__return_false' );


/**
 * Activate the plugin and generate a new key.
 */

function mythic_wp_management_activate() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < 32; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    update_option( 'mythic-wp-key', $randomString );
    update_option( 'mythic-wp-last_query', 0);
    update_option( 'mythic-wp-last_cron', time());
}

register_activation_hook( __FILE__, 'mythic_wp_management_activate' );


/**
 * Cleanup when deactivating the plugin.
 */

function mythic_wp_management_deactivate() {
    delete_option( 'mythic-wp-key' );
    delete_option( 'mythic-wp-last_query' );
    delete_option( 'mythic-wp-last_cron' );
    if ( wp_next_scheduled( 'mythic_wp_last_cron_check' )) {
        wp_unschedule_event(wp_next_scheduled( 'mythic_wp_last_cron_check' ), 'mythic_wp_last_cron_check' );
    }
}

register_deactivation_hook( __FILE__, 'mythic_wp_management_deactivate' );


/**
 * wp-cron job to update mythic-wp-last_cron option periodically
 */

if ( ! wp_next_scheduled( 'mythic_wp_last_cron_check' )) {
    wp_schedule_event( time(), 'hourly', 'mythic_wp_last_cron_check' );
}

add_action( 'mythic_wp_last_cron_check', 'mythic_wp_last_cron_update' );

function mythic_wp_last_cron_update() {
    update_option( 'mythic-wp-last_cron', time());
}


/**
 * List files matching a specific pattern optionally non-recursively.
 */

function mythic_wp_management_list_matching_files($dir, $pattern, $recursive, &$results = array() ) {

    if (!is_dir($dir)) { return false; }

    $files = scandir($dir);

    foreach ($files as $key => $value) {
        $path = realpath($dir . '/' . $value);
        if (!is_dir($path)) {
            $results[] = $path;
        } else if ($value != "." && $value != ".." && $recursive ) {
            mythic_wp_management_list_matching_files($path, $pattern, $recursive, $results );
        }
    }

    # only return the results which match the pattern, with a relative path
    return str_replace( "$dir/", '', preg_grep( $pattern, $results ));
}

/**
 * Generate hashes for specific files in a directory, optionally non-recursively.
 */

function mythic_wp_management_md5_matching_files($dir, $pattern, $recursive = true ) {

    if (!is_dir($dir)) { return false; }

    foreach ( array_values( mythic_wp_management_list_matching_files($dir, $pattern, $recursive) ) as $file ) {
        if ( $recursive ) {
            $result[$file] = hash_file ("md5", $dir . '/' . $file);
        } else {
            $result[$file] = hash_file ("md5", $file);
        }
    }
    return $result;
}


/**
 * Report the relevant diagnostic information.
 */

function mythic_wp_management_report() {
    // Lots of escaping here to be sure.

    // Report header - with report version number for backward compatibility when processing result.
    echo esc_html( "BEGIN_REPORT\tmythic-wp\t2" . PHP_EOL );

    // IP Address reported by PHP
    // Sanitise the SERVER_ADDR, and remove anything that isn't valid for an IPv4 or IPv6 address.
    $server_addr = preg_replace( '/[^a-f0-9\.:]/', '', $_SERVER['SERVER_ADDR'] );
    echo esc_html( "SERVER_ADDR\t" . $server_addr . PHP_EOL );

    // Get the document root the site in running in.
    $server_docroot = rtrim( $_SERVER['DOCUMENT_ROOT'], '/' );
    echo esc_html( "SERVER_DOCROOT\t" . $server_docroot . PHP_EOL );

    // Output the Database Info
    echo esc_html( "DB_HOST\t" . DB_HOST . PHP_EOL );
    echo esc_html( "DB_NAME\t" . DB_NAME . PHP_EOL );
    echo esc_html( "DB_USER\t" . DB_USER . PHP_EOL );

    // WordPress Install path, excluding trailing slash
    $wp_path = rtrim( ABSPATH, '/' );
    echo esc_html( "WP_ABSPATH\t" . $wp_path . PHP_EOL );

    // Instance ID to verify running location
    $instance = '[' . $server_addr . ']:' . $wp_path . "\n" ;
    echo esc_html( "INSTANCE_ID\t" . md5( $instance ) . PHP_EOL );

    // PHP Executing Username, UID, GID
    $pwdinfo = posix_getpwuid(posix_geteuid());
    echo esc_html( "USER_UID_GID\t" . $pwdinfo['name'] . "\t" . $pwdinfo['uid'] . "\t" . $pwdinfo['gid'] . PHP_EOL );

    // Owner information for the WordPress directory
    echo esc_html( "WP_DIR_OWNER\t" . posix_getpwuid(fileowner($wp_path))['name'] . "\t" . fileowner($wp_path) . "\t" . filegroup($wp_path) . PHP_EOL );

    // PHP Version and Running method.
    echo esc_html( "PHP_VERSION\t" . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . "\t" . PHP_SAPI . PHP_EOL );

    // List of available PHP Extensions.
    echo esc_html( "PHP_EXTENSIONS\t" ) . json_encode(get_loaded_extensions()) . esc_html( PHP_EOL );

    // Calculate the max upload size.
    echo esc_html( "PHP_UPLOAD\t" . wp_max_upload_size() . PHP_EOL ); 

    // Memory limits.
    echo esc_html( "PHP_MEMLIMIT\t" . ini_get('memory_limit') . PHP_EOL );

    echo esc_html( "WP_MEMLIMIT\t");
    if ( defined( 'WP_MEMORY_LIMIT' ) && ( WP_MEMORY_LIMIT != "" ) ) {
        echo esc_html( WP_MEMORY_LIMIT );
    } else {
        echo esc_html( 'unset');
    }
    if ( defined( 'WP_MAX_MEMORY_LIMIT' ) && ( WP_MAX_MEMORY_LIMIT != "" ) ) {
        echo esc_html( "\t" . WP_MAX_MEMORY_LIMIT . PHP_EOL);
    } else {
        echo esc_html( '\tunset' . PHP_EOL );
    }

    // PHP Uname Result
    echo esc_html( "UNAME\t" . php_uname() . PHP_EOL );

    // Various paths, URLs and switches.
    global $wp_version;
    echo esc_html( "WP_CORE_VERSION\t" . $wp_version . PHP_EOL );
    echo esc_html( "WP_CORE_LANG\t" . get_bloginfo("language") . PHP_EOL );
    echo esc_html( "BLOG_NAME\t" . get_bloginfo( 'name' ) . PHP_EOL );
    echo esc_html( "WP_HOME_URL\t" . get_home_url() . PHP_EOL );
    echo esc_html( "WP_SITE_URL\t" . get_site_url() . PHP_EOL );
    echo esc_html( "ADMIN_URL\t" . admin_url() . PHP_EOL );
    echo esc_html( "WP_CONTENT_URL\t" . WP_CONTENT_URL . PHP_EOL );
    echo esc_html( "WP_PLUGIN_URL\t" . WP_PLUGIN_URL . PHP_EOL );
    echo esc_html( "WP_CONTENT_DIR\t" . WP_CONTENT_DIR . PHP_EOL );  // no trailing slash, full paths only
    echo esc_html( "WP_PLUGIN_DIR\t" . WP_PLUGIN_DIR . PHP_EOL ); // full path, no trailing slash
    echo esc_html( "WP_THEME_DIR\t" . get_theme_root() . PHP_EOL ); // full path, no trailing slash
    $upload_dir = wp_upload_dir()["basedir"];
    echo esc_html( "WP_UPLOAD_DIR\t" . $upload_dir . PHP_EOL );

    if ( defined( 'WP_DEBUG' ) && ( WP_DEBUG != "" ) ) {
        echo esc_html( "WP_DEBUG\t" . WP_DEBUG . PHP_EOL );
    } else {
        echo esc_html( "WP_DEBUG\tunset" . PHP_EOL );
    }

    if ( defined( 'DISABLE_WP_CRON' ) && ( DISABLE_WP_CRON != "" ) ) {
        echo esc_html( "WP_CRON\t" . get_option("mythic-wp-last_cron") . "\t" . DISABLE_WP_CRON . PHP_EOL );
    } else {
        echo esc_html( "WP_CRON\t" . get_option("mythic-wp-last_cron") . "\tdefault" . PHP_EOL );
    }

    // WP_AUTO_UPDATE_CORE, to prevent automatic core updates.
    if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
        echo esc_html( "WP_UPDATE_CORE\t" . WP_AUTO_UPDATE_CORE . PHP_EOL );
    } else {
        echo esc_html( "WP_UPDATE_CORE\tunset" . PHP_EOL );
    }


    // Admin email for email notifications on updates.
    echo esc_html( "ADMIN_EMAIL\t" . get_option( 'admin_email' ) . PHP_EOL );


    // New user settings.
    echo esc_html( "USER_REGISTER\t" . get_option( 'users_can_register' ) . PHP_EOL );
    echo esc_html( "USER_DEFAULT\t" . get_option( 'default_role' ) . PHP_EOL );


    // User Overview Data - list of roles, and count of users with that role
    $user_roles = array();
    foreach( count_users()['avail_roles'] as $role => $count ) {
        $user_roles[$role] = $count;
    }
    echo esc_html( "USER_ROLES\t"). strip_tags(json_encode($user_roles)) . esc_html( PHP_EOL );


    // Plugin List, Active Plugins and Plugins with Updates available
    // Populate the plugin data
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    // Dump the plugin data
    echo esc_html( "PLUGIN_DATA\t" );
    $plugins = array();
    // Get a list of all the plugins, just by the keys.
    $all_plugins = array_keys(get_plugins());
    // Loop through them all...
    foreach ($all_plugins as $plugin) {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . "/" . $plugin);
        // Santise $plugin_data and add it to the $plugins array, using mbstring if available
        foreach(array_keys($plugin_data) as $key){
            if ( function_exists( 'mb_convert_encoding' ) ) {
                $plugin_data[$key] = mb_convert_encoding($plugin_data[$key],'UTF-8');
            } else {
                $plugin_data[$key] = iconv('UTF-8','UTF-8//IGNORE', $plugin_data[$key]);
            }
        }
        $plugins[ $plugin ] = $plugin_data;
    }
    // Output the sanitised array.
    echo strip_tags(json_encode($plugins)) . esc_html( PHP_EOL );


    // Plugin List, Active Plugins and Plugins with Updates available
    echo esc_html( "PLUGIN_UPDATE\t" ) . json_encode((array)get_site_transient( 'update_plugins' )) . esc_html( PHP_EOL );
    echo esc_html( "PLUGIN_ACTIVE\t" ). strip_tags(json_encode(get_option( 'active_plugins', array() ))) . esc_html( PHP_EOL );
    echo esc_html( "PLUGIN_NETWORK\t" ). strip_tags(json_encode(get_site_option( 'active_sitewide_plugins' ))) . esc_html( PHP_EOL );
    echo esc_html( "PLUGIN_MU\t" ). strip_tags(json_encode(get_mu_plugins())) . esc_html( PHP_EOL );
    echo esc_html( "PLUGIN_DROPIN\t" ). strip_tags(json_encode(get_dropins())) . esc_html( PHP_EOL );


    // Active Theme, various other theme information
    echo esc_html( "THEME_ACTIVE\t" . wp_get_theme()->stylesheet . PHP_EOL );
    echo esc_html( "THEME_LIST\t" ) . json_encode(search_theme_directories()) . esc_html( PHP_EOL );
    echo esc_html( "THEME_UPDATE\t" ) . json_encode((array)get_site_transient( 'update_themes' )) . esc_html( PHP_EOL );
    echo esc_html( "THEME_DATA\t" );
    $themes = array();
    $all_themes = wp_get_themes();
    // For each theme
    foreach ($all_themes as $theme) {
        // Store the theme info, ignoring the info we don't need
	    $theme_info = array(
            'Name' => $theme->get('Name'),
            'ThemeURI' => $theme->get('ThemeURI'),
            'Author' => $theme->get('Author'),
            'AuthorURI' => $theme->get('AuthorURI'),
            'Version' => $theme->get('Version'),
            'Template' => $theme->get('Template'),
            'Status' => $theme->get('Status')
    	);
        // Sanitise the generated array, using mbstring if possible.
        foreach(array_keys($theme_info) as $key){
            if ( function_exists( 'mb_convert_encoding' ) ) {
                $theme_info[$key] = mb_convert_encoding($theme_info[$key],'UTF-8');
            } else {
                $theme_info[$key] = iconv('UTF-8','UTF-8//IGNORE', $theme_info[$key]);
            }
        }
        // Add the theme_info to the array
	    $themes[ $theme->stylesheet ] = $theme_info;
    }
    echo strip_tags(json_encode($themes)) . esc_html( PHP_EOL );


    // Search uploads for PHP files, and hash the result(s)
    echo esc_html( "PHP_IN_UPLOADS\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( $upload_dir ,'~\.(php|php[s0-9]|phtml)$~' ))) . esc_html( PHP_EOL );


    // Return the contents of the sites .htaccess
    echo esc_html( "HTACCESS\t" );
    if ( $server_docroot != $wp_path ) {
        $htaccess_file = $wp_path . '/.htaccess';
    } else {
        $htaccess_file = $server_docroot . '/.htaccess';
    }
    if ( file_exists( $htaccess_file ) ) {
        echo json_encode( file_get_contents( $htaccess_file ) );
    } else {
        echo json_encode( '_missing' );
    }
    echo esc_html( PHP_EOL );



    // Report hashes for external verification if the parameter is set, otherwise skip.
    if ( isset( $_POST['mythic-wp-gethashes'] ) ) {

        // Increase the timeout from the default, as this may take a while on sites with lots of files in WP_CONTENT or slow IO
        set_time_limit(120);

        // Hashes of PHP files in wp-content (ie: drop-ins)
        echo esc_html( "WPCONTENT_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( WP_CONTENT_DIR, '~.*\.php~', false ))) . esc_html( PHP_EOL );

        // Plugin Hashes
        echo esc_html( "PLUGIN_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( WP_PLUGIN_DIR, '~.*~'))) . esc_html( PHP_EOL );

	    // Must-Use Plugin Hashes
        echo esc_html( "PLUGIN_MU_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( WP_CONTENT_DIR . '/mu-plugins' , '~.*~'))) . esc_html( PHP_EOL );

        // Theme Hashes
        echo esc_html( "THEME_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( get_theme_root(), '~.*~'))) . esc_html( PHP_EOL );

        // Hashes of PHP files in the ABSPATH
        echo esc_html( "WPABS_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( $wp_path, '~index\.php|wp-.*\.php~', false ))) . esc_html( PHP_EOL );

        // Hashes of files in wp-admin
        echo esc_html( "WPADM_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( $wp_path . '/wp-admin' ,'~.*~'))) . esc_html( PHP_EOL );

        // Hashes of files in wp-includes
        echo esc_html( "WPINC_HASH\t" ) . strip_tags(json_encode(mythic_wp_management_md5_matching_files( $wp_path . '/wp-includes' ,'~.*~'))) . esc_html( PHP_EOL );


    } else {
        echo esc_html( "HASHES\tnot requested" . PHP_EOL );
    }

    // Report the last time the query was run.
    $last_query = preg_replace( '/\D/', '', get_option( 'mythic-wp-last_query' ) );
    echo esc_html( "LAST_QUERY\t" .  date(DATE_ATOM, $last_query) . "\t" . $last_query . "\t" . ( time() - $last_query ) . PHP_EOL );



    // Footer
    echo esc_html( "END_REPORT\tmythic-wp" . PHP_EOL );

}


/**
 * Display the diagnostic information in a page when provided the correct key.
 */

function mythic_wp_management_page()
{
    // Check for the 'mythic-wp' field in the post request.
    if ( isset( $_POST['mythic-wp'] ) ) {
        // Read the value of mythic-wp into a var, replacing any non-alphanumeric characters with _ to avoid stuffing.
        $key = preg_replace( '/[^a-zA-Z0-9\.:]/', '_', htmlspecialchars($_POST["mythic-wp"]));
        if( strlen($key) == 32 && $key == get_option( 'mythic-wp-key' )){
            header("Cache-Control: no-cache, must-revalidate");
            header("Content-Type: text/plain;");
            mythic_wp_management_report();
            update_option( 'mythic-wp-last_query', preg_replace( '/\D/', '', time()));
            die;
        }
    }

}

add_action('plugins_loaded', 'mythic_wp_management_page', -1 );


/**
 * Display notices for administrator users, on relevant pages.
 */

function mythic_wp_management_notice() {
    if ( is_admin() ) {
        $last_query = preg_replace( '/\D/', '', get_option( 'mythic-wp-last_query' ) );

        if ( $last_query == "0" ) {

            // Not yet been queried.
            echo "<div class=\"notice notice-error\"><p><b>Warning:</b> This site is not currently managed. It may be you do not have <a href=\"https://www.mythic-beasts.com/apps/wordpress\">Managed WordPress Hosting from Mythic Beasts</a>, or the plugin was only just activated.</p></div>";

        } elseif ( ( time() - $last_query ) >= 604800 ) {

            // Queried some time ago.
            echo "<div class=\"notice notice-error\"><p><b>Warning:</b> This site is not currently managed. It was last checked on " . esc_html( date("Y-m-d", $last_query) ) . ".</p><p>If this is unexpected, please contact Mythic Beasts support via <a href=\"mailto:support@mythic-beasts.com\">email</a>.</p></div>";

        } else {

            // Queried recently.
            global $pagenow;
            $admin_pages = [ "index.php", "plugins.php", "update-core.php"];
            $update_pages = [ "plugins.php", "update-core.php"];

            if ( in_array( $pagenow , $admin_pages ) ) {
                // Managed Notice
                echo "<div class=\"notice notice-success\"><p><b><a href=\"https://www.mythic-beasts.com/apps/wordpress\">Mythic Beasts Managed WordPress Hosting</b></a> &mdash; This WordPress Site is managed by <b>Mythic Beasts</b>.<br />Site security, upgrades, 24/7 monitoring and daily backups are all handled automatically.</p><p style='text-align: right'>Problem with the site? <a href=\"mailto:support@mythic-beasts.com\">Email Mythic Beasts Support</a>.</p></div>";
            }

            if ( in_array( $pagenow , $update_pages ) ) {
                // Warn About Plugin Upgrades
                echo "<div class=\"notice notice-error\"><p style='text-align: center'><b>WordPress upgrades are being handled automatically. We'll keep them up to date for you and get in touch if there are any issues.</b></p></div>";
            }

        }

    }
}
add_action( 'admin_notices', 'mythic_wp_management_notice' );


/**
 * Disable the core upgrade nag, as this is managed elsewhere.
 */

function mythic_wp_management_disable_core_upgrade_nag() {
    remove_action( 'admin_notices', 'update_nag', 3 );
}

add_action('admin_menu','mythic_wp_management_disable_core_upgrade_nag');


/**
 * Disable the PHP version heathcheck notice, as PHP is managed elsewhere.
 */

function mythic_wp_management_disable_php_version_nag() {
    remove_meta_box( 'dashboard_php_nag', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'mythic_wp_management_disable_php_version_nag' );


/**
 * Disable the Object Cache healthcheck notice, as this is managed elsewhere.
 */
add_filter( 'site_status_should_suggest_persistent_object_cache', '__return_false' );


/**
 * End.
 */

?>
