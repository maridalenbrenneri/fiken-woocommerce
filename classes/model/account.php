<?php
if (!class_exists('FikenAccount')) {

    include_once FIKEN_PLUGIN_DIR . 'classes/model/fiken_model.php';

    class FikenAccount extends FikenModel
    {

        public $code;
        public $name;
        public $relSelf;

        function __construct($code, $name, $relSelf)
        {
            $this->code = $code;
            $this->name = $name;
            $this->relSelf = $relSelf;
        }

        protected function getDef()
        {
            return array('code', 'name', 'relSelf');
        }

        /**
         * @param mixed $code
         */
        public function setCode($code)
        {
            $this->code = $code;
        }

        /**
         * @return mixed
         */
        public function getCode()
        {
            return $this->code;
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
         * @param $orgNumber
         * @param string $accFilter optional (all)
         * @param int $year optional (current)
         * @return FikenAccount[]|bool
         */

        public static function getAccountsFromFiken($orgNumber, $accFilter = '', $year = 0)
        {
            /** @var $res FikenAccount[] */
            $res = array();
            $res[] = new FikenAccount('0', __('Empty account','fiken') , '');
            if ($year == 0) {
                $year = date("Y");
            }
            $company = FikenCompany::getCompanyFromFiken($orgNumber);
            if ($company) {
                $rel = str_replace('{year}', $year, $company->getRelAccounts());
                $result = FikenUtils::call($rel);
                if (isset($result['body']) && $result['body']) {
                    $hal = FikenHal::fromJson($result['body'], 1);
                    $resources = $hal->getResources();
                    if (isset($resources) && $resources) {
                        if (array_key_exists(FikenUtils::FIKEN_BASE_URL . "/rel/accounts", $resources)) {
                            $accounts = $resources[FikenUtils::FIKEN_BASE_URL . "/rel/accounts"];
                            foreach ($accounts as $acc) {
                                $data = $acc->getData();
                                if ($accFilter) {

                                    //22.02.2015 change filter
//                                  if (strpos($data['code'], $accFilter) === 0) {
                                    if (preg_match($accFilter, $data['code'])) {
                                        $res[] = new FikenAccount($data['code'], $data['name'], $acc->getUri());
                                    }
                                } else {
                                    $res[] = new FikenAccount($data['code'], $data['name'], $acc->getUri());
                                }
                            }
                        }
                    }
                }
            }
            if ($res) {
                $arrKeySort = array();
                foreach ($res as $item) {
                    $arrKeySort[] = $item->getCode();
                }
                array_multisort($arrKeySort, $res);
            }
            return $res;
        }


    }
}