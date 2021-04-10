<?php

class Merit_Aktiva_Woocommerce_Order_Handler
{

    private $logger;
    private $logging_context;

    public function __construct()
    {
        $this->logger          = wc_get_logger();
        $this->logging_context = array('source' => get_class());
    }

    public function on_order_status_completed($order)
    {
        if (is_int($order)) {
            $order = new WC_Order($order);
        }

        /**
         * @var Merit_Aktiva_Client
         */
        $client = apply_filters('merit-aktiva-woocommerce-integeration-get-client', null);
        if (!$client) {
            $this->logger->info(sprintf("Not sending order %d to Merit Aktiva - client not initialized", $order->get_id()), $this->logging_context);
            return;
        }

        $this->logger->info(sprintf("Handling order %d", $order->get_id()), $this->logging_context);
        if ($order->get_meta('merit_aktiva_invoice_guid')) {
            $this->logger->warning(sprintf("Order %d already has Merit Aktiva invoice", $order->get_id()), $this->logging_context);
            return;
        }

        $invoice = $this->create_invoice_from_order($order);
        $this->logger->debug("Sending invoice: " . json_encode($invoice), $this->logging_context);
        $result = $client->send_invoice($invoice);

        $this->handle_invoice_result($order, $result);
    }

    private function handle_invoice_result($order, $result)
    {
        update_post_meta($order->get_id(), 'merit_aktiva_result', json_encode($result));

        if ($result['success']) {
            update_post_meta($order->get_id(), 'merit_aktiva_invoice_guid', $result['data']['InvoiceId']);
            $order->add_order_note(sprintf(__('Successfully created order invoice in Merit Aktiva', 'merit-aktiva-woocommerce-plugin'), $order->get_id()));
        } else {
            $order->add_order_note(sprintf(__('Failed to create order invoice in Merit Aktiva', 'merit-aktiva-woocommerce-plugin'), $order->get_id()));
        }
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function create_invoice_from_order($order)
    {
        wc_get_logger()->info(json_encode($order->get_data()), array('source' => get_class()));
        return [
            'InvoiceNo'       => $this->get_invoice_number($order),
            'InvoiceRow'      => $this->get_invoice_row($order),

            'TotalAmount'     => number_format((float) $order->get_total() - $order->get_total_tax() - $order->get_shipping_tax(), wc_get_price_decimals(), '.', ''),
            'CurrencyCode'    => $order->get_currency(),
            'RoundingAmount'  => 2,
            'TaxAmount'       => $this->get_tax_amount($order),

            'DocDate'         => date('Ymd'),
            'TransactionDate' => $order->get_date_paid()->date('Ymd'),

            'Customer'        => $this->get_customer_data($order),
        ];
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_customer_data($order)
    {
        return [
            'Name'          => $order->get_formatted_billing_full_name(),
            'NotTDCustomer' => 'true',
            'Address'       => $order->get_billing_address_1(),
            'City'          => $order->get_billing_city(),
            'County'        => $order->get_billing_state(),
            'PostalCode'    => $order->get_billing_postcode(),
            'CountryCode'   => $order->get_billing_country(),
            'PhoneNo'       => $order->get_billing_phone(),
            'Email'         => $order->get_billing_email(),
        ];
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_invoice_row($order)
    {
        $row = [];
        foreach ($order->get_items() as /** @var WC_Order_Item */$item) {
            /** @var WC_Product */

            $row[] = [
                'Item'     => $this->get_order_item_data($item),
                'Quantity' => $item->get_quantity(),
                'Price'    => $order->get_item_total($item, true),
                'TaxId'    => 'b9b25735-6a15-4d4e-8720-25b254ae3d21',
            ];
        }

        return $row;
    }

    /**
     * @param WC_Order_Item $item
     * @return array
     */
    private function get_order_item_data($item)
    {
        switch ($item->get_type()) {
            case 'product':
                /** @var WC_Order_Item_Product */
                $product_item = $item;
                /** @var WC_Product|bool */
                $product = $product_item->get_product();
                return [
                    'Code'        => $product->get_sku(),
                    'Description' => $product->get_description(),
                    'Type'        => 3,
                ];
                break;
            case 'shipping':
            default:
                return [
                    'Code'        => $item->get_id(),
                    'Description' => $item->get_name(),
                    'Type'        => 2,
                ];
        }
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_tax_amount($order)
    {
        return [
            [
                'TaxId'  => 'b9b25735-6a15-4d4e-8720-25b254ae3d21',
                'Amount' => $order->get_total_tax(),
            ],
        ];
    }

    /**
     * @param WP_Order $order
     * @return string
     */
    private function get_invoice_number($order)
    {
        $invoice_number = $order->get_order_number(); // fallback

        if (function_exists('wcpdf_get_document')) {
            $invoice = wcpdf_get_document('invoice', $order, true);
            if ($invoice && $invoice->exists()) {
                $number         = $invoice->get_number();
                $invoice_number = $number->get_formatted();
            }
        }

        return $invoice_number;
    }

}
