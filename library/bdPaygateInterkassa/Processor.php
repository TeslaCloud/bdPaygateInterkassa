<?php

class bdPaygateInterkassa_Processor extends bdPaygate_Processor_Abstract
{
    const CURRENCY_RUB = 'rub';
    const CURRENCY_UAH = 'uah';

	public function getSupportedCurrencies()
	{
        $currencies = array();
        $currencies[] = self::CURRENCY_RUB;
        $currencies[] = self::CURRENCY_UAH;

        return $currencies;
	}

    public function isAvailable()
    {
        $options = XenForo_Application::getOptions();
        if(empty($options->bdPaygateInterkassa_ID) || empty($options->bdPaygateInterkassa_SecretKey) || $this->_sandboxMode()){
            return false;
        }

        return true;
    }

	public function isRecurringSupported()
	{
		return false;
	}
	
	public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
	{
	    // TODO: Пофиксить алгоритм
		$input = new XenForo_Input($request);
        $transactionDetails = $input->getInput();

        $signature = $transactionDetails['ik_sign'];

		$transactionId = (!empty($transactionDetails['ik_inv_id']) ? ('interkassa_' . $transactionDetails['ik_inv_id']) : '');
		$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;

		$processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');
		$options = XenForo_Application::get('options');
        //$interkassa_key = $options->bdPaygateInterkassa_SecretKey;
        $interkassa_key = $this->_sandboxMode() ? $options->bdPaygateInterkassa_SecretKey_Test : $options->bdPaygateInterkassa_SecretKey;

        // Проверяем, не была ли уже проведена такая операция
		$log = $processorModel->getLogByTransactionId($transactionId);
		if (!empty($log))
		{
			$this->_setError("Transaction {$transactionId} has already been processed");
			return false;
		}

        // Генерация MD5 подписи
        unset($transactionDetails['ik_sign'], $transactionDetails['p'], $transactionDetails['0'], $transactionDetails['_callbackIp']);
        // Сортировка эл-тов массива в алфавитном порядке
        ksort($transactionDetails, SORT_STRING);
        // Добавление секретного ключа в конец массива
        array_push($transactionDetails, $interkassa_key);
        // Конкатенация значений через символ ":"
        $crc = implode(':', $transactionDetails);
        // Кодирование MD5 хэша в BASE64
        $crc = base64_encode(md5($crc, true));

        // Сверяем нашу подпись с той, которую мы получили
		if ($crc != $signature)
		{
			$this->_setError('Request not validated + '.$crc.' + '.$signature);
			return false;
		}

		// https://www.interkassa.com/documentation-sci/
		switch ($transactionDetails['ik_inv_st']) {
		case "success":
			// Платеж успешно проведен
            $itemId = $transactionDetails['ik_x_item'];
			$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
            echo "OK";
			break;
		default:
			$paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
		}
		
		return true;
	}
	
	public function generateFormData($amount, $currency, $itemName, $itemId, $recurringInterval = false, $recurringUnit = false, array $extraData = array())
	{
		$this->_assertAmount($amount);
		$this->_assertCurrency($currency);
		$this->_assertItem($itemName, $itemId);
		$this->_assertRecurring($recurringInterval, $recurringUnit);

		$formAction = 'https://sci.interkassa.com/';
		$callToAction = new XenForo_Phrase('bdpaygate_interkassa_call_to_action');

        $options = XenForo_Application::get('options');
        $interkassa_key = $options->bdPaygateInterkassa_SecretKey;

        $payment = array(
            'ik_x_item' => $itemId,
            'ik_desc' => $itemName,
            'ik_am' => $amount,
            'ik_cur' => utf8_strtoupper($currency),
            'ik_pm_no' => substr(md5(time()), 0, 6),
            'ik_co_id' => $options->bdPaygateInterkassa_ID,
            'ik_suc_u' => $options->bdPaygateInterkassa_SuccessUrl,
            'ik_suc_m' => 'GET',
            'ik_fal_u' => $options->bdPaygateInterkassa_FailUrl,
            'ik_fal_m' => 'GET'
        );

        // Генерация MD5 подписи для формы
        // Сортировка эл-тов массива в алфавитном порядке
        ksort($payment, SORT_STRING);
        // Добавление секретного ключа в конец массива
        array_push($payment, $interkassa_key);
        // Конкатенация значений через символ ":"
        $crc = implode(':', $payment);
        // Кодирование MD5 хэша в BASE64
        $crc = base64_encode(md5($crc, true));

		
		$form = <<<EOF
            
            <form action="{$formAction}" method="POST">
                <input type="hidden" name="ik_x_item" value="{$payment['ik_x_item']}" />
                <input type="hidden" name="ik_co_id" value="{$payment['ik_co_id']}" />
                <input type="hidden" name="ik_pm_no" value="{$payment['ik_pm_no']}" />
                <input type="hidden" name="ik_cur" value="{$payment['ik_cur']}" />
                <input type="hidden" name="ik_am" value="{$payment['ik_am']}" />
                <input type="hidden" name="ik_desc" value="{$payment['ik_desc']}" />
                <input type="hidden" name="ik_suc_u" value="{$payment['ik_suc_u']}" />
                <input type="hidden" name="ik_suc_m" value="{$payment['ik_suc_m']}" />
                <input type="hidden" name="ik_fal_u" value="{$payment['ik_fal_u']}" />
                <input type="hidden" name="ik_fal_m" value="{$payment['ik_fal_m']}" />
                <input type="hidden" name="ik_sign" value="{$crc}" />
                
                <input type="submit" value="{$callToAction}" class="button" />
            </form>
EOF;

		return $form;
	}
}