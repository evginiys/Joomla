<?php
/**
 * Portmone.com Payment Plugin
 *
 * NOTICE OF LICENSE
 *
 * @category        Portmone.com
 * @package         portmone.paysystem
 * @version         1.0.0
 * @author          Portmone.com
 * @copyright       Copyright (c) 2018 Portmone.com
 * @license         Payment Card Industry Data Security Standard (PCI DSS)
 * @license url     https://www.portmone.com.ua/r3/uk/security/
 *
 * EXTENSION INFORMATION
 *
 * Joomla! 3.8.4
 */

checkForbidden();

class plgVmPaymentPortmone extends vmPSPlugin {

    const ORDER_PAYED           = 'PAYED';
    const ORDER_CREATED         = 'CREATED';
    const ORDER_REJECTED        = 'REJECTED';
    const CURRENCY              = 'UAH';
    const GATEWAY_URL           = "https://www.portmone.com.ua/gateway/";

    public static $_this = false;
    
    function __construct(&$subject, $config) {
        parent::__construct($subject, $config);
        (JFactory::getLanguage())->load('plg_vmpayment_portmone', JPATH_ADMINISTRATOR, NULL, TRUE);
        $this->tableFields  = array_keys($this->getTableSQLFields());
        $this->_loggable    = true;
        $this->_tablepkey   = 'id'; //virtuemart_portmone_id';
        $this->_tableId     = 'id'; //virtuemart_portmone_id';
        $varsToPush         = array(
            'user_id'         => array('','string'),
            'user_login'      => array('','string'),
            'user_password'   => array('','string'),
            'payment_logos'   => array('','char'),
            'user_desc'       => array('','char'),
            'status_success'  => array('','char'),
            'status_canceled' => array('','char')
        );
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
    }

