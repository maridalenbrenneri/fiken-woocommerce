<?php
if ( ! class_exists( 'FikenCompany' ) ) {

    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_model.php';

    class FikenCompany extends FikenModel
    {

        public $organizationNumber;
        public $name;
        public $relSelf;
        public $relSales;
        public $relAccounts;
        public $relContacts;

        function __construct($organizationNumber, $name, $relSelf, $relSales, $relAccounts, $relContacts)
        {
            $this->organizationNumber = $organizationNumber;
            $this->name = $name;
            $this->relSelf = $relSelf;
            $this->relSales = $relSales;
            $this->relAccounts = $relAccounts;
            $this->relContacts = $relContacts;
        }

        protected function getDef()
        {
            return array('organizationNumber', 'name', 'relSelf', 'relSales', 'relAccounts', 'relContacts');
        }


        public function setName($name)
        {
            $this->name = $name;
        }

        public function getName()
        {
            return $this->name;
        }

        public function setNumber($organizationNumber)
        {
            $this->organizationNumber = $organizationNumber;
        }

        public function getNumber()
        {
            return $this->organizationNumber;
        }

        public function setRelAccounts($relAccounts)
        {
            $this->relAccounts = $relAccounts;
        }

        public function getRelAccounts()
        {
            return $this->relAccounts;
        }

        public function setRelContacts($relContacts)
        {
            $this->relContacts = $relContacts;
        }

        public function getRelContacts()
        {
            return $this->relContacts;
        }

        public function setRelSales($relSales)
        {
            $this->relSales = $relSales;
        }

        public function getRelSales()
        {
            return $this->relSales;
        }

        public function setRelSelf($relSelf)
        {
            $this->relSelf = $relSelf;
        }

        public function getRelSelf()
        {
            return $this->relSelf;
        }

        /**
         * @param $orgNumber
         * @return FikenCompany
         */
        public static function getCompanyFromFiken($orgNumber)
        {
            /** @var $res FikenCompany|bool */
            $res = false;
            $companies = FikenCompany::getCompaniesFromFiken();
            foreach ($companies as $comp) {
                if ($comp->getNumber() == $orgNumber) {
                    $res = $comp;
                }
            }
            return $res;
        }

        /**
         * @return FikenCompany[]|bool
         */
        public static function getCompaniesFromFiken()
        {
            /** @var $res FikenCompany[] */
            $res = array();
            $result = FikenUtils::call("/companies");
            if ($result && isset($result['body'])) {
                $hal = FikenHal::fromJson($result['body'], 1);
                $resources = $hal->getResources();
                if (isset($resources) && $resources) {
                    if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/companies", $resources)) {
                        $companies = $resources[FikenUtils::FIKEN_BASE_URL . "/rel/companies"];
                        foreach ($companies as $comp) {
                            $links = $comp->getLinks();
                            $rSales = '';
                            if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/sales", $links) && $links[FikenUtils::FIKEN_BASE_URL . "/rel/sales"]) {
                                $rSales = $links[FikenUtils::FIKEN_BASE_URL . "/rel/sales"][0]->getUri();
                            }
                            $rAccounts = '';
                            if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/accounts", $links) && $links[FikenUtils::FIKEN_BASE_URL . "/rel/accounts"]) {
                                $rAccounts = $links[FikenUtils::FIKEN_BASE_URL . "/rel/accounts"][0]->getUri();
                            }
                            $rContacts = '';
                            if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/contacts", $links) && $links[FikenUtils::FIKEN_BASE_URL . "/rel/contacts"]) {
                                $rContacts = $links[FikenUtils::FIKEN_BASE_URL . "/rel/contacts"][0]->getUri();
                            }
                            $data = $comp->getData();
                            $res[] = new FikenCompany(preg_replace('%^(.+)/%','', $comp->getUri()), $data['name'], $comp->getUri(), $rSales, $rAccounts, $rContacts);
                        }
                    }
                }
            }
            if ($res) {
                $arrKeySort = array();
                foreach ($res as $item) {
                    $arrKeySort[] = $item->getName();
                }
                array_multisort($arrKeySort, $res);
            }
            return $res;
        }
    }
}