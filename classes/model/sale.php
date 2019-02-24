<?php
if (!class_exists('FikenSale')) {


    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_model.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/customer.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/company.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_order.php';
    include_once FIKEN_PLUGIN_DIR . 'pdf/class.pdf.php';

    class FikenSale extends FikenModel
    {

        public $relSelf;
        public $relPayments;
        public $relAttachments;
        public $customer;
        public $date;
        public $identifier;
        /** @var $lines array */
        public $lines;
        public $kind;
        public $paymentAccount;
        public $paymentDate;


        function __construct($relSelf = '', $date = '', $identifier = '', $lines = array(), $relAttachments = '', $relCustomer = '', $relPayments = '', $kind = '', $paymentAccount = '', $paymentDate = '')
        {
            $this->date = $date;
            $this->identifier = $identifier;
            $this->lines = $lines;
            $this->relAttachments = $relAttachments;
            $this->customer = $relCustomer;
            $this->relPayments = $relPayments;
            $this->relSelf = $relSelf;
            $this->kind = $kind;
            $this->paymentAccount = $paymentAccount;
            $this->paymentDate = isset($paymentDate) ? $paymentDate : $date;
        }

        protected function getDef()
        {
            return array('relSelf', 'relPayments', 'relAttachments', 'customer', 'date', 'identifier', 'lines', 'kind', 'paymentAccount', 'paymentDate');
        }

        /**
         * @param mixed $date
         */
        public function setDate($date)
        {
            $this->date = $date;
        }

        /**
         * @return mixed
         */
        public function getDate()
        {
            return $this->date;
        }

        /**
         * @param mixed $identifier
         */
        public function setIdentifier($identifier)
        {
            $this->identifier = $identifier;
        }

        /**
         * @return mixed
         */
        public function getIdentifier()
        {
            return $this->identifier;
        }

        /**
         * @param array $lines
         */
        public function setLines($lines)
        {
            $this->lines = $lines;
        }

        /**
         * @return array
         */
        public function getLines()
        {
            return $this->lines;
        }

        /**
         * @param mixed $relAttachments
         */
        public function setRelAttachments($relAttachments)
        {
            $this->relAttachments = $relAttachments;
        }

        /**
         * @return mixed
         */
        public function getRelAttachments()
        {
            return $this->relAttachments;
        }

        /**
         * @param mixed $relPayments
         */
        public function setRelPayments($relPayments)
        {
            $this->relPayments = $relPayments;
        }

        /**
         * @return mixed
         */
        public function getRelPayments()
        {
            return $this->relPayments;
        }

        /**
         * @param mixed $relSelf
         */
        public function setRelSelf($relSelf)
        {
            $this->relSelf = $relSelf;
        }

        /**
         * @return mixed
         */
        public function getRelSelf()
        {
            return $this->relSelf;
        }

        /**
         * @param mixed $relCustomer
         */
        public function setRelCustomer($relCustomer)
        {
            $this->customer = $relCustomer;
        }

        /**
         * @return mixed
         */
        public function getRelCustomer()
        {
            return $this->customer;
        }

        /**
         * @return string
         */
        public function getKind()
        {
            return $this->kind;
        }

        /**
         * @param string $kind
         */
        public function setKind($kind)
        {
            $this->kind = $kind;
        }

        /**
         * @return string
         */
        public function getPaymentAccount()
        {
            return $this->paymentAccount;
        }

        /**
         * @param string $paymentAccount
         */
        public function setPaymentAccount($paymentAccount)
        {
            $this->paymentAccount = $paymentAccount;
        }

        /**
         * @return string
         */
        public function getPaymentDate()
        {
            return $this->paymentDate;
        }

        /**
         * @param string $paymentDate
         */
        public function setPaymentDate($paymentDate)
        {
            $this->paymentDate = $paymentDate;
        }

        /**
         * @param $uri
         * @return FikenSale|bool
         */
        public function uploadAttachment($order)
        {
            /**
             * v 3.0 compatible
             */
            if (version_compare(WC_VERSION, '3.0') < 0) {
                $wcOrderId = $order->id;
            } else {
                $wcOrderId = $order->get_id();
            }

            $pdf = new FikenPDF($order);
            $target_path_base = wp_upload_dir();
            $target_path_base = $target_path_base['basedir'] . '/';
            $filename = $target_path_base . uniqid();
            //created only if invoice  for order exists
            if ($pdf->generate_pdf($filename)) {
                $attachFileName = 'inv_' . $wcOrderId . ".pdf";
                $saleAtt = array();
                $saleAtt['filename'] = $attachFileName;
                $saleAtt['attachToPayment'] = true;
                $saleAtt['attachToSale'] = true;
                $data = array();
                $data['SaleAttachment'] = stripslashes(json_encode($saleAtt) . ';type=application/json');
                $data['AttachmentFile'] = new CURLFile($filename, 'application/pdf', $attachFileName);
                $result = FikenUtils::call($this->getRelAttachments(), $data, false);
                unlink($filename);
                if (!$result || !isset($result['location'])) {
                    throw new Exception(sprintf(__('Error attachment invoice id=%s  ("%s")!', 'fiken'), $wcOrderId, $this->getRelAttachments()));
                }
            }
        }


        public static function getSaleFromFiken($uri)
        {
            $res = false;
            $result = FikenUtils::call($uri);
            if (isset($result['body']) && $result['body']) {
                $hal = FikenHal::fromJson($result['body'], 1);
                $links = $hal->getLinks();
                $rAttachments = '';
                if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/attachments", $links) && $links[FikenUtils::FIKEN_BASE_URL . "/rel/attachments"]) {
                    $rAttachments = $links[FikenUtils::FIKEN_BASE_URL . "/rel/attachments"][0]->getUri();
                }
                $rPayments = '';
                if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/payments", $links) && $links[FikenUtils::FIKEN_BASE_URL . "/rel/payments"]) {
                    $rPayments = $links[FikenUtils::FIKEN_BASE_URL . "/rel/payments"][0]->getUri();
                }
                $data = $hal->getData();
                $rCustomer = array_key_exists('customer', $data) ? $data['customer'] : '';
                $res = new FikenSale($hal->getUri(), $data['date'], $data['identifier'], $data['lines'], $rAttachments, $rCustomer, $rPayments);
            }
            return $res;
        }


        public static function registerSale($order_id, $orgNumber = -1, $ajax = false)
        {
            try {
                /***  @var $order OrderCore */
                $order = new  WC_Order($order_id);
                if (!$order) {
                    throw new Exception(sprintf(__('Order id: %s not found!', 'fiken'), $order_id));
                }

                /**
                 * v 3.0 compatible
                 */
                if (version_compare(WC_VERSION, '3.0') < 0) {
                    $wcOrderId = $order->id;
                    $wcOrderPaymentMethod = $order->payment_method;
                    $wcOrderPostStatus = $order->post_status;
                } else {
                    $wcOrderId = $order->get_id();
                    $wcOrderPaymentMethod = $order->get_payment_method();
                    $wcOrderPostStatus = get_post_status($order->get_id());
                }

                //first time  -  status 1
                if (!$ajax) {
                    $fikenOrder = new FikenOrder($wcOrderId, '1');
                    $fikenOrder->update();
                }

                if ($orgNumber == -1) {
                    $orgNumber = get_option(FikenUtils::CONF_FIKEN_COMPANY);
                }
                $company = FikenCompany::getCompanyFromFiken($orgNumber);
                if (!$company) {
                    throw new Exception(__('Default company not found!', 'fiken'));
                }

                $payMethodSettings = json_decode(get_option(FikenUtils::CONF_FIKEN_PAY_METHODS));
                $payM = $wcOrderPaymentMethod ? $wcOrderPaymentMethod : 'free_order';
                $status = isset($payMethodSettings->{$payM}->{FikenUtils::CTRL_NAME_PAY_STATUS}) ? $payMethodSettings->{$payM}->{FikenUtils::CTRL_NAME_PAY_STATUS} : false;

                if (!$status) {
                    throw new Exception(sprintf(__('Order status for payment module "%s" not set!', 'fiken'), $order->module));
                }

                if ($status === $wcOrderPostStatus) {
                    try {
                        $resStatus = '2';
                        $relNewSale = self::registerSaleInner($order, $company, $payMethodSettings->{$payM}, $resStatus);
                        if ($relNewSale) {
                            $fikenOrder = new FikenOrder($wcOrderId, $resStatus);
                            $fikenOrder->update();
                            try {
                                //add attachment
                                $newSale = self::getSaleFromFiken($relNewSale);
                                if (!$newSale) {
                                    throw new Exception(sprintf(__('Sale not found ("%s")!', 'fiken'), $relNewSale));
                                }

                                if (get_option(FikenUtils::CONF_FIKEN_PDF_INV) || true) {
                                    $newSale->uploadAttachment($order);
                                }

                                return $ajax ? array('success' => sprintf(__('The transfer order id: %s successfully completed', 'fiken'), $wcOrderId)) : true;

                            } catch (Exception $exception) {
                                FikenUtils::log($exception->getMessage(), 'ERROR Add Attachment', FikenUtils::LOG_LEVEL_ERROR);
                                return $ajax ? array('error' => $exception->getMessage()) : false;
                            }
                        }
                    } catch (Exception $exception) {
                        $errStatus = ($exception->getCode() === 0) ? 3 : $exception->getCode();
                        $fikenOrder = new FikenOrder($wcOrderId, $errStatus, $exception->getMessage());
                        $fikenOrder->update();
                        FikenUtils::log($exception->getMessage(), 'ERROR Register Sale', FikenUtils::LOG_LEVEL_ERROR);
                        return $ajax ? array('error' => $exception->getMessage()) : false;
                    }
                } else {
                    //not in expected state, new - add with state = 1
                    return $ajax ? array('error' => sprintf(__('Order id: %s not in the expected state!', 'fiken'), $wcOrderId)) : false;
                }
            } catch (Exception $exception) {
                FikenUtils::log($exception->getMessage(), 'ERROR Register Sale', FikenUtils::LOG_LEVEL_ERROR);
                return $ajax ? array('error' => $exception->getMessage()) : false;
            }
            return true;
        }

        /**
         * @param $order OrderCore
         * @param $company FikenCompany
         * @param $payMethodCurrentSettings
         * @return mixed
         * @throws Exception
         */
        private static function registerSaleInner($order, $company, $payMethodCurrentSettings, &$resStatus)
        {
            /**
             * v 3.0 compatible
             */
            if (version_compare(WC_VERSION, '3.0') < 0) {
                $wcOrderId = $order->id;
                $wcOrderPaymentMethod = $order->payment_method;
                $wcOrderDate = $order->order_date;
            } else {
                $wcOrderId = $order->get_id();
                $wcOrderPaymentMethod = $order->get_payment_method();
                $wcOrderDate = $order->get_date_created() ? gmdate( 'Y-m-d H:i:s', $order->get_date_created()->getOffsetTimestamp() ) : '';
            }

            $order_products = $order->get_items();

            $acc = isset($payMethodCurrentSettings->{FikenUtils::CTRL_NAME_ACCOUNT}) ? $payMethodCurrentSettings->{FikenUtils::CTRL_NAME_ACCOUNT} : '';

            if ($acc == '') {
                throw new Exception(sprintf(__('Account for payment extension "%s" not found!', 'fiken'), $wcOrderPaymentMethod));
            }

            $saleKind = isset($payMethodCurrentSettings->{FikenUtils::CTRL_NAME_SALE_KIND}) ? $payMethodCurrentSettings->{FikenUtils::CTRL_NAME_SALE_KIND} : false;
            if (!$saleKind) {
                throw new Exception(sprintf(__('Sale type for payment extension "%s" not found!', 'fiken'), $wcOrderPaymentMethod));
            }

            $sale = new FikenSale();
            $sale->setDate(substr($wcOrderDate, 0, 10));
            $invNumber = FikenProvider::getInvoiceNumber($order);
            $sale->setIdentifier(!empty($invNumber) ? $invNumber :  $order->get_order_number());

            $lines = array();
            foreach ($order_products as $product) {

                if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
                    $netPrice = floatval($product['line_total']);
                    $vat = floatval($product['line_tax']);
                } else {
                    $netPrice = wc_round_tax_total($product['line_total']);
                    $vat = wc_round_tax_total($product['line_tax']);
                }
                $line = array(
                    "description" => $product['qty'] . " x " . $product['name'],
                    "netPrice" => $netPrice,
                    "vat" => $vat,
                    "vatType" => FikenProvider::getVatCode($product)
                );
                // Hack - all our medium VAT products are 'egentilvirkede'
                // Accounts for different products should probably be made configurable
                // or properly linked to actual Fiken products
                if ($line['vatType'] == FikenUtils::VAT_MEDIUM){
                    $line['incomeAccount'] = '3040';
                }
                $lines[] = $line;
            }


            /**
             * Order log
             */
            FikenUtils::log(var_export($order_products, true), '\'Products debug: order products\'', FikenUtils::LOG_LEVEL_INFO);

            /**
             * Shipping
             */
            foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
                if ($shipping_item['cost'] > 0) {

                    $taxes = maybe_unserialize($shipping_item['taxes']);

                    //get shipping taxes
                    $shipVat = 0;
                    $shipVatType = FikenUtils::VAT_NONE;
                    if (isset($taxes) && $taxes) {
                        foreach ($taxes as $taxCode => $taxValue) {
                            if (isset($taxValue) && $taxValue) {

	                            /**
	                             * v 3.0 compatible
	                             */
                                $taxTotalValue = 0;
                                if (is_array($taxValue)) {
                                    foreach ($taxValue as $taxValueItem) {
                                        $taxTotalValue += floatval($taxValueItem);
                                    }
                                } else {
                                    $taxTotalValue = floatval($taxValue);
                                }

                                $shipVat = wc_round_tax_total($taxTotalValue);
                                $shipVatType = FikenProvider::calculateVatCode($shipping_item['cost'], $taxTotalValue);
                                /**
                                 * takes first not null tax
                                 */
                                break;
                            }
                        }
                    }

                    $lines[] = array(
                        "description" => $shipping_item['name'],
                        "netPrice" => wc_round_tax_total($shipping_item['cost']),
                        "vat" => $shipVat,
                        // "incomeAccount" => "3090",
                        "vatType" => (($shipVat > 0) && ($shipVatType == FikenUtils::VAT_NONE) ? FikenUtils::VAT_HIGH : $shipVatType)
                    );
                }
            }

            /**
             * Shipping log
             */
            FikenUtils::log(var_export($order->get_shipping_methods(), true), '\'Shipping debug: order shipping methods\'', FikenUtils::LOG_LEVEL_INFO);

            /**
             * Fees
             */
            foreach ($order->get_fees() as $fee_item) {
                if ($fee_item['total']) {
                    $totalTax = wc_round_tax_total(floatval($fee_item['total_tax']));
                    $lines[] = array(
                        "description" => $fee_item['name'],
                        "netPrice" => wc_round_tax_total($fee_item['total']),
                        "vat" => $totalTax,
                        "vatType" => ($totalTax > 0 ? FikenUtils::VAT_HIGH : FikenUtils::VAT_NONE)
                    );
                }
            }

            /**
             * Fees log
             */
            FikenUtils::log(var_export($order->get_fees(), true), 'Fee debug: order fees', FikenUtils::LOG_LEVEL_INFO);
            FikenUtils::log(var_export($lines, true), 'ORDER LINES', FikenUtils::LOG_LEVEL_INFO);

            //check $sales["lines"] !=0
            if (FikenUtils::SKIP_EMPTY_PRICE && isset($lines)) {
                $sl = array();
                foreach ($lines as $line) {
                    if (intval($line['netPrice']) === 0) {
                        $resStatus = FikenUtils::EMPTY_PRICE_PARTIAL_ERROR_CODE;
                    } else {
                        $sl[] = $line;
                    }
                }
                $lines = $sl;
                if (!isset($lines) || !$lines) {
                    throw new Exception(__('Can not register order id: ' . $wcOrderId . '( "amount=0") ', 'fiken'), FikenUtils::EMPTY_PRICE_ERROR_CODE);
                }
            }


            /**
             * collapse lines
             */
            $packedLines = array(
                FikenUtils::VAT_HIGH => array(),
                FikenUtils::VAT_MEDIUM => array(),
                FikenUtils::VAT_LOW => array(),
                FikenUtils::VAT_NONE => array(),
                FikenUtils::VAT_EXEMPT => array(),
                FikenUtils::VAT_OUTSIDE => array(),
            );

            $vatCaptions = FikenUtils::getVatCaptions();

            foreach ($lines as $line) {
                if (!isset($packedLines[$line['vatType']]['description'])) {
                    $packedLines[$line['vatType']]['description'] = $vatCaptions[$line['vatType']];
                    $packedLines[$line['vatType']]['netPrice'] = 0;
                    $packedLines[$line['vatType']]['vat'] = 0;
                    $packedLines[$line['vatType']]['vatType'] = $line['vatType'];
                }
                    
                $packedLines[$line['vatType']]['netPrice'] += $line['netPrice'];
                $packedLines[$line['vatType']]['vat'] += $line['vat'];
                // Hack, should probably pack lines on account + vatType instead, or use proper product lines.
                if (isset($line['incomeAccount'])){
                    $packedLines[$line['vatType']]['incomeAccount'] = $line['incomeAccount'];
                }
            }

            /**
             * clear packedLines
             */
            $packedLines = array_filter($packedLines);

            /**
             * reset keys
             */
            $packedLines = array_values($packedLines);

            FikenUtils::log(var_export($packedLines, true), 'PACKED LINES', FikenUtils::LOG_LEVEL_INFO);
            /**
             * Convert all money values to fixed point
             */
            $lines = array();
            foreach ($packedLines as $line) {
                $line['netPrice'] = FikenUtils::moneyToCent($line['netPrice']);
                $line['vat'] = FikenUtils::moneyToCent($line['vat']);
                array_push($lines, $line);
            }
            FikenUtils::log(var_export($lines, true), 'FIKEN LINES', FikenUtils::LOG_LEVEL_INFO);
            
            $sale->setLines($lines);
            $sale->setKind($saleKind);
            $sale->setPaymentAccount($acc);
            $sale->setPaymentDate($sale->getDate());

            if ($sale->getKind() == FikenUtils::EXTERNAL_INVOICE) {

                /*
                 * 08.09.2015
                 */
                if ($acc == '0') {
                    $sale->setPaymentAccount('');
                    $sale->setPaymentDate('');
                }

                /** @var $fikenCustomer FikenCustomer */
                $fikenCustomer = FikenCustomer::getCustomerFromFiken($order->billing_email, $company);
                if ($fikenCustomer) {
                    $sale->setRelCustomer($fikenCustomer->getRelSelf());
                } else {
                    ////if customer not exists in fiken - creating
                    $fikenCustomer = FikenCustomer::getCustomerFromOrderWC($order);
                    //REGISTER CUSTOMER
                    $relNewCustomer = $fikenCustomer->register($company, $wcOrderId);
                    if ($relNewCustomer) {
                        $sale->setRelCustomer($relNewCustomer);
                    } else {
                        throw new Exception(sprintf(__('Can not add customer from order id=%s !', 'fiken'), $wcOrderId));
                    }
                }
            }


            //REGISTER SALE
            $err = '';
            $resNewSale = FikenUtils::call($company->getRelSales(), $sale->asArray(), true, null, null, $err);
            if ($resNewSale === false) {
                throw new Exception(__('Can not register order id: ' . $wcOrderId . ' ::  ' . $err, 'fiken'));
            } else {
                return $resNewSale['location'];
            }
        }


    }
}