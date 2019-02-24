<?php

if (!class_exists('FikenUtils')) {

    include_once ABSPATH . 'wp-includes/version.php';

    class FikenUtils
    {
        const FIKEN_VERSION = "1.17-MB-3";

        const ACC_FILTER = "/^19[26]0:/";
        const FIKEN_BASE_URL = "https://fiken.no/api/v1";

        const EXTERNAL_INVOICE = "EXTERNAL_INVOICE";
        const CASH_SALE = "CASH_SALE";

        const VAT_HIGH = "HIGH";
        const VAT_MEDIUM = "MEDIUM";
        const VAT_LOW = "LOW";
        const VAT_EXEMPT = "EXEMPT";
        const VAT_OUTSIDE = "OUTSIDE";
        const VAT_NONE = "NONE";

        const SKIP_EMPTY_PRICE = true;
        const EMPTY_PRICE_ERROR_CODE = 4;
        const EMPTY_PRICE_PARTIAL_ERROR_CODE = 5;

        const LOG_LEVEL_INFO = 1;
        const LOG_LEVEL_WARNING = 2;
        const LOG_LEVEL_ERROR = 3;
        const LOG_LEVEL_CRASH = 4;

        const DEFAULT_LOG_LEVEL = self::LOG_LEVEL_ERROR;

        const PREFIX_CONF = 'fiken_v1_';
        const CONF_FIKEN_LOGIN = 'fiken_v1_login';
        const CONF_FIKEN_PASSW = 'fiken_v1_password';
        const CONF_FIKEN_COMPANY = 'fiken_v1_company';
        const CONF_FIKEN_PAY_METHODS = 'fiken_v1_pay_methods';
        const CONF_FIKEN_VATS_MAPPING = 'fiken_v1_vats_mapping';
        const CONF_FIKEN_PDF_INV = 'fiken_v1_pdf_inv';
        const CONF_FIKEN_DEBUG_MODE = 'fiken_v1_debug_mode';

        const CTRL_NAME_ACCOUNT = 'fiken_account_';
        const CTRL_NAME_PAY_STATUS = 'fiken_pay_status_';
        const CTRL_NAME_SALE_KIND = 'fiken_sale_kind_';
        const CTRL_NAME_VAT = 'fiken_vat_';
        const CTRL_NAME_LOGIN = 'fiken_login';
        const CTRL_NAME_PASSW = 'fiken_passw';
        const CTRL_NAME_COMPANY = 'fiken_company';
        const CTRL_NAME_PDF_INV = 'fiken_pdf_inv';
        const CTRL_NAME_DEBUG_MODE = 'fiken_debug_mode';

        public static function getVatCaptions()
        {
            return array(
                self::VAT_HIGH => 'Ordrelinjer høy mva. sats',
                self::VAT_MEDIUM => 'Ordrelinjer middels mva. sats',
                self::VAT_LOW => 'Ordrelinjer lav mva. sats',
                self::VAT_NONE => 'Ordrelinjer',
                self::VAT_EXEMPT => 'Ordrelinjer fritatt for mva.',
                self::VAT_OUTSIDE => 'Ordrelinjer utenfor avgiftsområdet'
            );
        }

        public static function log($mes, $title = '', $severity = self::LOG_LEVEL_INFO)
        {
            $debug_mode = get_option(self::CONF_FIKEN_DEBUG_MODE);
	        if ($debug_mode || (int)$severity >= (int)self::DEFAULT_LOG_LEVEL) {
		        if (version_compare(WC_VERSION, '3.0') < 0) {
			        include_once FIKEN_PLUGIN_DIR . '../woocommerce/includes/class-wc-logger.php';
			        $log = new WC_Logger();
			        $log->add('fiken', $title . ' :: ' . $mes);
		        } else {
			        $logger = wc_get_logger();
			        $logger->log('debug', $title . ' :: ' . $mes, array( 'source' => 'fiken' ));
		        }
	        }
        }


        public static function call($rel, $data = null, $json = true, $login = null, $passw = null, &$error = '')
        {
            if (strpos($rel, self::FIKEN_BASE_URL) === false) {
                $rel = self::FIKEN_BASE_URL . $rel;
            }

            if (!isset($login) || !$login) {
                $login = get_option(self::CONF_FIKEN_LOGIN);
            }
            if (!isset($passw) || !$passw) {
                $passw = get_option(self::CONF_FIKEN_PASSW);
            }

            $headers = $json ? array('Content-Type: application/json', 'Accept: application/hal+json, application/json') : array('Content-Type: multipart/form-data');
            global $wp_version;
            array_push($headers, 'User-Agent: ' . 'fiken/' . self::FIKEN_VERSION . ' woocommerce/' . WC_VERSION . ' wordpress/' . $wp_version);
            self::log($rel, 'CALL URI', self::LOG_LEVEL_INFO);
            if (isset($data) && $data) {
                self::log($json ? json_encode($data) : var_export($data, true), 'CALL DATA', self::LOG_LEVEL_INFO);
            }

            $ch = curl_init();

            if (!$ch) {
                self::log('Connect failed with CURL', 'CURL ERROR', self::LOG_LEVEL_ERROR);
                return false;
            }

            @curl_setopt($ch, CURLOPT_URL, $rel);
            @curl_setopt($ch, CURLOPT_USERPWD, $login . ":" . $passw);
            @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            @curl_setopt($ch, CURLOPT_HEADER, true);
            @curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            @curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
            @curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if (isset($data) && $data) {
                @curl_setopt($ch, CURLOPT_POST, true);
                @curl_setopt($ch, CURLOPT_POSTFIELDS, $json ? json_encode($data) : $data);
            }

            $result = curl_exec($ch);
            $info = curl_getinfo($ch);

            self::log(stripslashes(json_encode($info)), 'CURL_INFO', self::LOG_LEVEL_INFO);

            if ($result === false) {
                $log_data = array(
                    'curl_error' => curl_error($ch),
                    'curl_errno' => curl_errno($ch)
                );
                curl_close($ch);
                self::log(stripslashes(json_encode($log_data)), 'CURL FAILED', self::LOG_LEVEL_ERROR);
                return false;
            }
            curl_close($ch);

            $header = substr($result, 0, $info['header_size']);
            $body = substr($result, $info['header_size']);

            $bodyLen = strlen($body);
            $logbody = $body;
            if ($bodyLen > 256){
                $logbody = substr($body, 0, 256) . ' ... ';
            }

            self::log($header, 'RESULT_HEADER', self::LOG_LEVEL_INFO);
            self::log($logbody, 'RESULT_BODY', self::LOG_LEVEL_INFO);

            if (intval($info['http_code']) > 300) {
                self::log($info['http_code'], 'HTTP status error code', self::LOG_LEVEL_ERROR);
                $errMes = json_decode($body, true);
                if (isset($errMes) && isset($errMes[0]['message'])) {
                    $error = $errMes[0]['message'];
                }
                return false;
            }
            return array('location' => self::getHeaderInfo('Location:', $header), 'body' => $body);
        }

        private static function getHeaderInfo($key, $header)
        {
            $res = '';
            $header = explode("\n", $header);
            foreach ($header as $head) {
                if (stripos($head, $key) !== false) {
                    $res = trim(str_ireplace($key, '', $head));
                }
            }
            return $res;
        }

        public static function moneyToCent($value)
        {
            return round($value * 100);
        }

        public static function getStatesFromSettings()
        {
            $res = array();
            $methods = json_decode(get_option(self::CONF_FIKEN_PAY_METHODS));
            if (isset($methods) && $methods) {
                foreach ($methods as $key => $value) {
                    if (isset($value->{self::CTRL_NAME_PAY_STATUS}) && !in_array(intval($value->{self::CTRL_NAME_PAY_STATUS}), $res, true)) {
                        $res[] = $value->{self::CTRL_NAME_PAY_STATUS};
                    }
                }
            }
            return $res;
        }


        private static function getPostValueByPartialKey($keyPartial)
        {
            if (!isset($keyPartial) || empty($keyPartial) || !is_string($keyPartial)) {
                return false;
            }
            $res = array();
            foreach ($_POST as $key => $value) {
                if (strpos($key, $keyPartial) !== false) {
                    $res[$key] = $value;
                }
            }
            return $res;
        }


        private static function parsePostControlData($keyPartial, $data)
        {
            $res = array();
            foreach ($data as $key => $value) {
                $code = explode($keyPartial, $key);
                if (count($code) == 2) {
                    $res[$code[1]] = array($keyPartial => $value);
                }
            }
            return $res;
        }


        /**
         * @param $controlNamesPartial
         * @return array
         */
        public static function parseDataFromPost($controlNamesPartial)
        {
            $res = array();
            $data = array();

            if (!is_array($controlNamesPartial)) {
                $controlNamesPartial = array($controlNamesPartial);
            }

            foreach ($controlNamesPartial as $item) {
                $data[$item] = self::parsePostControlData($item, self::getPostValueByPartialKey($item));
            }
            //merge
            foreach ($data as $key => $value) {
                foreach ($value as $key_1 => $value_1) {
                    $res[$key_1] = array_merge(isset($res[$key_1]) ? $res[$key_1] : array(), $value_1);
                }
            }
            return $res;
        }

        public static function getIsset($key)
        {
            if (!isset($key) || empty($key) || !is_string($key))
                return false;
            return isset($_POST[$key]) ? true : (isset($_GET[$key]) ? true : false);
        }

        public static function getValue($key, $default_value = false)
        {
            if (!isset($key) || empty($key) || !is_string($key))
                return false;
            $ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $default_value));

            if (is_string($ret) === true)
                $ret = urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret)));
            return !is_string($ret) ? $ret : stripslashes($ret);
        }
    }
}
