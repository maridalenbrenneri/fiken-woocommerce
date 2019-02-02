<?php
if (!class_exists('FikenModel')) {

    include_once FIKEN_PLUGIN_DIR . 'classes/utils.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/fikenhal.php';
    include_once FIKEN_PLUGIN_DIR . 'classes/provider.php';

    abstract class  FikenModel
    {

        /**
         * @return array
         */
        protected abstract function getDef();

        public function asArray()
        {
            $res = array();
            foreach ($this->getDef() as $item) {
                if (isset($this->{$item}) && $this->{$item}) {
                    $res[$item] = $this->{$item};
                }
            }
            return $res;
        }

    }
}