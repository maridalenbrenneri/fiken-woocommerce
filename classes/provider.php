<?php

if (!class_exists('FikenProvider')) {

    include_once FIKEN_PLUGIN_DIR . 'classes/utils.php';

    class FikenProvider
    {
        public static function getFikenModule()
        {
            return new Fiken();
        }

        public static function getSalesKindFiken()
        {
            $fikenModule = self::getFikenModule();
            return array(
                array("code" => FikenUtils::CASH_SALE, "name" => __('Cash sale', 'fiken')),
                array("code" => FikenUtils::EXTERNAL_INVOICE, "name" => __('External invoice', 'fiken'))
            );
        }


        public static function getVatTypesFiken()
        {
            return array(
                array("code" => FikenUtils::VAT_HIGH, "name" => FikenUtils::VAT_HIGH),
                array("code" => FikenUtils::VAT_MEDIUM, "name" => FikenUtils::VAT_MEDIUM),
                array("code" => FikenUtils::VAT_LOW, "name" => FikenUtils::VAT_LOW),
                array("code" => FikenUtils::VAT_EXEMPT, "name" => FikenUtils::VAT_EXEMPT),
                array("code" => FikenUtils::VAT_OUTSIDE, "name" => FikenUtils::VAT_OUTSIDE),
                array("code" => FikenUtils::VAT_NONE, "name" => FikenUtils::VAT_NONE),
            );
        }

        public static function getStatesFiken($id_state = '')
        {
            global $wpdb;
            return $wpdb->get_results("SELECT `id_state`, `name` FROM `{$wpdb->prefix}fiken_states` " . ($id_state != '' ? "WHERE `id_state` = " . (int)$id_state : '') . " ORDER BY `id_state`", ARRAY_A);
        }


        public static function getListPayWC()
        {
            global $woocommerce;
            $res = array();
            $payMethods = $woocommerce->payment_gateways->payment_gateways;
            foreach ($payMethods as $item) {
                if ($item->enabled == 'yes') {
                    $res[] = array('id' => $item->id, 'name' => $item->title);
                }
            }
            $res[] = array('id' => 'free_order', 'name' => 'Free order');
            return $res;
        }

        public static function getOrderStatesWC()
        {
            $res = array();
            $states = wc_get_order_statuses();
            foreach ($states as $key => $item) {
                $st = array();
                $st['id'] = $key;
                $st['name'] = $item;
                $res[] = $st;
            }
            return $res;
        }

        public static function getTaxesWC()
        {
            global $wpdb;
            $ret = array();
            $result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates", ARRAY_A);
            foreach ($result as $item) {
                $tax = array();
                $tax['id'] = $item['tax_rate_id'];
                $tax['name'] = $item['tax_rate_name'] . ' (' . number_format((float)$item['tax_rate'], 2, ',', '') . ' %)';
                $tax['rate'] = floatval($item['tax_rate']);
                $ret[] = $tax;
            }
            return $ret;
        }


        public static function getVatCode($product)
        {
            $res = FikenUtils::VAT_NONE;
            $line_tax_data = maybe_unserialize($product['line_tax_data']);
            if (isset($line_tax_data['total']) && $line_tax_data['total']) {
                $taxes = $line_tax_data['total'];
                if (isset($taxes) && $taxes) {
                    foreach ($taxes as $taxCode => $taxValue) {
                        if (isset($taxValue) && $taxValue) {
                            $vatSettings = json_decode(get_option(FikenUtils::CONF_FIKEN_VATS_MAPPING));
                            if (isset($vatSettings)) {
                                if (isset($taxCode)
                                    && isset($vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT})
                                    && $vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT}) {
                                    $res = $vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT};
                                }
                            }
                            /**
                             * takes first not null tax
                             */
                            break;
                        }
                    }
                }
            }
           return $res;
        }

        public static function calculateVatCode($cost, $vat)
        {
            $vatRate = 100 * $vat / $cost;
            $taxes = self::getTaxesWC();
            // FikenUtils::log(var_export($taxes, true), 'VAT rates', FikenUtils::LOG_LEVEL_INFO);
            foreach ($taxes as $tax){
                $diff = abs($tax['rate'] - $vatRate);
                if ($diff < 0.01){
                    return self::getVatCodeByTaxCode($tax['id']);
                }
            }

            throw new Exception(sprintf(__('No WooCommerce tax rate matches calculated rate %0.2f', 'fiken', $vatRate)));
        }

        public static function getVatCodeByTaxCode($taxCode)
        {
            $res = FikenUtils::VAT_NONE;
            if (isset($taxCode) && $taxCode) {
                $vatSettings = json_decode(get_option(FikenUtils::CONF_FIKEN_VATS_MAPPING));
                if (isset($vatSettings)) {
                    if (isset($vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT})
                        && $vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT}) {
                        $res = $vatSettings->{$taxCode}->{FikenUtils::CTRL_NAME_VAT};
                    }
                }
            }
            return $res;
        }


        public static function getInvoiceNumber($order)
        {
            /**
             * v 3.0 compatible
             */
            if (version_compare(WC_VERSION, '3.0') < 0) {
                $wcOrderId = $order->id;
            } else {
                $wcOrderId = $order->get_id();
            }

            $creditNote = get_post_meta($wcOrderId, '_wcpdf_credit_note_number', true);
            if (!empty($creditNote)) {
                return $creditNote;
            }
            $invoiceNumber = get_post_meta($wcOrderId, '_wcpdf_invoice_number', true);
            if (!empty($invoiceNumber)) {
                return $invoiceNumber;
            }
            return '';
        }
    }

}