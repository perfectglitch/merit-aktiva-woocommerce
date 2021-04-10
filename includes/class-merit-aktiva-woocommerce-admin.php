<?php

class Merit_Aktiva_Woocommerce_Admin
{
    public function add_integration($integrations)
    {
        $integrations[] = 'Merit_Aktiva_Woocommerce_Integration';
        return $integrations;
    }

    public function add_order_action($actions){
        /** @var WC_Order */
        global $theorder;
        $actions['merit_aktiva_woocommerce_create_invoice_action'] = __('Create Merit Aktiva Invoice');
        return $actions;
    }
}
