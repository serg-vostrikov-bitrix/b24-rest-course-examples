<?php
/**
 * Payment system application
 * PHP version 8.0.24
 *
 * @category Application
 * @package  Package
 *
 * @author    Vadim Soluyanov <sallee@info-expert.ru>
 * @copyright 2022 Bitrix
 * @license   GNU AGPLv3 https://choosealicense.com/licenses/agpl-3.0/
 *
 * @link https://bitbucket.org/b24dev/exampleps.git
 */

require_once(__DIR__.'/cextrest.php');

/**
 * Payment system application class
 * 
 * @category Application
 * @package  Package
 *
 * @author    Vadim Soluyanov <sallee@info-expert.ru>
 * 
 * @link https://bitbucket.org/b24dev/exampleps.git
 */
class PaySys
{
    /**
     * Тип entity COMPANY на портале
     *
     * @var int
     */
    const COMPANY_TYPE_ID = 4;
    
    /**
    * Идентификатор портала
    * 
    * @access protected
    *
    * @var string
    */
    protected $memberId = '';
    
    /**
    * Домен портала
    * 
    * @access protected
    *
    * @var string
    */
    protected $domain = '';
    
    protected $handlerCodePrefix = 'my_ps_';
    
    protected $errors = [];
    
    /**
     * Конструктор
     *
     * @param string $memberId идентификатор портала
     * @param string $domain   домен портала
     *
     * @return void
     */
    public function __construct(string $memberId, string $domain='') {
        $this->memberId = $memberId;
        $this->domain = $domain;
        if (empty($domain)) {
            $settings = $this->getAppSettings();
            if (is_array($settings) && array_key_exists('DOMAIN', $settings)) {
                $this->domain = $settings['DOMAIN'];
            }
        }
        CRestExt::setCurrentBitrix24($memberId);
    }
    
    /**
     * Найти заказ по его идентификатору
     *
     * Используется на страницах оплаты
     *
     * @param string $hash ключ заказа в файле заказов портала
     *
     * @return array
     */
    public static function getOrderByHash($hash): array
    {
        $allOrders = static::getAllOrders();
        foreach ($allOrders as $memberId => $orders) {
            if (array_key_exists($hash, $orders)) {
                return $orders[$hash];
            }
        }
        
        return [];
    }
    
