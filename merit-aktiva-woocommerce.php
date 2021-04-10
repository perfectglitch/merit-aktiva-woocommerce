<?php

/**
 * Plugin name: Merit Aktiva Woocommerce Plugin
 * Plugin URI:        https://wizewarez.eu/merit-aktiva-woocommerce-plugin/
 * Description:       Automatically send completed Woocommerce order invoices to Merit Aktiva.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Stanislav Gaistruk
 * Author URI:        https://wizewarez.eu
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       merit-aktiva-woocommerce-plugin
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define('MERIT_ACTIVA_WOOCOMMERCE_PLUGIN_VERSION', '1.0.0');

require plugin_dir_path(__FILE__) . 'includes/class-merit-aktiva-woocommerce.php';

function activate_merit_aktiva_woocommerce()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-merit-aktiva-woocommerce-activator.php';
    Merit_Aktiva_Woocommerce_Activator::activate();
}

function deactivate_merit_aktiva_woocommerce()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-merit-aktiva-woocommerce-deactivator.php';
    Merit_Aktiva_Woocommerce_Deactivator::deactivate();
}

function check_merit_aktiva_woocommerce_plugin_requirements()
{
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        return true;
    } else {
        add_action('admin_notices', function () {
            $class   = 'notice notice-error';
            $message = __('Woocommerce is required for using Merit Aktiva Woocommerce Plugin', 'merit-aktiva-woocommerce-plugin');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        });
        do_action('deactivate_plugin', 'merit-aktiva-woocommerce/merit-aktiva-woocommerce.php');

        return false;
    }
}

function run_merit_aktiva_woocommerce_plugin()
{
    if (check_merit_aktiva_woocommerce_plugin_requirements()) {
        $plugin = new Merit_Aktiva_Woocommerce_Plugin();
        $plugin->run();
    }
}

register_activation_hook(__FILE__, 'activate_merit_aktiva_woocommerce_plugin');
register_deactivation_hook(__FILE__, 'deactivate_merit_aktiva_woocommerce_plugin');

add_action('plugins_loaded', 'run_merit_aktiva_woocommerce_plugin');
