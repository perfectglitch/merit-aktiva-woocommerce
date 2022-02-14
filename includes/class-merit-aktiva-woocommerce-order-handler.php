<?php

class Merit_Aktiva_Woocommerce_Order_Handler
{

    private $logger;
    private $logging_context;

    public function __construct()
    {
        $this->logger          = wc_get_logger();
        $this->logging_context = array('source' => get_class());
        $this->client          = apply_filters('merit-aktiva-woocommerce-integeration-get-client', null);
    }

    /**
     * @param WC_Order|int $order
     * @return void
     */
    public function on_order_status_completed($order)
    {
        $order = $this->validate_order($order);
        
        if (!$order) {
            $this->logger->error('Received invalid order from order status hook', $this->logging_context);
            return;
        }

        if (!$this->client) {
            $order->add_order_note(sprintf(__('Skipped creating invoice in Merit Aktiva - API not configured', 'merit-aktiva-woocommerce-plugin'), $order->get_id()));
            return;
        }

        $this->logger->debug(sprintf('Handling order %d', $order->get_id()), $this->logging_context);

        try {
            $this->create_merit_aktiva_invoice($order);
        } catch (Exception $e) {
            $this->logger->error($e, $this->logging_context);
        }
    }

    /**
     * @param WC_Order|int $order
     * @return WC_Order|false
     */
    private function validate_order($order)
    {
        if (is_int($order)) {
            $order = new WC_Order($order);
        }

        if ($order->get_meta('merit_aktiva_invoice_guid')) {
            $order->add_order_note(sprintf(__('Skipped creating invoice in Merit Aktiva - order %d already has Merit Aktiva invoice ID', 'merit-aktiva-woocommerce-plugin'), $order->get_id()));
            return false;
        }

        return apply_filters('merit-aktiva-woocommerce-validate-order', $order);
    }

    /**
     * @param WC_Order
     * @return void
     */
    private function create_merit_aktiva_invoice($order)
    {
        $invoice = $this->create_invoice_from_order($order);

        $this->logger->debug('Sending invoice: ' . json_encode($invoice), $this->logging_context);
        $result = $this->client->send_invoice($invoice);
        $this->logger->debug('Invoice result: ' . json_encode($result), $this->logging_context);

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
        return apply_filters('merit-aktiva-woocommerce-get-invoice', [
            'InvoiceNo'       => $this->get_invoice_number($order),
            'InvoiceRow'      => $this->get_invoice_row($order),

            'TotalAmount'     => round($order->get_total() - $order->get_total_tax(), wc_get_price_decimals()),
            'CurrencyCode'    => $order->get_currency(),
            'RoundingAmount'  => 0,
            'TaxAmount'       => $this->get_tax_amount($order),

            'DocDate'         => $order->get_date_created()->date('Ymd'),
            'TransactionDate' => $order->get_date_paid()->date('Ymd'),

            'Customer'        => $this->get_customer_data($order),
            'Payment'         => $this->get_payment_data($order),
        ], $order);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_payment_data($order)
    {
        return apply_filters('merit-aktiva-woocommerce-get-invoice-payment-data', [
            'PaymentMethod' => apply_filters('merit-aktiva-woocommerce-integeration-get-payment-method', $order->get_payment_method()),
            'PaidAmount'    => $order->get_total(),
            'PaymDate'      => $order->get_date_paid()->date('Ymd'),
        ], $order);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_customer_data($order)
    {
        return apply_filters('merit-aktiva-woocommerce-get-customer-data', [
            'Name'          => $order->get_formatted_billing_full_name(),
            'NotTDCustomer' => 'true',
            'Address'       => sprintf('%s %s', $order->get_billing_address_1(), $order->get_billing_address_2()),
            'City'          => $order->get_billing_city(),
            'County'        => $order->get_billing_state(),
            'PostalCode'    => $order->get_billing_postcode(),
            'CountryCode'   => $order->get_billing_country(),
            'PhoneNo'       => $order->get_billing_phone(),
            'Email'         => $order->get_billing_email(),
        ], $order);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_invoice_row($order)
    {
        $row = [];
        foreach (array_merge($order->get_items(), $order->get_items('shipping')) as /** @var WC_Order_Item */$item) {
            $row[] = [
                'Item'     => $this->get_order_item_data($item),
                'Quantity' => $item->get_quantity(),
                'Price'    => $order->get_item_total($item, false, false),
                'TaxId'    => 'b9b25735-6a15-4d4e-8720-25b254ae3d21',
            ];
        }

        return apply_filters('merit-aktiva-woocommerce-get-invoice-row', $row, $order);
    }

    /**
     * @param WC_Order_Item $item
     * @return array
     */
    private function get_order_item_data($item)
    {
        switch ($item->get_type()) {
            case 'line_item':
                /** @var WC_Order_Item_Product */
                $product_item = $item;
                /** @var WC_Product|bool */
                $product = $product_item->get_product();
                $data    = [
                    'Code'        => $product->get_sku(),
                    'Description' => $product->get_name(),
                    'Type'        => 3,
                ];
                break;
            case 'shipping':
            default:
                $data = [
                    'Code'        => $item->get_id(),
                    'Description' => apply_filters('merit-aktiva-woocommerce-integeration-get-shipping-description', $item->get_name()),
                    'Type'        => 2,
                ];
        }

        return apply_filters('merit-aktiva-woocommerce-get-order-item-data', $data, $item);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function get_tax_amount($order)
    {
        return apply_filters('merit-aktiva-get-invoice-tax', [
            [
                'TaxId'  => 'b9b25735-6a15-4d4e-8720-25b254ae3d21',
                'Amount' => $order->get_total_tax(),
            ],
        ], $order);
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

        return apply_filters('merit-aktiva-woocommerce-get-invoice-number', $invoice_number, $order);
    }

}
