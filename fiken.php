<?php
/**
 * @package Fiken
 */
/*
Plugin Name: Fiken
Plugin URI: https://www.fiken.no/
Description: The Module is used for transferring orders to <a target='_blank' href = 'https://www.fiken.no'>Fiken</a>.
Version: 1.17
Author: Fiken
Author URI: https://www.fiken.no/
License: GPLv2 or later
Text Domain: fiken
*/

// Make sure we don't expose any info if called directly
if (!defined('ABSPATH')) {
    exit;
}

define('FIKEN_VERSION', '1.17');
define('FIKEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FIKEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FIKEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

include_once FIKEN_PLUGIN_DIR . 'classes/class.fiken.php';

register_activation_hook(__FILE__, array('Fiken', 'plugin_activation'));
register_deactivation_hook(__FILE__, array('Fiken', 'plugin_deactivation'));

add_action('plugins_loaded', 'fiken_load_lang');
add_action('init', 'fiken_init', 0);

function fiken_init()
{
    if (fiken_isWoocommerce()) {
        add_action('admin_menu', array('Fiken', 'add_menu'));
        add_filter('plugin_action_links_' . FIKEN_PLUGIN_BASENAME, array('Fiken', 'plugin_action_links'));
        add_action('admin_enqueue_scripts', array('Fiken', 'load_media'));
        add_action("wp_ajax_fiken_get_order_status_history", array('Fiken', 'ajax_process_get_order_status_history'));
        add_action("wp_ajax_fiken_register_sale", array('Fiken', 'ajax_process_register_sale'));
        add_action('woocommerce_order_status_changed', array('Fiken', 'process_register_sale'));
    } else {
        add_action('admin_notices', 'fiken_err_woo_message');
    }
}

function fiken_load_lang()
{
    $locale = apply_filters('plugin_locale', get_locale(), 'fiken');
    load_textdomain('fiken', trailingslashit(WP_LANG_DIR) . 'plugins/fiken-' . $locale . '.mo');
    load_plugin_textdomain('fiken', false, dirname(plugin_basename(__FILE__)) . '/languages');
}


function fiken_isWoocommerce()
{
    $blog_plugins = get_option('active_plugins', array());
    $site_plugins = get_site_option('active_sitewide_plugins', array());
    if (in_array('woocommerce/woocommerce.php', $blog_plugins) || isset($site_plugins['woocommerce/woocommerce.php'])) {
        return true;
    } else {
        return false;
    }
}


function fiken_err_woo_message()
{
    echo '<div class="error"><p>' . __('Fiken requires WooCommerce to be installed!', 'fiken') . '</p></div>';
}