    /**
     * Получить все заказы приложения
     *
     * @return array
     */
    public static function getAllOrders(): array
    {
        $path = static::getOrdersDir(true);
        $orders = [];
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $file = $path.'/'.$entry;
                    $memberId = str_replace('.json','', $entry);
                    $orders[$memberId] = static::expandData(file_get_contents($file));
                }
            }
            closedir($handle);
        }
        
        return $orders;
    }
    
    /**
     * Получить порталы, имеющие заказы
     *
     * Используется на странице orders.php
     *
     * @return array
     */
    public static function getPortals(): array
    {
        $portals = [];
        $allOrders = static::getAllOrders();
        foreach ($allOrders as $memberId => $orders) {
            if (is_array($orders) && count($orders)) {
                $firstOrder = current($orders);
                $portals[$memberId] = $firstOrder['DOMAIN'];
            }
        }
        
        return $portals;
    }
    
    /**
     * Получить ошибки
     *
     * @return array
     */
    public function getErrors(): array {
        
        return $this->errors;
    }
    
    /**
     * Оплатить заказ
     *
     * Производит оплату заказа на портале
     *
     * @param string $hash      идентификатор записи из файла заказов портала
     * @param string $walletNum номер кошелька при оплате iframe
     *
     * @return bool
     */
    public function payOrderByHash($hash, $walletNum=''): bool
    {
        $this->errors = [];
        $order = static::getOrderByHash($hash);
        if (!is_array($order) || empty($order)) {
            $this->errors[] = 'No item found';
            
            return false;
        }
        if ($order['TYPE'] == 'ORDER') {
            $method = 'sale.paysystem.pay.payment';
            $params = [
                'PAYMENT_ID' => $order['PAYMENT_ID'],
                'PAY_SYSTEM_ID' => $order['PAYSYSTEM_ID'], 
            ];
        }
        else {
            $method = 'sale.paysystem.pay.invoice';
            $params = [
                'invoice_id' => $order['ID'],
                'bx_rest_handler' => $order['METHOD'],
            ];
        }
        $res = CRestExt::call($method, $params);
        if (!array($res)) {
            $this->errors[] = 'No portal responce';
        }
        elseif (array_key_exists('error', $res)) {
            $this->errors[] = 'Error ('.$res['error'].') '.$res['error_description'];
        }
        if (count($this->errors)) {
        
            return false;
        }
        // обновить данные о заказе
        $order['PAID'] = 1;
        if (!empty($walletNum)) {
            $order['WALLET'] = $walletNum;
        }
        
        $orders = [
            $hash => $order
        ];
        $this->setOrders($orders);
        
        return true;
    }
    
    /**
     * Отклонить оплату заказа
     *
     * Добавляет запись об отказе в таймлайн сущности на портале
     *
     * @param string $hash      идентификатор записи из файла заказов портала
     * @param string $walletNum номер кошелька при оплате iframe
     *
     * @return bool
     */
    public function rejectOrderByHash($hash, $walletNum=''): bool
    {
        $this->errors = [];
        $order = static::getOrderByHash($hash);
        if (!is_array($order) || empty($order)) {
            $this->errors[] = 'No item found';
            
            return false;
        }
        $method = 'crm.timeline.comment.add';
        if ($order['TYPE'] == 'ORDER') {
            $params = [
                'ENTITY_ID' => $order['ID'],
                'ENTITY_TYPE' => 'order', 
                'COMMENT' => 'Order payment rejected', 
            ];
        }
        else {
            $invoiceDetail = $this->getInvoiceDetail($order['ID']);
            CRestExt::setLog([
                    'invoice_detail' => $invoiceDetail
                ],
                'invoice_detail'
            );
            if (array_key_exists('PAYER_ID', $invoiceDetail) && !empty($invoiceDetail['PAYER_ID'])) {
                $type = ($invoiceDetail['PAYER_TYPE_ID'] == self::COMPANY_TYPE_ID) ? 'company' : 'contact';
                $params = [
                    'fields' => [
                        'ENTITY_ID' => $invoiceDetail['PAYER_ID'],
                        'ENTITY_TYPE' => $type, 
                        'COMMENT' => 'Invoice #'. $order['ID'].' payment rejected', 
                    ]
                ];
            }
        }
        if (empty($params)) {
            $this->errors[] = 'No payer found';
            
            return false;
        }
        $res = CRestExt::call($method, $params);
        if (!array($res)) {
            $this->errors[] = 'No portal responce';
        }
        elseif (array_key_exists('error', $res)) {
            $this->errors[] = 'Error ('.$res['error'].') '.$res['error_description'];
        }
        if (count($this->errors)) {
        
            return false;
        }
        // обновить данные о заказе
        $order['PAID'] = -1;
        if (!empty($walletNum)) {
            $order['WALLET'] = $walletNum;
        }
        $orders = [
            $hash => $order
        ];
        $this->setOrders($orders);
        
        return true;
    }
    
    /**
     * Получить текущие настройки авторизации
     *
     * Возвращает ключи авторизации в ПС.
     * Используется в index.php
     *
     * @return array
     */
    public function getSettings(): array
    {
        $settings = $this->getAppSettings();
        $apiKey = (array_key_exists('API_KEY', $settings)) ? $settings['API_KEY'] : '';
        $pass = (array_key_exists('PASS', $settings)) ? $settings['PASS'] : '';
        
        return ['API_KEY' => $apiKey, 'PASS' => $pass];
    }
    
    /**
     * Удалить авторизацию и ПС
     * 
     * Удаляет все ПС и обработчики,
     * после чего очищает значения авторизационных ключей
     * в настройках приложения.
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        $this->deleteOldPaysystems();
        $keys = ['API_KEY' => '', 'PASS' => ''];
        $this->setAppSettings($keys);
        
        return true;
    }
    
    /**
     * Сохранить ключи авторизации в ПС
     *
     * @return bool
     */
    public function saveSettings(): bool
    {
        $this->errors = [];
        $apiKey = (array_key_exists('API_KEY', $_POST)) ? trim($_POST['API_KEY']) : '';
        $pass = (array_key_exists('PASS', $_POST)) ? trim($_POST['PASS']) : '';
        if (empty($apiKey)) {
            $this->errors[] = 'Empty Api key';
        }
        if (empty($pass)) {
            $this->errors[] = 'Empty Password';
        }
        
        if (count($this->errors)) {
            
            return false;
        }
        
        $keys = ['API_KEY' => $apiKey, 'PASS' => $pass, 'DOMAIN' => $this->domain];
        if ($this->setAppSettings($keys)) {
        
            return $this->addPaySystems();
        }
        else {
            $this->errors[] = 'Save error';
        }
        
        return false;
    }
    
    /**
     * Получить путь к каталогу размещения заказов
     *
     * Каталог содержит файлы вида member_id.json
     * хранящие данные о заказах по порталам
     * 
     * @param bool $withoutSlash возвращать без конечного слэша
     *
     * @return string
     */
    protected static function getOrdersDir($withoutSlash=false): string
	{

		$dir = __DIR__ . '/orders';
		if (!$withoutSlash) {
            $dir .= '/';
		}

		if(!file_exists($dir))
		{
			mkdir($dir, 0775, true);
			@chmod($dir, 0775);
		}

		return $dir;
	}

	/**
     * Получить данные о заказах
     * 
     * Ключами массива является md5 от member_id, ENTITY_TYPE_ID, ORDER_ID
     *
     * @return array
     */
	public function getOrders(): array
	{
        $return = false;
		if ($this->memberId != '') {
            $path = static::getOrdersDir().$this->memberId.'.json';
            if (file_exists($path)) {
                $return = static::expandData(file_get_contents($path));
			}
		}
		if (!is_array($return)) {
            $return = [];
		}

		return $return;
	}
	
	/**
     * Сохранить данные о заказах
     *
     * @param array $orders параметры заказа
     *
     * @return bool
     */
	public function setOrders($orders): bool
	{
        $result = false;
		if ($this->memberId != '') {
            $oldData = $this->getOrders();
            $orders = array_merge($oldData, $orders);
			$result = (boolean)file_put_contents(static::getOrdersDir().$this->memberId.'.json', static::wrapData($orders));
			@chmod(static::getOrdersDir().$this->memberId.'.json', 0664);
        }
		
		return $result;
	}
    
    /**
     * Получить путь к каталогу размещения настроек
     *
     * Каталог содержит файлы вида member_id.json
     * хранящие настройки платежной системы по порталам
     *
     * @return string
     */
    protected static function getAppSettingsDir(): string
	{

		$dir = __DIR__ . '/paysys/';

		if(!file_exists($dir))
		{
			mkdir($dir, 0775, true);
			@chmod($dir, 0775);
		}

		return $dir;
	}

	/**
     * Получить настройки ПС
     *
     * @return array
     */
	public function getAppSettings(): array
	{
        $return = false;
		if ($this->memberId != '') {
            $path = static::getAppSettingsDir().$this->memberId.'.json';
            if (file_exists($path)) {
                $return = static::expandData(file_get_contents($path));
			}
		}
		if (!is_array($return)) {
            $return = [];
		}

		return $return;
	}
	
	/**
     * Сохранить настройки ПС
     *
     * @param array $arSettings массив настроек
     *
     * @return bool
     */
	public function setAppSettings($arSettings): bool
	{
        $result = false;
		if ($this->memberId != '') {
            $oldData = $this->getAppSettings();
            $arSettings = array_merge($oldData, $arSettings);
			$result = (boolean)file_put_contents(static::getAppSettingsDir().$this->memberId.'.json', static::wrapData($arSettings));
			@chmod(static::getAppSettingsDir().$this->memberId.'.json', 0664);
        }
		
		return $result;
	}
	
	/**
	 * @var $data mixed
	 * @var $encoding boolean true - encoding to utf8, false - decoding
	 *
	 * @return string json_encode with encoding
	 */
	protected static function changeEncoding($data, $encoding = true)
	{
		if(is_array($data))
		{
			$result = [];
			foreach ($data as $k => $item)
			{
				$k = static::changeEncoding($k, $encoding);
				$result[$k] = static::changeEncoding($item, $encoding);
			}
		}
		else
		{
			if($encoding)
			{
				$result = iconv(C_REST_CURRENT_ENCODING, "UTF-8//TRANSLIT", $data);
			}
			else
			{
				$result = iconv( "UTF-8",C_REST_CURRENT_ENCODING, $data);
			}
		}

		return $result;
	}
	
	/**
	 * @var $data mixed
	 * @var $debag boolean
	 *
	 * @return string json_encode with encoding
	 */
	protected static function wrapData($data, $debag = false)
	{
		if(defined('C_REST_CURRENT_ENCODING'))
		{
			$data = static::changeEncoding($data, true);
		}
		$return = json_encode($data, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);

		if($debag)
		{
			$e = json_last_error();
			if ($e != JSON_ERROR_NONE)
			{
				if ($e == JSON_ERROR_UTF8)
				{
					return 'Failed encoding! Recommended \'UTF - 8\' or set define C_REST_CURRENT_ENCODING = current site encoding for function iconv()';
				}
			}
		}

		return $return;
	}

	/**
	 * @var $data mixed
	 * @var $debag boolean
	 *
	 * @return string json_decode with encoding
	 */
	protected static function expandData($data)
	{
		$return = json_decode($data, true);
		if(defined('C_REST_CURRENT_ENCODING'))
		{
			$return = static::changeEncoding($return, false);
		}
		return $return;
	}
    
    /**
     * Получить зарегистрированные ПС
     *
     * Возвращает сведения о ПС, зарегистрированных
     * данным приложением на портале.
     * Используется в index.php
     *
     * @return array
     */
    public function getPaySystems(): array
    {
        $crmPsUrlTemplate = 'https://'.$this->domain.'/crm/configs/ps/edit/#ID#/';
        $salePsUrlTemplate = 'https://'.$this->domain.'/shop/settings/sale_pay_system_edit/?ID=#ID#';
        
        // test
        $test = CRestExt::call('sale.paysystem.handler.list');
        CRestExt::setLog(['PS_handler_list' => $test], 'PS_handler_list');
        
        $paySystems = [];
        $res = CRestExt::call('sale.paysystem.list');
        if (is_array($res) && is_array($res['result'])) {
            foreach($res['result'] as $ps) {
                if (strpos($ps['ACTION_FILE'], $this->handlerCodePrefix) === 0) {
                    $tpl = ($ps['ENTITY_REGISTRY_TYPE'] == 'CRM_INVOICE') ? $crmPsUrlTemplate : $salePsUrlTemplate;
                    $ps['EDIT_URL'] = str_replace('#ID#', $ps['ID'], $tpl);
                    $paySystems[] = $ps;
                }
            }
        }
        
        CRestExt::setLog(['PS_list' => $paySystems], 'ps_list');
        
        return $paySystems;
    }
    
    /**
     * Оплата по методу Checkout
     *
     * Регистрирует заказ в сторонней ПС,
     * получая в ответ УРЛ для оплаты и ID заказа,
     * которые возвращает в checkout.php, где
     * данные параметры возвращаются на портал в виде json
     *
     * @return array
     */
    public function checkout(): array {
        $result = [];
/*
array (
    'PAYMENT_ID' => '247',
    'ORDER_ID' => '253',
    'PAYMENT_SHOULD_PAY' => '3000',
    'PAYMENT_CURRENCY' => 'ANG',
    'MEMBER_ID' => '610f9beb9476d47e81d22cdbaa028768',
    'SERVICE_ID' => 'f28f5d56e3ed3f8a66eadef0e03bd484',
    'BX_SYSTEM_PARAMS' => 
    array (
        'RETURN_URL' => 'https://b24-qfpnr9.bitrix24.shop/checkout/?orderId=253&access=cc360d69a6f8d2222144580975c783f0&paymentId=247&user_lang=en',
        'PAYSYSTEM_ID' => '349',
        'PAYMENT_ID' => '247',
        'SUM' => '3000',
        'CURRENCY' => 'ANG',
    ),
)
*/
        list($type, $settings) = $this->searchPaymentSettings($_REQUEST);
        if (empty($settings)) {
            
            return $result;
        }
        $serviceId = $this->getServiceId();
        if ($_REQUEST['SERVICE_ID'] != $serviceId) {
            
            return $result;
        }
        
        if ($type == 'ORDER') {
            $detail = $this->getOrderDetail($_REQUEST['ORDER_ID']);
        }
        else {
            $detail = $this->getInvoiceDetail($_REQUEST['ORDER_ID']);
        }
        if ($detail['PAID'] == 'Y') {
            
            return $result;
        }
        
        if (!empty($detail)) {
            // здесь делаем регистрацию оплаты в сторонней ПС
            // регистрируем заказ в приложении
            $key = md5($this->memberId.$type.$detail['ORDER_ID']);
            $orders = [
                $key => [
                    'ID' => $detail['ORDER_ID'],
                    'TYPE' => $type,
                    'METHOD' => $settings['ACTION_FILE'],
                    'MEMBER_ID' => $this->memberId,
                    'DOMAIN' => $this->domain,
                    'DOCUMENT_NUMBER' => $detail['DOCUMENT_NUMBER'],
                    'PAID' => 0,
                    'SUM' => $settings['PAYMENT_SHOULD_PAY'],
                    'CURRENCY' => $settings['PAYMENT_CURRENCY'],
                    'DATE' => $detail['DATE_BILL'],
                    'PAYMENT_ID'    => $settings['PAYMENT_ID'],
                    'PAYSYSTEM_ID' => $_REQUEST['BX_SYSTEM_PARAMS']['PAYSYSTEM_ID'],
                    'RETURN_URL'    => $_REQUEST['BX_SYSTEM_PARAMS']['RETURN_URL'],
                ],
            ];
            $this->setOrders($orders);
        }
        $page = ($settings['ACTION_FILE'] == 'my_ps_card') ? 'card_checkout.php' : 'bank_checkout.php';
        
        $result = [
            'PAYMENT_URL' => $this->getBaseUrl().$page.'?id='.$key,
            'PAYMENT_ID' => $key,
        ];
        
        return $result;
    }
    
    /**
     * Получение и регистрация заказа по методу IFRAME
     *
     * Получает данные о заказе с портала,
     * регистрирует заказ в файле,
     * возвращает первичный ключ записи для дальнейшей
     * процедуры оплаты
     *
     * @return string
     */
    public function makeWalletOrder(): string
    {
/*
array (
    'PAYMENT_ID' => '247',
    'ORDER_ID' => '253',
    'MEMBER_ID' => '610f9beb9476d47e81d22cdbaa028768',
    'SERVICE_ID' => 'f28f5d56e3ed3f8a66eadef0e03bd484',
    'BX_SYSTEM_PARAMS' => 
    array (
        'RETURN_URL' => 'https://b24-qfpnr9.bitrix24.shop/checkout/?orderId=253&access=cc360d69a6f8d2222144580975c783f0&paymentId=247&user_lang=en',
        'PAYSYSTEM_ID' => '349',
        'PAYMENT_ID' => '247',
        'SUM' => '3000',
        'CURRENCY' => 'ANG',
    ),
)
*/
        $request = $_REQUEST;
        $request['BX_SYSTEM_PARAMS'] = json_decode($request['BX_SYSTEM_PARAMS'], true);
        CRestExt::setLog(
            [
                'payment_params' => $request
            ],
            'payment_params'
        );
        list($type, $settings) = $this->searchPaymentSettings($request);
        if (empty($settings)) {
            
            return $result;
        }
        $serviceId = $this->getServiceId();
        if ($request['SERVICE_ID'] != $serviceId) {
            
            return $result;
        }
        
        if ($type == 'ORDER') {
            $detail = $this->getOrderDetail($request['ORDER_ID']);
        }
        else {
            $detail = $this->getInvoiceDetail($request['ORDER_ID']);
        }
        if ($detail['PAID'] == 'Y') {
            $entity = ($type == 'ORDER') ? 'Order': 'Invoice';
            $this->errors[] = $entity.' is already paid';
            
            return '';
        }
        if (!empty($detail)) {
            // здесь делаем регистрацию оплаты в сторонней ПС
            // регистрируем заказ в приложении
            $key = md5($this->memberId.$type.$detail['ORDER_ID']);
            $orders = [
                $key => [
                    'ID' => $detail['ORDER_ID'],
                    'TYPE' => $type,
                    'METHOD' => $settings['ACTION_FILE'],
                    'MEMBER_ID' => $this->memberId,
                    'DOMAIN' => $this->domain,
                    'DOCUMENT_NUMBER' => $detail['DOCUMENT_NUMBER'],
                    'PAID' => 0,
                    'SUM' => $settings['PAYMENT_SHOULD_PAY'],
                    'CURRENCY' => $settings['PAYMENT_CURRENCY'],
                    'DATE' => $detail['DATE_BILL'],
                    'PAYMENT_ID'    => $settings['PAYMENT_ID'],
                    'PAYSYSTEM_ID' => $request['BX_SYSTEM_PARAMS']['PAYSYSTEM_ID'],
                    'RETURN_URL'    => $request['BX_SYSTEM_PARAMS']['RETURN_URL'],
                ],
            ];
            $this->setOrders($orders);
            
            return $key;
        }
        
        return '';
    }
    
    protected function getBaseUrl(): string
    {
        return ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
        . $_SERVER['SERVER_NAME']
        . (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
        . str_replace($_SERVER['DOCUMENT_ROOT'], '',__DIR__)
        . '/';
    }
    
    /**
     * Ищет настройки конкретной платёжной системы.
     *
     * @param array  $paymentParams - параметры платежа
     *
     * @return array
     */
    protected function searchPaymentSettings(array $paymentParams): array
    {
        $result = [];
        
        if (!is_array($paymentParams) || !array_key_exists('BX_SYSTEM_PARAMS', $paymentParams)) {
            return $result;
        }
        $psId = intval($paymentParams['BX_SYSTEM_PARAMS']['PAYSYSTEM_ID']);
        $paySystemList = CRestExt::call(
            'sale.paysystem.list',
            ['filter' => ['ID' => $psId]]
        );
        CRestExt::setLog(['Search ps res' => $paySystemList], 'search_ps_res');
        if (array_key_exists('error', $paySystemList)) {
            
            return $result;
        }
        $ps = current($paySystemList['result']);
        if ($ps['ENTITY_REGISTRY_TYPE'] == 'ORDER') {
            $method = 'sale.paysystem.settings.payment.get';
            $params = [
                'payment_id' => $paymentParams['PAYMENT_ID'],
                'pay_system_id' => $ps['ID']
            ];
        } else {
            $method = 'sale.paysystem.settings.invoice.get';
            $params = [
                'invoice_id' => $paymentParams['ORDER_ID'],
                'pay_system_id' => $paymentParams['BX_SYSTEM_PARAMS']['PAYSYSTEM_ID']
            ];
        }
        $res = CRestExt::call($method, $params);
        CRestExt::setLog(['PS settings res' => $res], 'ps_settings_res');
        if (array_key_exists('error', $res)) {
            
            return $result;
        }
        // добавим код обработчика, для определения конечной страницы оплаты
        $res['result']['ACTION_FILE'] = $ps['ACTION_FILE'];

        $result[] = $ps['ENTITY_REGISTRY_TYPE'];
        $result[] = $res['result'];

        return $result;
    }
    
    /**
     * Получить детализированную информацию о заказе.
     *
     * @param int $orderId идентификатор заказа
     *
     * @return array
     */
    protected function getOrderDetail(int $orderId): array
    {
        $detail = [];
        $orderItem = CRestExt::call('sale.order.get', ['id' => $orderId]);
        CRestExt::setLog([
                'order_detail_res' => $orderItem
            ],
            'order_detail_res'
        );
        if (array_key_exists('error', $orderItem)) {
            
            return $detail;
        }

        $orderTopic = $orderItem['result']['order']['orderTopic'];
        if (empty($orderTopic) && count($orderItem['result']['order']['basketItems'])) {
            $orderTopic = $orderItem['result']['order']['basketItems'][0]['name'];
            if (count($orderItem['result']['order']['basketItems']) > 1) {
                $orderTopic .= ',...';
            }
        }
        if (empty($orderTopic)) {
            $orderTopic = 'Payment for_order '.$orderItem['result']['order']['accountNumber'];
        }
        $basket = [];
        if (is_array($orderItem['result']['order']['basketItems'])) {
            foreach ($orderItem['result']['order']['basketItems'] as $bItem) {
                $basket[] = [
                    'ID' => $bItem['productId'],
                    'TITLE' => $bItem['name'],
                    'QUANTITY' => $bItem['quantity'],
                    'PRICE' => $bItem['price'],
                    'CURRENCY' => $bItem['currency'],
                ];
            }
        }
        $arPayer = [];
        foreach ($orderItem['result']['order']['propertyValues'] as $value) {
            switch ($value['code']) {
                case 'COMPANY':
                case 'FIO':
                    $arPayer['CONTACT_PERSON'] = $value['value'];

                    break;
                case 'EMAIL':
                    $arPayer['EMAIL'] = $value['value'];

                    break;
                case 'PHONE':
                    $arPayer['PHONE'] = $value['value'];

                    break;
                default:
                    break;
            }
        }
        $detail['SHIPMENT'] = [];
        if (isset($orderItem['result']['order']['shipments'][0]['priceDelivery'])
            && $orderItem['result']['order']['shipments'][0]['priceDelivery'] > 0
        ) {
            $detail['SHIPMENT'] = [
                'CURRENCY' => strval($orderItem['result']['order']['shipments'][0]['currency']),
                'PRICE' => doubleval($orderItem['result']['order']['shipments'][0]['priceDelivery']),
            ];
        }

        $detail['ORDER_TOPIC'] = $orderTopic;
        $detail['DOCUMENT_NUMBER'] = $orderItem['result']['order']['accountnumber'];
        $detail['PAID'] = $orderItem['result']['order']['payed'];
        $detail['NAME'] = $arPayer['CONTACT_PERSON'];
        $detail['PHONE'] = $arPayer['PHONE'];
        $detail['MAIL'] = $arPayer['EMAIL'];
        $detail['ORDER_ID'] = $orderId;
        $detail['DATE_BILL'] = $orderItem['result']['order']['dateInsert'];
        $detail['STATUS_ID'] = $orderItem['result']['order']['statusId'];
        $detail['BASKET'] = $basket;

        return $detail;
    }

    /**
     * Получить детализированную информацию об инвойсе.
     *
     * @param int $invoiceId идентификатор счета
     *
     * @return array
     */
    protected function getInvoiceDetail(int $invoiceId): array
    {
        $detail = [];

        $batch = [
            'invoice' => [
                'method' => 'crm.invoice.get',
                'params' => [
                    'id' => $invoiceId
                ],
            ],
            'company' => [
                'method' => 'crm.company.get',
                'params' => [
                    'id' => '$result[invoice][UF_COMPANY_ID]',
                ]
            ],
            'contact' => [
                'method' => 'crm.contact.get',
                'params' => [
                    'id' => '$result[req_link][UF_CONTACT_ID]',
                ],
            ],
        ];
        $res = CRestExt::callBatch($batch);
        CRestExt::setLog([
                'invoice_detail_res' => $res
            ],
            'invoice_detail_res'
        );
        if (!is_array($res) || empty($res) || !empty($res['result_error'])) {
            
            return $detail;
        }

        $res['result'] = $res['result']['result'];
        $detail['NAME'] = '';
        $entityId = '';
        $entityTypeId = '';
        if (array_key_exists('company', $res['result'])) {
            $entityId = $res['result']['invoice']['UF_COMPANY_ID'];
            $entityTypeId = 4;
            if (!empty($res['result']['invoice']['INVOICE_PROPERTIES']['COMPANY'])) {
                $detail['NAME'] = $res['result']['invoice']['INVOICE_PROPERTIES']['COMPANY'];
            }
            elseif(array_key_exists('company', $res['result'])) {
                $detail['NAME'] = $res['result']['company']['TITLE'];
            }
        } else {
            $entityId = $res['result']['invoice']['UF_CONTACT_ID'];
            $entityTypeId = 3;
            if (!empty($res['result']['invoice']['INVOICE_PROPERTIES']['FIO'])) {
                $detail['NAME'] = $res['result']['invoice']['INVOICE_PROPERTIES']['FIO'];
            }
            elseif(array_key_exists('contact', $res['result'])) {
                $detail['NAME'] = $res['result']['contact']['NAME'] . ' ' . $res['result']['contact']['LAST_NAME'];
            }
        }

        $crmItem = $res['result']['invoice'];
        $basket = [];
        if (is_array($crmItem['PRODUCT_ROWS'])) {
            foreach ($crmItem['PRODUCT_ROWS'] as $product) {
                $basket[] = [
                    'ID' => $product['PRODUCT_ID'],
                    'TITLE' => $product['PRODUCT_NAME'],
                    'QUANTITY' => $product['QUANTITY'],
                    'PRICE' => $product['PRICE'],
                    'CURRENCY' => $crmItem['CURRENCY'],
                ];
            }
        }
        if (empty($crmItem['ORDER_TOPIC'])) {
            $crmItem['ORDER_TOPIC'] = 'Payment for invoice '.$crmItem['ACCOUNT_NUMBER'];
        }

        $arPayer = $crmItem['INVOICE_PROPERTIES'];
        $detail['ORDER_TOPIC'] = $crmItem['ORDER_TOPIC'];
        $detail['PAID'] = $crmItem['PAYED'];
        $detail['PAYER_TYPE_ID'] = $entityTypeId;
        $detail['PAYER_ID'] = $entityId;
        $detail['PHONE'] = $arPayer['PHONE'];
        $detail['MAIL'] = $arPayer['EMAIL'];
        $detail['DOCUMENT_NUMBER'] = $crmItem['ACCOUNT_NUMBER'];
        $detail['ORDER_ID'] = $invoiceId;
        $detail['DATE_BILL'] = $crmItem['DATE_BILL'];
        $detail['STATUS_ID'] = $crmItem['STATUS_ID'];
        $detail['BASKET'] = $basket;
        $detail['SHIPMENT'] = [];

        return $detail;
    }
    
    /**
     * Добавить ПС
     *
     * Добавляет также хендлеры, если нет
     * Добавляет ошибки в $this->errors, если были
     *
     * @return bool
     */
    protected function addPaySystems(): bool
    {
        $psInfo = $this->getPaySystemsInfo();
        CRestExt::setLog(['PS_info' => $psInfo], 'ps_info');
        
        $handlersExist = true;
        if (empty($psInfo['HANDLERS'])) {
            $handlersExist = $this->addPayHandlers();
            $psInfo = $this->getPaySystemsInfo();
        }
        
        if ($handlersExist) {
            $batch = [];
            foreach ($psInfo['HANDLERS'] as $h) {
                foreach ($psInfo['PT'] as $type => $ids) {
                    foreach ($ids as $id) {
                        $entityName = (strpos($type, 'CONTACT') !== false) ? 'Contact' : 'Company';
                        if (strpos($type, 'SALE') !== false) {
                            $entityType =  'Order';
                            $type = 'ORDER';
                        }
                        else {
                            $entityType = 'Invoice';
                            $type = 'CRM_INVOICE';
                        }
                        $name = $h['NAME'].' ('.$entityType.'.'.$entityName.')';
                        $handlerCode = $h['CODE'];
                        $serviceId = $this->getServiceId();
                        $psFields = [
                            'NAME' => $name,
                            'ACTIVE' => 'Y',
                            'PERSON_TYPE_ID' => $id, 
                            'ENTITY_REGISTRY_TYPE' => $type,
                            'BX_REST_HANDLER' => $handlerCode,
                            'SETTINGS' => [ // Настройки обработчика для данной платежной системы
                                'PAYMENT_ID' => [
                                    'TYPE' => 'PAYMENT',
                                    'VALUE' => 'ID'
                                ],
                                'ORDER_ID' => [
                                    'TYPE' => 'ORDER',
                                    'VALUE' => 'ID'
                                ],
                                'PAYMENT_SHOULD_PAY' => [
                                    'TYPE' => 'PAYMENT',
                                    'VALUE' => 'SUM'
                                ],
                                'PAYMENT_CURRENCY' => [
                                    'TYPE' => 'PAYMENT',
                                    'VALUE' => 'CURRENCY'
                                ],
                                'REST_MEMBER_ID' => [
                                    'TYPE' => 'VALUE',
                                    'VALUE' => $this->memberId
                                ],
                                'REST_SERVICE_ID' => [
                                    'TYPE' => 'VALUE',
                                    'VALUE' => $serviceId
                                ],
                            ]
                        ];
                        
                        $batch[] = ['method'=> 'sale.paysystem.add', 'params'=> $psFields];
                    }
                }
            }
            
            CRestExt::setLog(['PS_batch' => $batch], 'ps_batch');
            
            if (count($batch)) {
                $addRes = CRestExt::callBatch($batch);
                
                return is_array($addRes) && empty($addRes['result_error']);
            }
        }
    
        return false;
    }
    
    /**
     * Получить параметр ПС SERVICE_ID
     *
     * Параметр используется для проверки корректности запроса на оплату
     *
     * @return string
     */
    protected function getServiceId(): string
    {
        $settings = $this->getAppSettings();
        
        return md5($settings['API_KEY'].$settings['PASS']);
    }
    
    
    protected function addPayHandlers(): bool
    {
        $handlersConf = [
            $this->handlerCodePrefix.'card' => [
                'NAME'    => 'Credit card',
                'TYPE'    => 'CHECKOUT_DATA',
                'HANDLER' => 'checkout.php',
            ],
            $this->handlerCodePrefix.'bank' => [
                'NAME'    => 'Bank transfer',
                'TYPE'    => 'CHECKOUT_DATA',
                'HANDLER' => 'checkout.php',
            ],
            $this->handlerCodePrefix.'wallet' => [
                'NAME'    => 'E-wallet',
                'TYPE'    => 'IFRAME_DATA',
                'HANDLER' => 'iframe.php',
            ],
        ];
        
        $serviceId = $this->getServiceId();
        
        $batch = [];
        
        $baseHandlerURL = ($_SERVER['HTTPS'] === 'on' || $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http') . '://'
			. $_SERVER['SERVER_NAME']
			. (in_array($_SERVER['SERVER_PORT'],	['80', '443'], true) ? '' : ':' . $_SERVER['SERVER_PORT'])
			. str_replace($_SERVER['DOCUMENT_ROOT'], '',__DIR__)
			. '/';
        
        foreach($handlersConf as $code => $cfg) {
            $fields = [
                'NAME' => $cfg['NAME'],
                'CODE' => $code,
                'SORT' => 100,
                'SETTINGS' => [
                    $cfg['TYPE'] => [
                        'ACTION_URI' => $baseHandlerURL.$cfg['HANDLER'],
                        'METHOD' => 'POST',
                        'FIELDS' => [
                            'PAYMENT_ID' => [
                                'CODE' => 'PAYMENT_ID'
                            ],
                            'ORDER_ID' => [
                                'CODE' => 'ORDER_ID'
                            ],
                            'PAYMENT_SHOULD_PAY' => [
                                'CODE' => 'PAYMENT_SHOULD_PAY'
                            ],
                            'PAYMENT_CURRENCY' => [
                                'CODE' => 'PAYMENT_CURRENCY'
                            ],
                            'MEMBER_ID' => [
                                'CODE' => 'REST_MEMBER_ID'
                            ],
                            'SERVICE_ID' => [
                                'CODE' => 'REST_SERVICE_ID',
                            ],
                        ],
                    ],
                    'CODES' => [
                        'PAYMENT_ID' => [
                            'NAME' => 'Payment ID',
                            'GROUP' => 'PAYMENT',
                            'SORT' => 200,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'PAYMENT',
                                'PROVIDER_VALUE' => 'ID',
                            ],
                        ],
                        'ORDER_ID' => [
                            'NAME' => 'Order ID',
                            'GROUP' => 'ORDER',
                            'SORT' => 300,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'ORDER',
                                'PROVIDER_VALUE' => 'ID',
                            ],
                        ],
                        'PAYMENT_SHOULD_PAY' => [
                            'NAME' => 'Payment Should Pay',
                            'GROUP' => 'PAYMENT',
                            'SORT' => 400,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'PAYMENT',
                                'PROVIDER_VALUE' => 'SUM',
                            ],
                        ],
                        'PAYMENT_CURRENCY' => [
                            'NAME' => 'Payment Currency',
                            'GROUP' => 'PAYMENT',
                            'SORT' => 500,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'PAYMENT',
                                'PROVIDER_VALUE' => 'CURRENCY',
                            ],
                        ],
                        'REST_MEMBER_ID' => [
                            'NAME' => 'Member Id',
                            'SORT' => 600,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'VALUE',
                                'PROVIDER_VALUE' => $this->memberId,
                            ],
                        ],
                        'REST_SERVICE_ID' => [
                            'NAME' => 'Service ID',
                            'SORT' => 700,
                            'DEFAULT' => [
                                'PROVIDER_KEY' => 'VALUE',
                                'PROVIDER_VALUE' => $serviceId
                            ],
                        ],
                    ],
                ],
            ];
            $batch[$code] = [
                'method' => 'sale.paysystem.handler.add',
                'params' => $fields,
            ];
        }
        
        if (count($batch)) {
            $res = CRestExt::callBatch($batch);
            
            return is_array($res) && empty($res['result_error']);
        }
        
        return false;
    }
    
    protected function getPaySystemsInfo() {
        $result = [
            'HANDLERS' => [],
            'PS' => [],
            'PT' => [
                'CRM_COMPANY'  => [],
                'CRM_CONTACT'  => [],
                'SALE_COMPANY' => [],
                'SALE_CONTACT' => [],
            ],
        ];
        
        $psRes = CRestExt::callBatch([
			'paysystem_handler_list' => [
				'method' => 'sale.paysystem.handler.list',
				'params' => [],
			],
			'paysystem_list' => [
				'method' => 'sale.paysystem.list',
				'params' => [],
			],
			'crm_persontype_list' => [
				'method' => 'crm.persontype.list',
				'params' => [],
			],
			'sale_persontype_list' => [
				'method' => 'sale.persontype.list',
				'params' => [],
			],
		]);
		
		//CRestExt::setLog(['PS_info' => $psRes], 'ps_info');
		
		if (is_array($psRes) && array_key_exists('result', $psRes)) {
            $psRes['result'] = $psRes['result']['result'];
            if (is_array($psRes['result']['paysystem_handler_list'])) {
                foreach ($psRes['result']['paysystem_handler_list'] as $handler) {
                    if (strpos($handler['CODE'], $this->handlerCodePrefix) === 0) {
                        $result['HANDLERS'][$handler['CODE']] = $handler;
                    }
                }
            }
            
            if (is_array($psRes['result']['paysystem_list'])) {
                foreach($psRes['result']['paysystem_list'] as $ps) {
                    if (strpos($ps['ACTION_FILE'], $this->handlerCodePrefix) === 0) {
                        $result['PS'][] = $ps;
                    }
                }
            }
            
            if (is_array($psRes['result']['crm_persontype_list'])) {
                foreach ($psRes['result']['crm_persontype_list'] as $pt) {
                    if ($pt['NAME'] == 'CRM_COMPANY') {
                        $result['PT']['CRM_COMPANY'][] = $pt['ID'];
                    }
                    if ($pt['NAME'] == 'CRM_CONTACT') {
                        $result['PT']['CRM_CONTACT'][] = $pt['ID'];
                    }
                }
            }
            if (is_array($psRes['result']['sale_persontype_list']['personTypes'])) {
                foreach ($psRes['result']['sale_persontype_list']['personTypes'] as $pt) {
                    if ($pt['code'] == 'CRM_COMPANY') {
                        $result['PT']['SALE_COMPANY'][] = $pt['id'];
                    }
                    if ($pt['code'] == 'CRM_CONTACT') {
                        $result['PT']['SALE_CONTACT'][] = $pt['id'];
                    }
                }
            }
		}
		
		return $result;
    }
    
    protected function deleteOldPaysystems() {
        $psInfo = $this->getPaySystemsInfo();
        

        if (is_array($psInfo['PS'])) {
            $batch = [];
            foreach ($psInfo['PS'] as $paysystemItem) {
                $batch[] = [
                    'method' => 'sale.paysystem.delete',
                    'params' => ['id' => $paysystemItem['ID']],
                ];
            }
            if (count($batch)) {
                CRestExt::callBatch($batch);
            }
        }
        if (is_array($psInfo['HANDLERS'])) {
            $batch = [];
            foreach ($psInfo['HANDLERS'] as $paysystemHandlerItem) {
                $batch[] = [
                    'method' => 'sale.paysystem.handler.delete',
                    'params' => ['id' => $paysystemHandlerItem['ID']],
                ];
            }
            if (count($batch)) {
                CRestExt::callBatch($batch);
            }
        }
		
		return true;
	}
}
