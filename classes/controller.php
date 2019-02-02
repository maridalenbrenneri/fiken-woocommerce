<?php

if (!class_exists('FikenController')) {

    include_once FIKEN_PLUGIN_DIR . 'classes/utils.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/provider.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_order.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/pagination.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/company.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/account.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/model/sale.php';

    class FikenController
    {

        public $data = array();

        function __construct()
        {
            $this->init();
        }


        private function init()
        {
            if (FikenUtils::getIsset('submitSettings')) {
                $this->postProcess();
            }

            $this->data['plugin_name'] = 'Fiken';
            $this->data['img_dir'] = FIKEN_PLUGIN_URL . 'views/img/';
            $this->data['admin_fiken_base_url_ajax'] = admin_url('admin-ajax.php');
            $this->data['admin_fiken_base_url'] = admin_url('admin.php?page=fiken-plugin-page');
            $this->data = array_merge($this->data, $this->getAssignData());
        }


        private function getAssignData()
        {
            $data = array();
            //companies
            $data['companies'] = FikenCompany::getCompaniesFromFiken();
            //paylist
            $data['list_pay'] = FikenProvider::getListPayWC();
            //shipping list
            $data['list_shipping'] = FikenProvider::getListShippingWC();
            //accounts
            $data['accounts'] = FikenAccount::getAccountsFromFiken(get_option(FikenUtils::CONF_FIKEN_COMPANY), FikenUtils::ACC_FILTER);
            //order satatuses
            $data['list_states_wc'] = FikenProvider::getOrderStatesWC();
            //sales king
            $data['list_sales_kind_fiken'] = FikenProvider::getSalesKindFiken();
            //fiken vat codes
            $data['list_vat_types_fiken'] = FikenProvider::getVatTypesFiken();
            //fiken vat codes for shipping
            $data['list_vat_types_fiken_for_shipping'] = FikenProvider::getVatTypesFikenForShipping();
            //taxes
            $data['list_taxes_ps'] = FikenProvider::getTaxesWC();
            //fikenStates
            $data['states_fiken'] = FikenProvider::getStatesFiken();
            //orders
            $data = array_merge($data, $this->getListOrders());
            //from setings
            $data = array_merge($data, $this->getSettingsData());
            $data['current_tab'] = FikenUtils::getIsset('submitSettings') ? 'tab-settings' : 'tab-orders';
            return $data;
        }

        private function postProcess()
        {
            if (FikenUtils::getIsset('submitSettings')) {
                if (FikenUtils::getIsset(FikenUtils::CTRL_NAME_LOGIN)) {
                    update_option(FikenUtils::CONF_FIKEN_LOGIN, FikenUtils::getValue(FikenUtils::CTRL_NAME_LOGIN));
                }
                if (FikenUtils::getIsset(FikenUtils::CTRL_NAME_PASSW)) {
                    update_option(FikenUtils::CONF_FIKEN_PASSW, FikenUtils::getValue(FikenUtils::CTRL_NAME_PASSW));
                }
                if (FikenUtils::getIsset(FikenUtils::CTRL_NAME_COMPANY)) {
                    update_option(FikenUtils::CONF_FIKEN_COMPANY, FikenUtils::getValue(FikenUtils::CTRL_NAME_COMPANY));
                }
                update_option(FikenUtils::CONF_FIKEN_PAY_METHODS, json_encode(
                    FikenUtils::parseDataFromPost(array(FikenUtils::CTRL_NAME_ACCOUNT, FikenUtils::CTRL_NAME_PAY_STATUS, FikenUtils::CTRL_NAME_SALE_KIND))));

                update_option(FikenUtils::CONF_FIKEN_VATS_MAPPING, json_encode(FikenUtils::parseDataFromPost(FikenUtils::CTRL_NAME_VAT)));

                update_option(FikenUtils::CONF_FIKEN_SHIPPING_METHODS, json_encode(FikenUtils::parseDataFromPost(FikenUtils::CTRL_NAME_SHIPPING)));

                update_option(FikenUtils::CONF_FIKEN_DEBUG_MODE, FikenUtils::getValue(FikenUtils::CTRL_NAME_DEBUG_MODE));
            }
        }


        private function getSettingsData()
        {
            $data = array();

            $data['login'] = get_option(FikenUtils::CONF_FIKEN_LOGIN);
            $data['password'] = get_option(FikenUtils::CONF_FIKEN_PASSW);
            $data['company_number'] = get_option(FikenUtils::CONF_FIKEN_COMPANY);
            $data['pdf_inv'] = get_option(FikenUtils::CONF_FIKEN_PDF_INV);
            $data['debug_mode'] = get_option(FikenUtils::CONF_FIKEN_DEBUG_MODE);

            $selectedAccounts = array();
            $selectedPayStatuses = array();
            $selectedSalesKind = array();
            $selectedVat = array();
            $selectedShipping = array();

            $res = json_decode(get_option(FikenUtils::CONF_FIKEN_PAY_METHODS));

            if ($res) {
                foreach ($res as $key => $value) {
                    $selectedAccounts[FikenUtils::CTRL_NAME_ACCOUNT . $key] = isset($value->{FikenUtils::CTRL_NAME_ACCOUNT}) ? $value->{FikenUtils::CTRL_NAME_ACCOUNT} : '';
                    $selectedPayStatuses[FikenUtils::CTRL_NAME_PAY_STATUS . $key] = isset($value->{FikenUtils::CTRL_NAME_PAY_STATUS}) ? $value->{FikenUtils::CTRL_NAME_PAY_STATUS} : '';
                    $selectedSalesKind[FikenUtils::CTRL_NAME_SALE_KIND . $key] = isset($value->{FikenUtils::CTRL_NAME_SALE_KIND}) ? $value->{FikenUtils::CTRL_NAME_SALE_KIND} : '';
                }
            }

            $res = json_decode(get_option(FikenUtils::CONF_FIKEN_VATS_MAPPING));
            if ($res) {
                foreach ($res as $key => $value) {
                    $selectedVat[FikenUtils::CTRL_NAME_VAT . $key] = $value->{FikenUtils::CTRL_NAME_VAT};
                }
            }

            $res = json_decode(get_option(FikenUtils::CONF_FIKEN_SHIPPING_METHODS));
            if ($res) {
                foreach ($res as $key => $value) {
                    $selectedShipping[FikenUtils::CTRL_NAME_SHIPPING . $key] = $value->{FikenUtils::CTRL_NAME_SHIPPING};
                }
            }

            $data['selectedAccounts'] = $selectedAccounts;
            $data['selectedPayStatuses'] = $selectedPayStatuses;
            $data['selectedSalesKind'] = $selectedSalesKind;
            $data['selectedVat'] = $selectedVat;
            $data['selectedShipping'] = $selectedShipping;

            $data = array_merge($data, $this->checkLogin($data['login'], $data['password']));

            if (version_compare(WC_VERSION, '2.6') >= 0) {
                $data = array_merge($data, $this->checkShipping($data['selectedShipping']));
            }

            return $data;
        }

        /**
         * Checking for consistency fiken shipping settings and woo shipping settings
         *
         * @param array $selectedShipping
         * @return array
         */
        private function checkShipping($selectedShipping)
        {
            $data = array();

            /**
             * Collect all the shipping option
             */
            $options = array();
            $wordWide = new WC_Shipping_Zone(0);
            $methods = array();
            foreach ($wordWide->get_shipping_methods() as $item) {
                $methods[] = array($item->id => $item->tax_status);
            }
            $options[$wordWide->get_zone_name()] = $methods;

            $zones = WC_Shipping_Zones::get_zones();
            foreach ($zones as $zone) {
                $methods = array();
                foreach ($zone['shipping_methods'] as $item) {
                    $methods[] = array($item->id => $item->tax_status);
                }
                $options[$zone['zone_name']] = $methods;
            }

            /**
             * Check for consistency
             */
            $prefixLength = strlen('fiken_shipping_');
            foreach ($options as $zone => $methods) {
                foreach ($methods as $method) {
                    foreach ($selectedShipping as $key => $itemSelectedShipping) {
                        $wcShippingCode = substr($key, $prefixLength);
                        if ($wcShippingCode && array_key_exists($wcShippingCode, $method)
                            && (($method[$wcShippingCode] === 'none' && $itemSelectedShipping !== FikenUtils::VAT_NONE) || ($method[$wcShippingCode] !== 'none' && $itemSelectedShipping === FikenUtils::VAT_NONE))
                        ) {
                            $data['warning'] = sprintf(__('Please be advised the module tax option for the shipping method "%s" does not correspond to the WC tax option for shipping zone "%s".', 'fiken'), $wcShippingCode, $zone);
                        }
                    }
                }
            }
            return $data;
        }

        private function checkLogin($user, $pass)
        {
            $data = array();
            $result = FikenUtils::call("/whoAmI", null, true, $user, $pass);
            if (isset($result['body']) && $result['body']) {
                $body = json_decode($result['body'], TRUE);
                if (array_key_exists('name', $body)) {
                    /*NOP*/
                } else {
                    $data['error_warning'] = __('Authentication failed', 'fiken');
                }
            } else {
                $data['error_warning'] = __('Authentication failed', 'fiken');
            }
            return $data;
        }


        private function getListOrders()
        {
            $data = array();
            $dt = new DateTime();

            if (FikenUtils::getIsset('filter_date_start')) {
                $filter_date_start = FikenUtils::getValue('filter_date_start');
            } else {
                $filter_date_start = $dt->format('Y-m-d');
            }

            if (FikenUtils::getIsset('filter_date_end')) {
                $filter_date_end = FikenUtils::getValue('filter_date_end');
            } else {
                $filter_date_end = $dt->format('Y-m-d');
            }

            if (FikenUtils::getIsset('filter_order_status_id')) {
                $filter_order_status_id = FikenUtils::getValue('filter_order_status_id');
            } else {
                $filter_order_status_id = 0;
            }

            if (FikenUtils::getIsset('sort')) {
                $sort = FikenUtils::getValue('sort');
            } else {
                $sort = 'posts.id';
            }

            if (FikenUtils::getIsset('asc_desc')) {
                $asc_desc = FikenUtils::getValue('asc_desc');
            } else {
                $asc_desc = 'DESC';
            }

            if (FikenUtils::getIsset('cur_page')) {
                $page = FikenUtils::getValue('cur_page');
            } else {
                $page = 1;
            }

            $data['orders'] = array();

            $filter_data = array();
            $filter_data['filter_date_start'] = $filter_date_start;
            $filter_data['filter_date_end'] = $filter_date_end;
            $filter_data['filter_order_status_id'] = $filter_order_status_id;
            $filter_data['limit'] = get_option('posts_per_page');
            $filter_data['start'] = ($page - 1) * $filter_data['limit'];
            $filter_data['sort'] = $sort;
            $filter_data['asc_desc'] = $asc_desc;

            $results = FikenOrder::getOrdersWC($filter_data);
            $order_total = FikenOrder::getTotalOrdersWC($filter_data);

            foreach ($results as $result) {
                $data['orders'][] = array(
                    'id_order' => $result['id_order'],
                    'view' => admin_url('post.php?action=edit&post=' . $result['id_order']),
                    'current_state' => $result['current_state'],
                    'status_name_fiken' => $result['status_name_fiken'],
                    'customer' => $result['customer'],
                    'total_paid' => wc_price($result['total_paid']),
                    'date_add' => substr($result['date_add'], 0, 10),
                    'date_upd' => substr($result['date_upd'], 0, 10),
                );
            }

            $url = '';

            if (FikenUtils::getIsset('filter_date_start')) {
                $url .= '&filter_date_start=' . FikenUtils::getValue('filter_date_start');
            }

            if (FikenUtils::getIsset('filter_date_end')) {
                $url .= '&filter_date_end=' . FikenUtils::getValue('filter_date_end');
            }

            if (FikenUtils::getIsset('filter_order_status_id')) {
                $url .= '&filter_order_status_id=' . FikenUtils::getValue('filter_order_status_id');
            }

            $pagination = new FikenPagination();
            $pagination->total = $order_total;
            $pagination->page = $page;
            $pagination->limit = get_option('posts_per_page');
            $pagination->url = admin_url('admin.php?page=fiken-plugin-page') . $url . '&asc_desc=' . $asc_desc . '&sort=' . $sort . '&cur_page={page}';
            $data['pagination'] = $pagination->render();

            if ($asc_desc == 'ASC') {
                $url .= '&asc_desc=DESC';
            } else {
                $url .= '&asc_desc=ASC';
            }

            $url .= '&cur_page=' . $page;

            $linkController = admin_url('admin.php?page=fiken-plugin-page') . $url;

            $data['sort_order'] = $linkController . '&sort=posts.id';
            $data['sort_customer'] = $linkController . '&sort=customer';
            $data['sort_customer'] = $linkController . '&sort=customer';
            $data['sort_status'] = $linkController . '&sort=current_state';
            $data['sort_total'] = $linkController . '&sort=total_paid';
            $data['sort_date_add'] = $linkController . '&sort=date_add';
            $data['sort_date_upd'] = $linkController . '&sort=date_upd';

            $data['filter_date_start'] = $filter_date_start;
            $data['filter_date_end'] = $filter_date_end;
            $data['filter_order_status_id'] = $filter_order_status_id;

            $data['sort'] = $sort;
            $data['asc_desc'] = $asc_desc;

            return $data;
        }


        public function ajaxProcessGetOrderStatusHistory()
        {
            $res = array();
            if (FikenUtils::getIsset('order_id')) {
                $fikenOrders = FikenOrder::getFikenOrderHistoryById(FikenUtils::getValue('order_id'));
                if ($fikenOrders) {
                    foreach ($fikenOrders as $fOrder) {
                        $res[] = $fOrder->asArray();
                    }
                }
            } else {
                $res['error'] = __('Order id is not set!', 'fiken');
            }
            die(json_encode($res));
        }


        function ajaxProcessRegisterSale()
        {
            $res = array();
            if (FikenUtils::getIsset('order_id')) {
                $result = FikenSale::registerSale(FikenUtils::getValue('order_id'), -1, true);
                if ($result) {
                    if (array_key_exists('error', $result)) {
                        $res = $result;
                    } else {
                        $res['success'] = sprintf(__('The transfer order id: %s successfully completed', 'fiken'), FikenUtils::getValue('order_id'));
                    }
                } else {
                    $res['error'] = __('Unknown error', 'fiken');
                }
                $fikenOrderResult = FikenOrder::getFikenOrderById(FikenUtils::getValue('order_id'));
                $res = array_merge($res, array('order' => $fikenOrderResult->asArray()));
            } else {
                $res['error'] = __('Order id not set!', 'fiken');
            }
            die(json_encode($res));
        }


    }
}