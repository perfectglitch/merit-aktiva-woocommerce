<?php

class Merit_Aktiva_Woocommerce_Integration extends WC_Integration
{

    public function __construct()
    {
        $this->id                 = 'merit-aktiva-woocommerce-integration';
        $this->method_title       = __('Merit Aktiva Woocommerce Integration', 'merit-aktiva-woocommerce-plugin');
        $this->method_description = __('Adds Merit Aktiva integration to Woocommerce.', 'woocommerce-integration-demo');

        $this->init_form_fields();
        $this->init_settings();

        $this->api_url                = $this->get_option('merit-aktiva-woocommerce-api-url');
        $this->api_id                 = $this->get_option('merit-aktiva-woocommerce-api-id');
        $this->api_key                = $this->get_option('merit-aktiva-woocommerce-api-key');
        $this->default_payment_method = $this->get_option('merit-aktiva-woocommerce-default-payment-method');
        $this->shipping_description   = $this->get_option('merit-aktiva-woocommerce-shipping-description');

        $this->init_hooks();
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'merit-aktiva-woocommerce-api-url'                       => array(
                'title'       => __('API URL', 'text-domain'),
                'description' => __('Base URL for Merit Aktiva API, i.e https://aktiva.merit.ee/api/v1/', 'text-domain'),
                'type'        => 'text',
                'default'     => 'https://aktiva.merit.ee/api/v1/',
            ),
            'merit-aktiva-woocommerce-api-id'                        => array(
                'title'       => __('API ID', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'text',
                'description' => '',
            ),
            'merit-aktiva-woocommerce-api-key'                       => array(
                'title'       => __('API Key', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'password',
                'description' => '',
            ),
            'merit-aktiva-woocommerce-api-key'                       => array(
                'title'       => __('API Key', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'password',
                'description' => '',
            ),
            'merit-aktiva-woocommerce-default-payment-method'        => array(
                'default'     => 'Kaardiga laekunud müügiarved',
                'title'       => __('Default payment method', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'text',
                'description' => 'Must be valid payment method name in Merit Aktiva',
            ),
            'merit-aktiva-woocommerce-default-payment-method'        => array(
                'default'     => 'Kaardiga laekunud müügiarved',
                'title'       => __('Default payment method', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'text',
                'description' => 'Must be valid payment method name in Merit Aktiva',
            ),
            'merit-aktiva-woocommerce-shipping-description-template' => array(
                'default'     => 'Tarneviis: %s',
                'title'       => __('Shipping method description template', 'merit-aktiva-woocommerce-plugin'),
                'type'        => 'text',
                'description' => 'Template for shipping method description (not translateable).',
            ),
        );
    }

    private function init_hooks()
    {
        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));

        add_filter('merit-aktiva-woocommerce-integeration-get-client', array($this, 'get_client'));
        add_filter('merit-aktiva-woocommerce-integeration-get-payment-method', array($this, 'get_merit_activa_payment_method'), 10, 1);
        add_filter('merit-aktiva-woocommerce-integeration-get-shipping-description', array($this, 'get_merit_activa_shipping_description'), 10, 1);
    }

    public function get_client()
    {
        return Merit_Aktiva_Client::create(
            $this->get_option('merit-aktiva-woocommerce-api-url'),
            $this->get_option('merit-aktiva-woocommerce-api-id'),
            $this->get_option('merit-aktiva-woocommerce-api-key')
        );
    }

    public function get_merit_activa_payment_method($woocommerce_payment_method)
    {
        switch ($woocommerce_payment_method) {
            default:
                return $this->get_option('merit-aktiva-woocommerce-default-payment-method');
        }
    }

    public function get_merit_activa_shipping_description($woocommerce_shipping_method)
    {
        return sprintf($this->get_option('merit-aktiva-woocommerce-shipping-description-template') ?: '%s', $woocommerce_shipping_method);
    }
}
