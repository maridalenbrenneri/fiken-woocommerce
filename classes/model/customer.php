<?php

if ( ! class_exists( 'FikenCustomer' ) ) {

    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_model.php';

    class FikenCustomer extends FikenModel
    {

        public $relSelf;
        public $email;
        public $name;
        /**
         * @var $address array
         */
        public $address;
        public $organizationIdentifier;
        public $customer = true; //needed for fiken serv

        function __construct($relSelf = '', $name = '', $email = '', $address = array(), $organizationIdentifier = '')
        {
            $this->name = $name;
            $this->email = $email;
            $this->relSelf = $relSelf;
            $this->address = $address;
            $this->organizationIdentifier = $organizationIdentifier;
        }


        /**
         * @param array $address
         */
        public function setAddress($address)
        {
            $this->address = $address;
        }

        /**
         * @return array
         */
        public function getAddress()
        {
            return $this->address;
        }

        /**
         * @param mixed $name
         */
        public function setName($name)
        {
            $this->name = $name;
        }

        /**
         * @return mixed
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @return mixed
         */
        public function getEmail()
        {
            return $this->email;
        }

        /**
         * @param mixed $email
         */
        public function setEmail($email)
        {
            $this->email = $email;
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
         * @return mixed
         */
        public function getOrganizationIdentifier()
        {
            return $this->organizationIdentifier;
        }

        /**
         * @param mixed $organizationIdentifier
         */
        public function setOrganizationIdentifier($organizationIdentifier)
        {
            $this->organizationIdentifier = $organizationIdentifier;
        }

        protected function getDef()
        {
            return array('name', 'email', 'address', 'organizationIdentifier', 'customer');
        }

        /**
         * @param $email
         * @param $company FikenCompany
         * @return FikenCustomer|bool
         */
        public static function getCustomerFromFiken($email, $company)
        {
            /** @var $res FikenCustomer|bool */
            $res = false;
            $result = FikenUtils::call($company->getRelContacts());
            if (isset($result['body']) && $result['body']) {
                $hal = FikenHal::fromJson($result['body'], 1);
                $resources = $hal->getResources();
                if (isset($resources) && $resources) {
                    if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/contacts", $resources)) {
                        $contacts = $resources[FikenUtils::FIKEN_BASE_URL . "/rel/contacts"];
                        foreach ($contacts as $contact) {
                            $data = $contact->getData();
                            if (array_key_exists('email', $data) && strtoupper($data['email']) == strtoupper($email)) {
                                $res = new FikenCustomer($contact->getUri(), $data['name'], $data['email'], $data['address'], '');
                            }
                        }
                    }
                }
            }
            return $res;
        }


        /**
         * @param $order_info
         * @return FikenCustomer
         */

        public static function getCustomerFromOrderWC($order)
        {
            $fikenCustomer = new FikenCustomer();
            $fikenCustomer->setName($order->billing_first_name . " " . $order->billing_last_name);
            $fikenCustomer->setEmail($order->billing_email);

            if (isset($order->billing_company) && $order->billing_company) {
                $fikenCustomer->setOrganizationIdentifier($order->billing_company);
            }

            $address = array();

            if (isset($order->billing_postcode) && $order->billing_postcode) {
                $address['postalCode'] = $order->billing_postcode;
            }
            if (isset($order->billing_city) && $order->billing_city) {
                $address['postalPlace'] = $order->billing_city;
            }
            if (isset($order->billing_address_1) && $order->billing_address_1) {
                $address['address1'] = $order->billing_address_1;
            }
            if (isset($order->billing_address_2) && $order->billing_address_2) {
                $address['address2'] = $order->billing_address_2;
            }
            if (isset($order->billing_country) && $order->billing_country) {
                $full_country = (isset(WC()->countries->countries[$order->billing_country]))
                    ? WC()->countries->countries[$order->billing_country]
                    : $order->billing_country;
                $address['country'] = $full_country;
            }
            if ($address) {
                $fikenCustomer->setAddress($address);
            }
            return $fikenCustomer;
        }

        /**
         * @param $company
         * @param $idOrder
         * @return mixed  (location url)
         * @throws Exception
         */
        public function register($company, $idOrder)
        {
            if (!$this->getName() || !$this->getEmail()) {
                throw new Exception(sprintf(__('Customer name or email not set for order id: %s !', 'fiken'), $idOrder));
            }
            $res = FikenUtils::call($company->getRelContacts(), $this->asArray());
            if ($res === false) {
                throw new Exception(sprintf(__('Can not add customer from order id: %s !', 'fiken'), $idOrder));
            } else {
                return $res['location'];
            }
        }

    }
}