<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @author     Stanislav Gaistrk <stanislav@wizewarez.eu>
 */
class Merit_Aktiva_Woocommerce_Plugin
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Merit_Aktiva_Woocommerce_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('MERIT_ACTIVA_WOOCOMMERCE_PLUGIN_VERSION')) {
            $this->version = MERIT_ACTIVA_WOOCOMMERCE_PLUGIN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'merit-aktiva-woocommerce';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_woocommerce_hooks();
    }

    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-merit-aktiva-client.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-merit-aktiva-woocommerce-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-merit-aktiva-woocommerce-integration.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-merit-aktiva-woocommerce-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-merit-aktiva-woocommerce-order-handler.php';

        $this->loader = new Merit_Aktiva_Woocommerce_Loader();
    }

    private function define_admin_hooks()
    {
        $plugin_admin = new Merit_Aktiva_Woocommerce_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('woocommerce_integrations', $plugin_admin, 'add_integration');
        $this->loader->add_action('woocommerce_order_actions', $plugin_admin, 'add_order_action');
    }

    private function define_woocommerce_hooks()
    {
        $order_status_change_listener = new Merit_Aktiva_Woocommerce_Order_Handler();
        $this->loader->add_action('woocommerce_order_status_completed', $order_status_change_listener, 'on_order_status_completed', -1, 1);
        $this->loader->add_action('woocommerce_order_action_merit_aktiva_woocommerce_create_invoice_action', $order_status_change_listener, 'on_order_status_completed');
    }

    public function run()
    {
        $this->loader->run();
    }

    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    public function get_loader()
    {
        return $this->loader;
    }

    public function get_version()
    {
        return $this->version;
    }

}
