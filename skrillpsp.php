<?php

if (!defined('_PS_VERSION_'))
    exit;

class SkrillPsp extends PaymentModule
    {
    public static $views_dir    = 'skrillpsp/views/templates';
    public static $js_dir       = 'skrillpsp/js';
    public static $payments     = array('cc'    => 'Credit cards',
                                    'wlt'   => 'Skrill Digital Wallet',
                                    'obt'   => 'Online Bank Transfer',
                                    'idl'   => 'iDEAL',
                                    'did'   => 'Lastschrift (ELV)',
                                    'jcb'   => 'Credit/debit card JCB');

    const validation_result_ok_e    = 'ACK';
    const validation_result_error_e = 'NOK';
    const va_transaction_pending_e  = 'VA.DB.80.00';
    
    const payment_code_qc_e         = 'VA.DB';
    const payment_code_rg_e         = 'CC.RG';
    const payment_code_pa_e         = 'CC.PA';
    const payment_code_db_e         = 'CC.DB';
    
    const processing_status_code_ok_e   = '90';
    const processing_reason_code_ok_e   = '00';
    const waiting_status_code_ok_e      = '80';
    const waiting_reason_code_ok_e      = '00';
    
    private $_redirecturl = '';
    private $_redirectparams = array();
    
    private $_configErrors;
    private $_urls;
    
    public function __construct ()
	{
	$this->name = 'skrillpsp';
	$this->tab = 'payments_gateways';
	$this->version = '1.0';
	$this->author = 'Skrill Holdings Ltd.';
	$this->need_instance = 1;
        $this->is_configurable = 1;
        
        $this->currencies = true;
	$this->currencies_mode = 'radio';
	
        parent::__construct();
	
        $this->page = basename(__FILE__, '.php');
        
	$this->displayName = $this->l('Skrill PSP Payments');
	$this->description = $this->l('Official Skrill PSP Payment Module');
	}
	
    public function install ()
	{
        if (parent::install() == false ||
            !$this->registerHook('payment') ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('backOfficeHeader'))
            return false;

        Configuration::updateValue('SKRILLPSP_TESTMODE', 0);
	Configuration::updateValue('SKRILLPSP_CHANNEL', '');
        Configuration::updateValue('SKRILLPSP_SENDER', '');
        Configuration::updateValue('SKRILLPSP_LOGIN', '');
        Configuration::updateValue('SKRILLPSP_PASSWORD', '');
        Configuration::updateValue('SKRILLPSP_TRANSACTION_MODE', 0);
        
        Configuration::updateValue('SKRILLPSP_TESTPOST_URL',
                                   'https://test.nextgenpay.com/frontend/payment.prc');
        Configuration::updateValue('SKRILLPSP_TESTXML_URL',
                                   'https://test.nextgenpay.com/payment/ctpe');
        Configuration::updateValue('SKRILLPSP_POST_URL',
                                   'https://nextgenpay.com/frontend/payment.prc');
        Configuration::updateValue('SKRILLPSP_XML_URL',
                                   'https://nextgenpay.com/payment/ctpe');
            
        foreach (self::$payments as $pmethod => $plabel)
            {
            $pmethodname = 'SKRILLPSP_PMETHOD_' . strtoupper($pmethod);
            Configuration::updateValue($pmethodname, 0);
            Configuration::updateValue($pmethodname . '_ORDER', 0); 
            Configuration::updateValue($pmethodname . '_COUNTRIES', '');
            }
            
        if (!Db::getInstance()->Execute('CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'skrillpsp_trns` (
                                        `order_id`  int(10) unsigned NOT NULL,
                                        `cart_id`   int(10) unsigned NOT NULL,
                                        `trn_id`    varchar(128)    NOT NULL,
                                        `unique_id` varchar(128)    NOT NULL,
                                        `payment_code` char(5)      NOT NULL,
                                        `amount`    decimal         NOT NULL,
                                        `currency`  char(3)         NOT NULL,
                                        `processing_code` varchar(11) NOT NULL,
                                        `auxdata`   blob            DEFAULT NULL,
                                        
                                        PRIMARY KEY (`order_id`)
                                        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8'))
	    return false;

        return true;
        }
    
    public function getContent ()
        {
        $this->_saveConfiguration();
        
        foreach (Currency::getCurrencies() as $currency)
            {
            $currency_iso_code = $currency['iso_code'];
            $channels[$currency_iso_code]['channel'] = Configuration::get('SKRILLPSP_CHANNEL_' . $currency_iso_code);
            $channels[$currency_iso_code]['sender'] = Configuration::get('SKRILLPSP_SENDER_' . $currency_iso_code);
            $channels[$currency_iso_code]['login'] = Configuration::get('SKRILLPSP_LOGIN_' . $currency_iso_code);
            $channels[$currency_iso_code]['password'] = Configuration::get('SKRILLPSP_PASSWORD_' . $currency_iso_code);
            $channels[$currency_iso_code]['testmode'] = !Configuration::get('SKRILLPSPS_TESTMODE_' . $currency_iso_code);
            }
        
        $paymentmethods = array();
        foreach (self::$payments as $pmethod => $plabel)
            {
            $pmethodname = 'SKRILLPSP_PMETHOD_' . strtoupper($pmethod);
            array_push($paymentmethods, array('pmethodshort'    => $pmethod,
                                              'pmethodenabled'  => Configuration::get($pmethodname),
                                              'pmethodorder'    => Configuration::get($pmethodname . '_ORDER'),
                                              'pmethodlabel'    => Configuration::get($pmethodname . '_LABEL') ?
                                                                    Configuration::get($pmethodname . '_LABEL') :
                                                                    $plabel,
                                              'pmethodcountries'
                                                                => unserialize(Configuration::get($pmethodname . '_COUNTRIES'))
                                              ));
            }

        $this->context->smarty->assign(array('Currencies'   => Currency::getCurrencies(),
                                             'Countries'    => Country::getCountries((int)Language::getIdByIso('EN')),
                                             'channel'      => Configuration::get('SKRILLPSP_CHANNEL'),
                                             'sender'       => Configuration::get('SKRILLPSP_SENDER'),
                                             'login'        => Configuration::get('SKRILLPSP_LOGIN'),
                                             'password'     => Configuration::get('SKRILLPSP_PASSWORD'),
                                             'testmode'     => !Configuration::get('SKRILLPSP_TESTMODE'),
                                             'transactionmode'
                                                            => Configuration::get('SKRILLPSP_TRANSACTION_MODE'),
                                             'tmodes'       => array(array('value' => 'PA',
                                                                           'label' => 'Pre-authorization'),
                                                                     array('value' => 'DB',
                                                                           'label' => 'Authorization')),
                                             'channels'     => $channels,
                                             'paymentmethods'
                                                            => $paymentmethods,
                                             'paymentmethodssz'
                                                            => count($paymentmethods)));
            
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . self::$views_dir . '/back/back_office.tpl');
        }
        
    public function hookBackOfficeHeader ()
        {
        $this->context->smarty->assign('skrillmodule', _MODULE_DIR_ . $this->name);
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . self::$views_dir . '/back/header.tpl');
        }
        
    public function hookPayment ($params)
        {
        $currency = $this->context->currency;
        
        $currency_iso_code = $currency->iso_code;
        $channel = array();
        $channel['transactionmode'] = Configuration::get('SKRILLPSP_TRANSACTION_MODE');
        $channel['channel'] = Configuration::get('SKRILLPSP_CHANNEL_' . $currency_iso_code);
        $channel['sender'] = Configuration::get('SKRILLPSP_SENDER_' . $currency_iso_code);
        $channel['login'] = Configuration::get('SKRILLPSP_LOGIN_' . $currency_iso_code);
        $channel['password'] = Configuration::get('SKRILLPSP_PASSWORD_' . $currency_iso_code);
        $channel['testmode'] = Configuration::get('SKRILLPSPS_TESTMODE_' . $currency_iso_code);
        if (!strlen($channel['channel']) ||
            !strlen($channel['sender']) ||
            !strlen($channel['login']) ||
            !strlen($channel['password']))
            {
            $channel['channel'] = Configuration::get('SKRILLPSP_CHANNEL');
            $channel['sender'] = Configuration::get('SKRILLPSP_SENDER');
            $channel['login'] = Configuration::get('SKRILLPSP_LOGIN');
            $channel['password'] = Configuration::get('SKRILLPSP_PASSWORD');
            $channel['testmode'] = Configuration::get('SKRILLPSP_TESTMODE');
            }
        
        $secure_url = $channel['testmode'] ? Configuration::get('SKRILLPSP_TESTPOST_URL') :
                                            Configuration::get('SKRILLPSP_POST_URL');
        
        $pmethods = array();                                
        foreach (self::$payments as $payment_key => $payment_val)
            {
            if (Configuration::get('SKRILLPSP_PMETHOD_' . strtoupper($payment_key)))
                array_push($pmethods, strtoupper($payment_key));
            }
            
        $payments_config = array();
        foreach ($pmethods as $pmethod)
            {
            $requestdata = $this->_prepareRequest($channel);
            switch ($pmethod)
                {
                case 'CC' :
                    $requestdata['PAYMENT.CODE'] = 'CC.RG';
                    $requestdata['FRONTEND.ENABLED'] = 'true';
                    $requestdata['FRONTEND.PM.DEFAULT_DISABLE_ALL'] = 'true';
                    $requestdata['FRONTEND.PM.1.ENABLED'] = 'true';
                    $requestdata['FRONTEND.PM.1.METHOD'] = $pmethod;
                    $requestdata['FRONTEND.JSCRIPT_PATH'] = 'https://' . $_SERVER['HTTP_HOST'] .
                                                _MODULE_DIR_ . $this->name . '/js/skrillpsp.js';
                    $requestdata['FRONTEND.CSS_PATH'] = 'https://' . $_SERVER['HTTP_HOST'] .
                                                _MODULE_DIR_ . $this->name . '/css/skrillpsp_remote.css';
                    break;
                case 'JCB' :
                    $requestdata['NAME.TITLE'] = '';
                    $requestdata['NAME.COMPANY'] = '';
                    $requestdata['NAME.GIVEN'] = '';
                    $requestdata['NAME.FAMILY'] = '';
                case 'WLT' :
                case 'OBT' :
                case 'IDL' :
                case 'DID' :
                    $requestdata['PAYMENT.CODE'] = 'VA.DB';
                    $requestdata['ACCOUNT.BRAND'] = 'MONEYBOOKERS';
                    $requestdata['CRITERION.MONEYBOOKERS_payment_methods'] = $pmethod;
                    $requestdata['FRONTEND.ENABLED'] = 'true';
                    $requestdata['FRONTEND.COLLECT_DATA'] = 'false';
                    $requestdata['FRONTEND.PM.DEFAULT_DISABLE_ALL'] = 'true';
                    $requestdata['FRONTEND.PM.1.SUBTYPES'] = 'MONEYBOOKERS';
                    $requestdata['FRONTEND.PM.1.ENABLED'] = 'true';
                    $requestdata['FRONTEND.PM.1.METHOD'] = 'VA';
                    $requestdata['PRESENTATION.USAGE'] = '';
                    $requestdata['FRONTEND.JSCRIPT_PATH'] = 'https://' . $_SERVER['HTTP_HOST'] .
                                                _MODULE_DIR_ . $this->name . '/js/skrillpsp_va.js';
                    $requestdata['FRONTEND.CSS_PATH'] = 'https://' . $_SERVER['HTTP_HOST'] .
                                                _MODULE_DIR_ . $this->name . '/css/skrillpsp_va.css';
                    break;
                }

            $response = $this->_execPOSTRequest($requestdata, $secure_url);

            if ((array_key_exists('PROCESSING.RESULT', $response) &&
                $response['PROCESSING.RESULT'] == self::validation_result_ok_e &&
                array_key_exists('PROCESSING.CODE', $response) &&
                $response['PROCESSING.CODE'] == self::va_transaction_pending_e)
                ||
                (isset($response['FRONTEND.REDIRECT_URL']) &&
                !preg_match('/error=(\d+)$/', $response['FRONTEND.REDIRECT_URL'])))
                {
                $payments_config[$pmethod] = array();
                $payments_config[$pmethod]['plabel'] = self::$payments[strtolower($pmethod)];
                $payments_config[$pmethod]['pmethod'] = $pmethod;
                $payments_config[$pmethod]['sid'] = isset($response['PROCESSING.REDIRECT.PARAMETER.sid']) ?
                                                            $response['PROCESSING.REDIRECT.PARAMETER.sid'] : '';
                $payments_config[$pmethod]['redirect_url'] = $response['FRONTEND.REDIRECT_URL'];
                $payments_config[$pmethod]['logo'] = 'skrillpsp_' . strtolower($pmethod) . '.gif';
                }
            }
        $this->context->smarty->assign(array('payments_config' => $payments_config,
                                             'imgroot' => _MODULE_DIR_ . $this->name . '/images'));
        
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . self::$views_dir . '/front/skrillpsp.tpl');
        }
        
    public function hookPaymentReturn ()
	{
	if (!$this->active)
	    return;
	
	$lcart_id = $_GET['id_cart'] ? $_GET['id_cart'] : $this->context->cookie->ScartID;
        
        $trndata = Db::getInstance()->getRow('
                SELECT `order_id`, `trn_id`
                FROM `' . _DB_PREFIX_ . 'skrillpsp_trns`
                WHERE `cart_id` = ' . (int)$lcart_id);
        $key = split("_", $trndata['trn_id']);
        
	$this->context->smarty->assign('order_id', $trndata['order_id']);
	
	return $this->context->smarty->fetch(_PS_MODULE_DIR_ . self::$views_dir . '/front/confirmation.tpl');
	}

    public function getStatusUrl ()
        {
        return 'https://' . $_SERVER['HTTP_HOST'] . _MODULE_DIR_ . $this->name . '/validation.php';    
        }
        
    public function getSuccessUrl ($cart_id = 0)
        {
        $lcart_id = $cart_id ? $cart_id : $this->context->cookie->ScartID;
        
        $trndata = Db::getInstance()->getRow('
                SELECT `order_id`, `trn_id`
                FROM `' . _DB_PREFIX_ . 'skrillpsp_trns`
                WHERE `cart_id` = ' . (int)$lcart_id);
        $key = split("_", $trndata['trn_id']);

        return  'https://' . $_SERVER['HTTP_HOST'] . Context::getContext()->shop->getBaseURI() .
                'index.php?controller=order-confirmation&id_cart=' .
                $lcart_id . '&id_module=' . $this->id . '&id_order=' . $trndata['order_id'] .
                '&key=' . $key[2];
        }
        
    public function getErrorUrl ()
        {
        return  'https://' . $_SERVER['HTTP_HOST'] . Context::getContext()->shop->getBaseURI() .
                'index.php?controller=order&step=3' ;
        }

    public function getRedirectUrl ()
        {
        return $this->_redirecturl;
        }
        
    public function getRedirectParams ()
        {
        return $this->_redirectparams;
        }
      
    public function save3DSRedirectdata ($auxdata, $cart_id)
        {
        Db::getInstance()->Execute('UPDATE `' . _DB_PREFIX_ . 'skrillpsp_trns` SET `auxdata`= \'' .
                                   $auxdata . '\' WHERE cart_id=' . (int)$cart_id);
	}

    public function fetch3DSRedirectdata ($cart_id)
        {
        $auxdata = Db::getInstance()->getRow('
                SELECT `auxdata`
                FROM `' . _DB_PREFIX_ . 'skrillpsp_trns`
                WHERE `cart_id` = ' . (int)$cart_id);

        $data3ds = unserialize($auxdata);
        $this->_redirecturl = $data3ds['redirecturl'];
        $this->_redirectparams = $data3ds['redirectparams'];
        }

    public function preauthorizeRequest ($data)
        {
        $requestfields = array('TRANSACTION_RESPONSE',
                               'PRESENTATION_AMOUNT',
                               'IDENTIFICATION_UNIQUEID',
                               'IDENTIFICATION_SHORTID',
                               'PRESENTATION_USAGE',
                               'PRESENTATION_CURRENCY',
                               'IDENTIFICATION_TRANSACTIONID',
                               'NAME_GIVEN',
                               'NAME_FAMILY',
                               'ADDRESS_STREET',
                               'ADDRESS_ZIP',
                               'ADDRESS_CITY',
                               'ADDRESS_STATE',
                               'ADDRESS_COUNTRY',
                               'CONTACT_EMAIL',
                               'CONTACT_IP',
                               'FRONTEND_SESSION_ID');

        $currency_iso_code = $data['PRESENTATION_CURRENCY'];
        $channel = array();
        $channel['transactionmode'] = Configuration::get('SKRILLPSP_TRANSACTION_MODE');
        $channel['channel'] = Configuration::get('SKRILLPSP_CHANNEL_' . $currency_iso_code);
        $channel['sender'] = Configuration::get('SKRILLPSP_SENDER_' . $currency_iso_code);
        $channel['login'] = Configuration::get('SKRILLPSP_LOGIN_' . $currency_iso_code);
        $channel['password'] = Configuration::get('SKRILLPSP_PASSWORD_' . $currency_iso_code);
        $channel['testmode'] = Configuration::get('SKRILLPSPS_TESTMODE_' . $currency_iso_code);
        if (!strlen($channel['channel']) ||
            !strlen($channel['sender']) ||
            !strlen($channel['login']) ||
            !strlen($channel['password']))
            {
            $channel['channel'] = Configuration::get('SKRILLPSP_CHANNEL');
            $channel['sender'] = Configuration::get('SKRILLPSP_SENDER');
            $channel['login'] = Configuration::get('SKRILLPSP_LOGIN');
            $channel['password'] = Configuration::get('SKRILLPSP_PASSWORD');
            $channel['testmode'] = Configuration::get('SKRILLPSP_TESTMODE');
            }
        
        $secure_url = $channel['testmode'] ? Configuration::get('SKRILLPSP_TESTXML_URL') :
                                            Configuration::get('SKRILLPSP_XML_URL');
        
        //$this->context->cookie->SkrillPSPUniqueID = $data['IDENTIFICATION_UNIQUEID'];
        $requestdata = array();
        
        $requestdata['TRANSACTION.CHANNEL'] = $channel['channel'];
        $requestdata['SECURITY.SENDER'] = $channel['sender'];
        $requestdata['USER.LOGIN'] = $channel['login'];
        $requestdata['USER.PWD'] = $channel['password'];
        $requestdata['SECURITY.TOKEN'] = '';
        
        foreach ($requestfields as $fieldid)
            {
            $rfieldid = preg_replace('/_/', '.', $fieldid, 1);
            if ($rfieldid == 'IDENTIFICATION.UNIQUEID')
                $requestdata['ACCOUNT.REGISTRATION'] = $data[$fieldid];
            else 
                $requestdata[$rfieldid] = $data[$fieldid];
            }
        $requestdata['TRANSACTION.RESPONSE'] = 'ASYNC';
        $requestdata['TRANSACTION.MODE'] = $channel['testmode'] ? 'CONNECTOR_TEST' : 'LIVE';
        $requestdata['FRONTEND.RESPONSE_URL'] = $this->getStatusUrl();
        $requestdata['CRITERION.MONEYBOOKERS_recipient_description'] = Configuration::get('PS_SHOP_NAME');
        $requestdata['CRITERION.MONEYBOOKERS_hide_login'] = 1;
        $requestdata['PAYMENT.CODE'] = $channel['transactionmode'] == 'PA' ? 'CC.PA' : 'CC.DB';
        
        $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
        fprintf($logh, "%s\n\n\n", var_export($requestdata, true));
        fclose($logh);
        
        $xml = file_get_contents(_PS_MODULE_DIR_ . $this->name . '/xml/general.xml');
        foreach ($requestdata as $key => $val)
            {
            $xml = str_replace('{{'.$key.'}}', htmlspecialchars($val), $xml);
            }
        $xml = preg_replace('/\{\{[^\{\}]+\}\}/', '', $xml);
        
        $response = $this->_execXMLRequest($xml, $secure_url);
        $parsed_response = $this->_parseXMLResponse($response);
        $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
        fprintf($logh, "%s\n\n\n", var_export($parsed_response, true));
        
        $codes = split("\.", $parsed_response['PROCESSING_CODE']);
        fprintf($logh, "%s\n\n\n", var_export($codes, true));
        
        fclose($logh);
        
        if ($codes[2] == self::processing_status_code_ok_e &&
            $codes[3] == self::processing_reason_code_ok_e)
            {
            $cart_ids = split("_", $parsed_response['TRANSACTIONID']);
            if (empty(Context::getContext()->link))
                Context::getContext()->link = new Link();
            $this->validateOrder((int)$cart_ids[0], Configuration::get('PS_OS_PAYMENT'), (float)($parsed_response['AMOUNT']),
                        $this->displayName, $this->l('Skrill Transaction ID: ') . $parsed_response['TRANSACTIONID'],
                        array('transaction_id' => $parsed_response['TRANSACTIONID'],
                        'payment_status' => $parsed_response['RETURN']), null, false, $cart_ids[2]);
            $parsed_response['ORDER_ID'] = (int)$this->currentOrder;
            $parsed_response['CART_ID'] = (int)$cart_ids[0];
            $this->saveTransaction($parsed_response);
            
            return true;
            }
        elseif ($codes[2] == self::waiting_status_code_ok_e &&
                $codes[3] == self::waiting_reason_code_ok_e)
            {
            $parsed_response_3ds = $this->_process3DSResponse($response);
            $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
            fprintf($logh, "%s\n\n\n", var_export($parsed_response_3ds, true));
            fclose($logh);
            
            $this->_redirecturl = $parsed_response_3ds['REDIRECT'];
            $this->_redirectparams = $parsed_response_3ds['PARAMETER'];
            /*$params3ds = '';
            foreach ($parsed_response_3ds['PARAMETER'] as $key => $val)
                $params3ds .= "$key=$val" . '&';
            $params3ds = substr($params3ds, 0, -1);
            $this->_redirecturl = $parsed_response_3ds['REDIRECT'] . "?$params3ds";*/
            
            $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
            fprintf($logh, "%s\n\n\n", var_export($this->_redirecturl, true));
            fclose($logh);
            
            return true;
            }
            
        return false;
        }

    public function saveVADBTransaction ($data)
        {
        $fields = array('IDENTIFICATION_TRANSACTIONID' => 'TRANSACTIONID',
                        'PRESENTATION_AMOUNT' => 'AMOUNT',
                        'PRESENTATION_CURRENCY' => 'CURRENCY',
                        'IDENTIFICATION_UNIQUEID' => 'IDENTIFICATION_UNIQUEID');
        $codes = split("\.", $data['PROCESSING_CODE']);
        
        if ($codes[2] == '90' &&
            $codes[3] == '00')
            {
            $cart_ids = split("_", $data['IDENTIFICATION_TRANSACTIONID']);
            if (empty(Context::getContext()->link))
                Context::getContext()->link = new Link();
            $this->validateOrder((int)$cart_ids[0], Configuration::get('PS_OS_PAYMENT'), (float)($data['PRESENTATION_AMOUNT']),
                        $this->displayName, $this->l('Skrill Transaction ID: ') . $data['IDENTIFICATION_TRANSACTIONID'],
                        array('transaction_id' => $data['IDENTIFICATION_TRANSACTIONID'],
                        'payment_status' => $data['PROCESSING_RETURN']), null, false, $cart_ids[2]);
            
            $parsed_response = array();
            foreach ($data as $key => $val)
                $parsed_response[$fields[$key] ? $fields[$key] : $key] = $val;
            $parsed_response['ORDER_ID'] = (int)$this->currentOrder;
            $parsed_response['CART_ID'] = (int)$cart_ids[0];
            $this->saveTransaction($parsed_response);
            
            return true;
            }
            
        return false;
        }

    public function saveTransaction ($data)
        {
        Db::getInstance()->Execute('INSERT INTO `' . _DB_PREFIX_ . 'skrillpsp_trns` (`order_id`,
                                                                        `trn_id`,
                                                                        `unique_id`,
                                                                        `payment_code`,
                                                                        `amount`,
                                                                        `currency`,
                                                                        `processing_code`,
                                                                        `cart_id`) 
			VALUES(' . $data['ORDER_ID'] . ',
                                \'' . $data['TRANSACTIONID'] . '\',
                                \'' . $data['UNIQUEID'] . '\',
                                \'' . $data['PAYMENT_CODE'] . '\',
                                ' . (float)$data['AMOUNT'] . ',
                                \'' . $data['CURRENCY'] . '\',
                                \'' . $data['PROCESSING_CODE'] . '\',
                                ' . $data['CART_ID'] . ')');

	return Db::getInstance()->Insert_ID();
        }

    public function uninstall ()
	{
        Configuration::deleteByName('SKRILLPSP_TESTMODE');
        Configuration::deleteByName('SKRILLPSP_CHANNEL');
        Configuration::deleteByName('SKRILLPSP_SENDER');
        Configuration::deleteByName('SKRILLPSP_LOGIN');
        Configuration::deleteByName('SKRILLPSP_PASSWORD');
        Configuration::deleteByName('SKRILLPSP_TRANSACTION_MODE');
        foreach (self::$payments as $pmethod => $plabel)
            {
            Configuration::deleteByName('SKRILLPSP_PMETHOD_' . strtoupper($pmethod));
            Configuration::deleteByName('SKRILLPSP_PMETHOD_' . strtoupper($pmethod) . '_COUNTRIES');
            }

	parent::uninstall();
        }
        
    private function _prepareRequest ($config)
        {
        $cart = new Cart((int)$this->context->cart->id);
        $cart_details = $cart->getSummaryDetails(null, true);
        
        $billing_address = new Address($this->context->cart->id_address_invoice);
        $billing_address->country = new Country($billing_address->id_country);
        $billing_address->state	= new State($billing_address->id_state);
        $currency = $this->context->currency;
        $customer = $this->context->customer;
        $lang = $this->context->language;
        
        $requestdata = array('REQUEST.VERSION'      =>  '1.0',
                            'SECURITY.SENDER'       =>  $config['sender'],
                            'SECURITY.TOKEN'        =>  isset($config['token']) ? $config['token'] : '',
                            'USER.LOGIN'            =>  $config['login'],
                            'USER.PWD'              =>  $config['password'],
                            'TRANSACTION.CHANNEL'   =>  $config['channel'],
                            'TRANSACTION.RESPONSE'  =>  'ASYNC',
                            'TRANSACTION.MODE'      =>  $config['testmode'] ? 'CONNECTOR_TEST' : 'LIVE',
                            'IDENTIFICATION.TRANSACTIONID'
                                                    =>  (int)($cart->id) . '_' . date('YmdHis') . '_' . $cart->secure_key,
                            'PRESENTATION.USAGE'    =>  (int)($cart->id) . '_' . date('YmdHis'),
                            'PRESENTATION.AMOUNT'   =>  number_format($cart->getOrderTotal(), 2, '.', ''),
                            'PRESENTATION.CURRENCY' =>  $currency->iso_code,
                            'NAME.SALUTATION'       =>  null,
                            'NAME.TITLE'            =>  null,
                            'NAME.COMPANY'          =>  $customer->company,
                            'NAME.GIVEN'            =>  $billing_address->firstname,
                            'NAME.FAMILY'           =>  $billing_address->lastname,
                
                            'CONTACT.EMAIL'         =>  $customer->email,
                            'CONTACT.MOBILE'        =>  $billing_address->phone_mobile,
                            'CONTACT.PHONE'         =>  $billing_address->phone,
                            'CONTACT.IP'            =>  Tools::getRemoteAddr() ? Tools::getRemoteAddr() : '',
                            'ADDRESS.STREET'        =>  $billing_address->address1 . $billing_address->address2,
                            'ADDRESS.CITY'          =>  $billing_address->city,
                            'ADDRESS.STATE'         =>  $billing_address->state->name,
                            'ADDRESS.COUNTRY'       =>  $billing_address->country->iso_code,
                            'ADDRESS.ZIP'           =>  $billing_address->postcode,
                            'ACCOUNT.REGISTRATION'  =>  $this->context->cookie->SkrillPSPUniqueID ? $this->context->cookie->SkrillPSPUniqueID : '',
                            'ACCOUNT.ID'            =>  $customer->email,
            
                            'FRONTEND.ENABLED'      =>  'false',
                            'FRONTEND.POPUP'        =>  'false',
                            'FRONTEND.MODE'         =>  'DEFAULT',
                            'FRONTEND.STATUSBAR_VISIBLE'
                                                    =>  'false',
                            'FRONTEND.RETURN_ACCOUNT'
                                                    =>  'true',
                            'FRONTEND.REDIRECT_TIME'
                                                    =>  '0',
                            'FRONTEND.LANGUAGE'     =>  strtoupper($lang->iso_code),
                            'FRONTEND.RESPONSE_URL' =>  $this->getStatusUrl(),
                            'FRONTEND.SESSION_ID'   =>  Tools::getToken(),
                            'CRITERION.MONEYBOOKERS_hide_login'
                                                    =>   '1',
                            'FRONTEND.COLLECT_DATA' =>  'true',
                            'CRITERION.MONEYBOOKERS_recipient_description'
                                                    => Configuration::get('PS_SHOP_NAME'));
        
        return $requestdata;
        }

    private function _execPOSTRequest ($request, $secure_url)
        {
        try {
            if (!is_callable('curl_init'))
                return -1;
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $secure_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($request));
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
            $content = curl_exec($curl);
            $response = array();
            foreach (explode('&', $content) as $param_pair)
                {
                $param_pair = split("=", $param_pair);
                $response[$param_pair[0]] = urldecode($param_pair[1]);    
                }
            
            $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
            fprintf($logh, "%s\n\n", var_export($secure_url, true));
            fprintf($logh, "%s\n\n\n", var_export($request, true));
            fprintf($logh, "%s\n\n\n", var_export($response, true));
            fclose($logh);
            
            return $response;
            }
        catch (Exception $e)
            {
            
            }
        }
    
    private function _execXMLRequest ($xml, $secure_url)
        {
        try {
            if (!is_callable('curl_init'))
                return -1;
            
            $data = array('load' => $xml);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $secure_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
            $content = curl_exec($curl);
            
            $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
            fprintf($logh, "%s\n\n\n", var_export($xml, true));
            fprintf($logh, "%s\n\n\n", var_export($content, true));
            fclose($logh);
            
            return $content;
            }
        catch (Exception $e)
            {
            
            }
        }

    private function _parseXMLResponse ($xml)
        {
        $xml_parser = xml_parser_create();
        $nodes = $indexes = array();
        xml_parse_into_struct($xml_parser, $xml, $nodes, $indexes);

        $result = array();
        foreach ($indexes as $nodename => $nodepos)
            {
            if ($nodename == 'UNIQUEID' ||
                $nodename == 'TRANSACTIONID' ||
                $nodename == 'AMOUNT' ||
                $nodename == 'CURRENCY' ||
                $nodename == 'RESULT' ||
                $nodename == 'RETURN')
                {
                $result[preg_replace('/ /', '_', $nodename)] = $nodes[$nodepos[0]]['value'];
                }
            elseif ($nodename == 'PAYMENT' ||
                    $nodename == 'PROCESSING')
                {
                $result[$nodename . '_CODE'] = $nodes[$nodepos[0]]['attributes']['CODE'];
                }
            }
        xml_parser_free($xml_parser);

        return $result;
        }
    
     private function _process3DSResponse ($response)
        {
        $xml_parser = xml_parser_create();
        $nodes = $indexes = array();
        xml_parse_into_struct($xml_parser, $response, $nodes, $indexes);

        $result = array();
        foreach ($indexes as $nodename => $nodepos)
            {
            $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
            fprintf($logh, "%s %s\n\n\n", $nodename, var_export($nodepos, true));
            //fprintf($logh, "%s\n\n\n", var_export($nodes, true));
            fclose($logh);
            if ($nodename == 'UNIQUEID' ||
                $nodename == 'TRANSACTIONID' ||
                $nodename == 'AMOUNT' ||
                $nodename == 'CURRENCY' ||
                $nodename == 'RESULT' ||
                $nodename == 'RETURN')
                {
                $result[$nodename] = $nodes[$nodepos[0]]['value'];
                }
            elseif ($nodename == 'REDIRECT')
                {
                $result[$nodename] = $nodes[$nodepos[0]]['attributes']['URL'];
                }
            elseif ($nodename == 'PARAMETER')
                {
                $logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
                $result[$nodename] = array();
                $sz = count($nodepos);
                for ($i = 0; $i < $sz; $i ++)
                    {
                    fprintf($logh, "%s %s\n\n\n", $nodes[$nodepos[$i]]['attributes']['NAME'], $nodes[$nodepos[$i]]['value']);
                    $result[$nodename][$nodes[$nodepos[$i]]['attributes']['NAME']] = $nodes[$nodepos[$i]]['value'];
                    }
                fclose($logh);
                }
            elseif ($nodename == 'PAYMENT' ||
                    $nodename == 'PROCESSING')
                {
                $result[$nodename . '_CODE'] = $nodes[$nodepos[0]]['attributes']['CODE'];
                }
            }
        xml_parser_free($xml_parser);

        //$logh = fopen(_PS_MODULE_DIR_ . '/skrillpsp/datadump.log', "a+");
        //fprintf($logh, "%s\n\n\n", var_export($indexes, true));
        //fprintf($logh, "%s\n\n\n", var_export($nodes, true));
        //fclose($logh);
        
        return $result;
        }

    private function _saveConfiguration ()
	{
        $fieldslabels = array("channel" => "Channel",
                              "sender" => "Sender",
                              "login" => "Login",
                              "password" => "Password");
        
        if (!Tools::getValue('skrillbuttonconfirm'))
            return;
    
        $this->context->smarty->assign('isConfigFail', false);
        if (!$this->_validateConfiguration())
            {
            $this->context->smarty->assign(array('isConfigFail'     => true,
                                                 'errorMsgs'        => array('Unknown error')));
            if ($this->_configErrors['status'] == -1)
                {
                $errorMsgs = array();
                foreach ($this->_configErrors['mandatoryfields'] as $field)
                    array_push($errorMsgs, $this->l('The field <strong>' . $fieldslabels[$field] .
                            '</strong> is mandatory. Please fill in the required data.'));
                    
                $this->context->smarty->assign(array('errorMsgs'        => $errorMsgs,
                                                     'isConfigFail'     => true,
                                                     'errorfield'       => $this->_configErrors['mandatoryfields'])); 
                }
            }

        // Update Configuration
        Configuration::updateValue('SKRILLPSP_TESTMODE', !(int)Tools::getValue('testmode'));
        Configuration::updateValue('SKRILLPSP_CHANNEL', Tools::getValue('channel'));
        Configuration::updateValue('SKRILLPSP_SENDER', Tools::getValue('sender'));
        Configuration::updateValue('SKRILLPSP_LOGIN', Tools::getValue('login'));
        Configuration::updateValue('SKRILLPSP_PASSWORD', Tools::getValue('password'));
        Configuration::updateValue('SKRILLPSP_TRANSACTION_MODE', Tools::getValue('transactionmode'));
        
        if ($this->_configErrors['status'] == 1)
            {
            foreach (Currency::getCurrencies() as $currency)
                {
                $currency_iso_code = $currency['iso_code'];
            
                if ($this->_configErrors['currencies'][$currency_iso_code])
                    {
                    Configuration::updateValue('SKRILLPSP_TESTMODE_' . $currency_iso_code,
                                               (int)Tools::getValue('testmode_' . $currency_iso_code));
                    Configuration::updateValue('SKRILLPSP_CHANNEL_' . $currency_iso_code,
                                               Tools::getValue('channel_' . $currency_iso_code));
                    Configuration::updateValue('SKRILLPSP_SENDER_' . $currency_iso_code,
                                               Tools::getValue('sender_' . $currency_iso_code));
                    Configuration::updateValue('SKRILLPSP_LOGIN_' . $currency_iso_code,
                                               Tools::getValue('login_' . $currency_iso_code));
                    Configuration::updateValue('SKRILLPSP_PASSWORD_' . $currency_iso_code,
                                               Tools::getValue('password_' . $currency_iso_code));
                    }
                }
            }
        
        foreach (self::$payments as $pmethod => $plabel)
            {
            $pmethodname = 'SKRILLPSP_PMETHOD_' . strtoupper($pmethod);
            Configuration::updateValue($pmethodname, Tools::getValue(strtolower($pmethod) . '_enabled'));
            Configuration::updateValue($pmethodname . '_ORDER', Tools::getValue(strtolower($pmethod) . '_order'));
            Configuration::updateValue($pmethodname . '_LABEL', Tools::getValue(strtolower($pmethod) . '_label'));
            Configuration::updateValue($pmethodname . '_COUNTRIES', serialize(Tools::getValue(strtolower($pmethod) . '_countries')));
            }

        return;
	}
        
    private function _validateConfiguration ()
        {
        $fields = array("channel", "sender", "login", "password", "testmode");
        
        $this->_configErrors = array();
        $this->_configErrors['mandatoryfields'] = array();
        $this->_configErrors['status'] = 0;
        if (Tools::getValue('skrillbuttonconfirm'))
            {
            foreach ($fields as $iname)
                {
                if (!Tools::getvalue($iname) && $iname != "testmode")
                    {
                    $this->_configErrors['status'] = -1;
                    array_push($this->_configErrors['mandatoryfields'],
                               $iname);
                    }
                }
            
            if ($this->_configErrors['status']) // Mandatory field is empty !!!
                return false;
            
            $this->_configErrors['currencies'] = array();
            foreach (Currency::getCurrencies() as $currency)
                {
                $this->_configErrors['status'] = 1;
                
                $currency_iso_code = $currency['iso_code'];
            
                if (Tools::getvalue('channel_' . $currency_iso_code) ||
                    Tools::getvalue('sender_' . $currency_iso_code) ||
                    Tools::getvalue('login_' . $currency_iso_code) ||
                    Tools::getvalue('password_' . $currency_iso_code))
                    $this->_configErrors['currencies'][$currency_iso_code] = 1; 
                }
            }

        return true;
        }
        
    }

?>