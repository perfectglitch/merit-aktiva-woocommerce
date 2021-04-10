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

        $this->api_url = $this->get_option('merit-aktiva-woocommerce-api-url');
        $this->api_id  = $this->get_option('merit-aktiva-woocommerce-api-id');
        $this->api_key = $this->get_option('merit-aktiva-woocommerce-api-key');

        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));
        add_filter('merit-aktiva-woocommerce-integeration-get-client', array($this, 'get_client'), 10, 1);
    }

    public function get_client()
    {
        return Merit_Aktiva_Client::create(
            $this->get_option('merit-aktiva-woocommerce-api-url'),
            $this->get_option('merit-aktiva-woocommerce-api-id'),
            $this->get_option('merit-aktiva-woocommerce-api-key')
        );
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'merit-aktiva-woocommerce-api-url' => array(
                'title'       => __('API URL', 'text-domain'),
                'description' => __('Base URL for Merit Aktiva API, i.e https://aktiva.merit.ee/api/v2/', 'text-domain'),
                'type'        => 'text',
                'default'     => 'https://aktiva.merit.ee/api/v2/',
            ),
            'merit-aktiva-woocommerce-api-id'  => array(
                'title'       => __('API ID', 'text-domain'),
                'type'        => 'text',
                'description' => '',
            ),
            'merit-aktiva-woocommerce-api-key' => array(
                'title'       => __('API Key', 'text-domain'),
                'type'        => 'password',
                'description' => '',
            ),
        );
    }
}
