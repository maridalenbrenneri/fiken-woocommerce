<?php

if (!class_exists('FikenPDF')) {

    include_once FIKEN_PLUGIN_DIR . 'pdf/html_invoice.php';

    class FikenPDF
    {
        private $order;

        public function __construct($order)
        {
            $this->order = $order;
        }


        private function get_order_items()
        {
            $items = $this->order->get_items();
            $data_list = array();

            if (sizeof($items) > 0) {
                foreach ($items as $item) {
                    $data = array();
                    $data['product_id'] = $item['product_id'];
                    $data['variation_id'] = $item['variation_id'];
                    $data['name'] = $item['name'];
                    $data['quantity'] = $item['qty'];
                    $quantity_divider = ($item['qty'] == 0) ? 1 : $item['qty'];
                    $data['line_total'] = wc_price($item['line_total']);
                    $data['single_line_total'] = wc_price($item['line_total'] / $quantity_divider);
                    $data['line_tax'] = wc_price($item['line_tax']);
                    $data['single_line_tax'] = wc_price($item['line_tax'] / $quantity_divider);
                    $data['line_subtotal'] = wc_price($item['line_subtotal']);
                    $data['line_subtotal_tax'] = wc_price($item['line_subtotal_tax']);
                    $data['ex_price'] = $this->get_formatted_item_price($item, 'total', 'excl');
                    $data['price'] = $this->get_formatted_item_price($item, 'total');
                    $data['order_price'] = $this->order->get_formatted_line_subtotal($item);
                    $data['ex_single_price'] = $this->get_formatted_item_price($item, 'single', 'excl');
                    $data['single_price'] = $this->get_formatted_item_price($item, 'single');
                    $meta = new WC_Order_Item_Meta($item['item_meta']);
                    $data['meta'] = $meta->display(false, true);
                    $data['item'] = $item;
                    $data['product'] = null;
                    $product = $this->order->get_product_from_item($item);
                    if (!empty($product)) {
                        $data['sku'] = $product->get_sku();
                        $data['weight'] = $product->get_weight();
                        $data['dimensions'] = $product->get_dimensions();
                        $data['product'] = $product;
                    }
                    $data_list[] = $data;
                }
            }

            return $data_list;
        }


        private function get_formatted_item_price($item, $type, $tax_display = '')
        {
            $item_price = 0;
            $divider = ($type == 'single' && $item['qty'] != 0) ? $item['qty'] : 1;
            if (!isset($item['line_subtotal']) || !isset($item['line_subtotal_tax']))
                return;

            if ($tax_display == 'excl') {
                $item_price = wc_price(($this->order->get_line_subtotal($item)) / $divider);
            } else {
                $item_price = wc_price(($this->order->get_line_subtotal($item, true)) / $divider);
            }
            return $item_price;
        }

        private function get_order_totals()
        {
            $totals = $this->order->get_order_item_totals();
            foreach ($totals as $key => $total) {
                $label = $total['label'];
                $colon = strrpos($label, ':');
                if ($colon !== false) {
                    $label = substr_replace($label, '', $colon, 1);
                }
                $totals[$key]['label'] = $label;
            }
            return $totals;
        }


        private function  getViewData()
        {
            $data = array();
            $invNumber = FikenProvider::getInvoiceNumber($this->order);
            $data['invoice_number'] = !empty($invNumber) ? $invNumber :  $this->order->get_order_number();
            $data['order_date'] = substr($this->order->order_date, 0, 10);
            $data['payment_method'] = $this->order->payment_method_title;
            $data['billing_address'] = $this->order->get_formatted_billing_address();
            $data['order_items'] = $this->get_order_items();
            $data['order_totals'] = $this->get_order_totals();
            $data['shipping_notes'] = wpautop(wptexturize($this->order->customer_note));
            return $data;
        }


        public function generate_pdf($fileName)
        {
            if (!class_exists('Dompdf')) {
                require_once(FIKEN_PLUGIN_DIR . "pdf/lib/dompdf/autoload.inc.php");
            }

            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml(FikenHtmlInvoice::getHtmlInvoice($this->getViewData()));
            $dompdf->set_Paper('A4', 'portrait');
            $dompdf->render();
            file_put_contents($fileName, $dompdf->output());
            return file_exists($fileName);
        }


    }

}