    /**
     * @return string
     */
    protected function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment Portmone Table');
    }

    /**
     * @return array
     */
    function getTableSQLFields() {
        $SQLfields = array(
            'id'                            => 'int(1) UNSIGNED NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id'           => 'int(11) UNSIGNED',
            'order_number'                  => 'char(32)',
            'virtuemart_paymentmethod_id'   => 'mediumint(1) UNSIGNED',
            'payment_name'                  => 'varchar(5000)',
            'payment_order_total'           => 'decimal(15,2) NOT NULL DEFAULT \'0.00\' ',
            'payment_currency'              => 'char(3) '
        );
        return $SQLfields;
    }

    /**
     * @param VirtueMartCart $cart
     * @param int $method
     * @param array $cart_prices
     *
     * @return bool
     */
    protected function checkConditions($cart, $method, $cart_prices) {
        return true;
    }

    /**
     * @param array $logo_list
     *
     * @return html|string
     */
    protected function displayLogos($logo_list) {
        $img = "";
        if (!(empty($logo_list))) {
            $url = JURI::root() . 'plugins/vmpayment/portmone/images/';
            if (!is_array($logo_list) && $logo_list!=='no')
                $logo_list = (array) $logo_list;
            foreach ($logo_list as $logo) {
                $alt_text = substr($logo, 0, strpos($logo, '.'));
                $img .= '<img align="middle" src="' . $url . $logo . '"  alt="' . $alt_text . '" />';
            }
        }
        return $img;
    }

    /**
     * @param $user_desc
     *
     * @return string
     */
    private function _displayDesc($user_desc) {
        $description = "";
        if (!(empty($user_desc)) && $user_desc != '') {
            $description .= '<span class="' . $this->_type . '_description">' . $user_desc . '</span>';
        }
        return $description;
    }

    /**
     * @param $plugin
     *
     * @return mixed
     */
    protected function renderPluginName($plugin) {
        static $c = array();
        $idN = 'virtuemart_'.$this->_psType.'method_id';

        if(isset($c[$this->_psType][$plugin->$idN])){
            return $c[$this->_psType][$plugin->$idN];
        }

        $return = '';
        $plugin_name = $this->_psType . '_name';
        $plugin_desc = 'user_desc';
        $description = '';
        $logosFieldName = $this->_psType . '_logos';
        $logos = property_exists($plugin,$logosFieldName)? $plugin->$logosFieldName:array();
        if (!empty($logos) && $logos!== 'no') {
            $return = $this->displayLogos ($logos);
        } else {
            $return = $plugin->$plugin_name;
        }
        if (!empty($plugin->$plugin_desc)) {
            $description = $this->_displayDesc ($plugin->$plugin_desc);
        }
        $c[$this->_psType][$plugin->$idN] = $return .' '. $description;

        return $c[$this->_psType][$plugin->$idN];
    }

    /**
     * @param VirtueMartCart $cart
     * @param int $selected
     * @param $htmlIn
     *
     * @return bool
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /**
     * @param VirtueMartCart $cart
     * @param array $cart_prices
     * @param $cart_prices_name
     *
     * @return bool|null
     */
    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    /**
     * @param $jplugin_id
     *
     * @return bool|mixed
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * @param VirtueMartCart $cart
     *
     * @return null
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
        return $this->OnSelectCheck($cart);
    }

    /**
     * @param VirtueMartCart $cart
     * @param array $cart_prices
     *
     * @return null
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * @param $name
     * @param $id
     * @param $table
     *
     * @return bool
     */
    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    /**
     * @param $data
     *
     * @return bool
     */
    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }

    /**
     * @param $virtuemart_order_id
     * @param $virtuemart_paymentmethod_id
     * @param $payment_name
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * @param $order_number
     * @param $method_id
     *
     * @return mixed
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    /**
     * @param $name
     * @param $id
     * @param $data
     *
     * @return bool
     */
    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    /**
     * @param $cart
     * @param $order
     */
    function plgVmConfirmedOrder($cart, $order) {
        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null;
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        if (!class_exists('VirtueMartModelCurrency')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'currency.php');
        }

        JFactory::getLanguage()->load($filename = 'com_virtuemart', JPATH_ADMINISTRATOR);

        if (!class_exists('VirtueMartModelOrders')) {
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');
        }

        $amount = $this->getAmount($order);
        $this->_virtuemart_paymentmethod_id      = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name']                = $method->payment_name;
        $dbValues['order_number']                = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['payment_currency']            = self::CURRENCY;
        $dbValues['payment_order_total']         = $amount;
        $this->storePSPluginInternalData($dbValues);

        list($lang,) = explode('-', JFactory::getLanguage()->getTag());

        $paymentMethodID = $order['details']['BT']->virtuemart_paymentmethod_id;
        $responseUrl = JROUTE::_(JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=' . $paymentMethodID.'&order_number=' . $order['details']['BT']->order_number . '&order_pass=' . $order['details']['BT']->order_pass);
        $callbackUrl = JROUTE::_(JURI::root() . 'index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&pm=' . $paymentMethodID);

        $formFields = array(
                            'payee_id'          => $method->user_id,
                            'shop_order_number' => $order['details']['BT']->order_number .'_'. time(),
                            'bill_amount'       => $amount,
                            'description'       => $order['details']['BT']->order_number,
                            'success_url'       => $responseUrl,
                            'failure_url'       => $callbackUrl,
                            'lang'              => strtoupper($lang),
                            'encoding'          => 'UTF-8');

        $portmoneArgsArray = array();
        foreach ($formFields as $key => $value) {
            $portmoneArgsArray[] = "<input type='hidden' name='$key' value='$value'/>";
        }

        $logosFieldName = $this->_psType . '_logos';
        $logos = property_exists($method,$logosFieldName)? $method->$logosFieldName:array();
        if (!empty($logos) && $logos!== 'no') {
            $return = $this->displayLogos ($logos);
        } else {
            $return = $method->$plugin_name;
        }
        $html = '<form action="' . self::GATEWAY_URL . '" method="post" id="portmone_payment_form">
                    ' . implode('', $portmoneArgsArray) .
                '</form>' .
                "<div style='float: left; margin-right: 7px;'>".vmText::sprintf('PORTMONE_THANK_YOU_FOR_ORDER')."</div>".
                "<div>".$return."</div>".
                "<script> setTimeout(function() {
                     document.getElementById('portmone_payment_form').submit();
                 }, 100);
                </script>";

       return $this->processConfirmedOrderPaymentResponse(true, $cart, $order, $html, $this->renderPluginName($method, $order), 'P');
    }

    /**
     * @return null
     */
    public function plgVmOnPaymentNotification() {
        $callback = JRequest::get( 'post' );
        if (!empty($callback['SHOPORDERNUMBER'])){
            list($order_id,) = explode('_', $callback['SHOPORDERNUMBER']);
        }

        if (!($method = $this->getVmPluginMethod($method_id))) {
            return null; // Another method was selected, do nothing
        }

        if (!class_exists('VirtueMartModelOrders'))
            require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        if (!class_exists('plgVmPaymentPortmone'))
            require(dirname(__FILE__). DS . 'portmone.php');

        $order = new VirtueMartModelOrders();
        $order_s_id = $order->getOrderIdByOrderNumber($order_id);

        $db = JFactory::getDBO();
        $query = "SELECT * FROM #__virtuemart_orders WHERE virtuemart_order_id =".$order_s_id;
        $db->setQuery($query);
        $payment = $db->loadObject();
        $method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
        $data = array(
            "method"            => "result",
            "payee_id"          => $method->user_id ,
            "login"             => $method->user_login ,
            "password"          => $method->user_password ,
            "shop_order_number" => $_POST['SHOPORDERNUMBER'] ,
        );

        $result_portmone = $this->curlRequest(self::GATEWAY_URL, $data);
        $parseXml = $this->parseXml($result_portmone);

        if ($parseXml->orders->order->status != self::ORDER_PAYED) {
            $answer = $method->status_canceled;
            $msg = vmText::sprintf('HEADING_TITLE_FAILURE');
        } elseif ($parseXml->orders->order->status == self::ORDER_PAYED) {
            $answer = $method->status_success;
            $msg = vmText::sprintf('HEADING_TITLE_SUCCESS');
        }

        $orderitems                         = $order->getOrder($order_s_id);
        $orderitems['order_status']         = $answer;
        $orderitems['customer_notified']    = 1;
        $orderitems['virtuemart_order_id']  = $order_s_id;
        $orderitems['comments']             = 'ID: '.$order_id;
        $order->updateStatusForOneOrder($order_s_id, $orderitems, true);
        $this->redirectToOrder($msg, $orderitems);
    }

    /**
     * @param null $msg
     * @param $orderitems
     *
     * @throws Exception
     */
    function redirectToOrder ($msg = NULL, $orderitems) {
        $app = JFactory::getApplication();
        $app->redirect(JROUTE::_(JURI::root().'index.php?option=com_virtuemart&view=orders&layout=details&order_number=' . $orderitems['details']['BT']->order_number . '&order_pass=' . $orderitems['details']['BT']->order_pass), $msg);
    }

    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
        if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
            return null; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q  = 'SELECT * FROM `' . $this->_tablename . '` ' . ' WHERE `virtuemart_order_id` = '. $virtuemart_order_id;
        $db->setQuery($q);
        if (!($paymentTable = $db->loadObject())) {
            vmWarn(500, $q . " " . $db->getErrorMsg());
            return '';
        }
        $this->getPaymentCurrency($paymentTable);
        $html = '<table class="adminlist table ui-sortable">' . "\n";
        $html .= $this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_NAME', $paymentTable->payment_name);
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
        $html .= '</table>' . "\n";
        return $html;
    }

    /**
     * @param $url
     * @param $data
     * @return bool|mixed
     *
     * A request to verify the validity of payment in Portmone
     */
    public static function curlRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (200 !== intval($httpCode)) {
            return false;
        }
        return $response;
    }

    /**
     * @param $string
     * @return bool|SimpleXMLElement
     *
     * Parsing XML response from Portmone
     */
    public static function parseXml($string) {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false !== $xml) {
            return $xml;
        } else {
            return false;
        }
    }

    /**
     * @param $order
     * @return float
     *
     * Возврат суммы в нужном формате
     */
    public function getAmount($order) {
        return round($order['details']['BT']->order_total, 2);
    }
}

/**
 * проверка доступов плагина
 */
function checkForbidden() {
    defined('_JEXEC') or die('Access to ' . basename(__FILE__) . 'is forbidden.');
    if (!defined('_VALID_MOS') && !defined('_JEXEC')) die('Access to ' . basename(__FILE__) . ' is forbidden.');

    if (!class_exists('vmPSPlugin'))
        require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
}
