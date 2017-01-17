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
        if ($this->_sandboxMode()) {
            if (empty($options->bdPaygateInterkassa_ID) || empty($options->bdPaygateInterkassa_SecretKey_Test)) {
                return false;
            }
        } else {
            if (empty($options->bdPaygateInterkassa_ID) || empty($options->bdPaygateInterkassa_SecretKey)) {
                return false;
            }
        }

        return true;
    }

    public function isRecurringSupported()
    {
        return false;
    }

    public function validateCallback(Zend_Controller_Request_Http $request, &$transactionId, &$paymentStatus, &$transactionDetails, &$itemId)
    {
        $input = new XenForo_Input($request);
        $transactionDetails = $input->getInput();

        $options = XenForo_Application::get('options');

        if(empty($transactionDetails['ik_inv_id'])) {
            $this->_setError("Invalid Id");
            return false;
        }

        $interkassa_key = ($transactionDetails['ik_pw_via'] == 'test_interkassa_test_xts') ? $options->bdPaygateInterkassa_SecretKey_Test : $options->bdPaygateInterkassa_SecretKey;
        $transactionId = 'interkassa_' . $transactionDetails['ik_inv_id'];
        $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_OTHER;

        $processorModel = $this->getModelFromCache('bdPaygate_Model_Processor');

        // Проверяем, не была ли уже проведена такая операция
        $log = $processorModel->getLogByTransactionId($transactionId);
        if (!empty($log))
        {
            if(($log['log_type'] == 'accepted') || ($log['log_type'] == 'rejected') || ($log['log_type'] == 'error'))
            {
                $this->_setError("Transaction {$transactionId} has already been processed");
                echo "OK";
                return false;
            }
        }
        else
        {
            if(!$this->ikMd5($transactionDetails, $interkassa_key))
            {
                $this->_setError('interkassa_'.$transactionDetails['ik_inv_id']." Incorrect sign. See log file");
                $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_REJECTED;
                echo "ERROR";
                return false;
            }
            else
            {
                $itemId = base64_decode($transactionDetails['ik_x_item']);
                $paymentStatus = bdPaygate_Processor_Abstract::PAYMENT_STATUS_ACCEPTED;
                echo "OK $itemId";
            }
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
        $visitor = XenForo_Visitor::getInstance();
        $interkassa_key = $options->bdPaygateInterkassa_SecretKey;

        $payment = array(
            'ik_co_id'  => $options->bdPaygateInterkassa_ID,
            'ik_pm_no'  => substr(md5(time()), 0, 6),
            'ik_cur'    => utf8_strtoupper($currency),
            'ik_am'     => $amount,
            'ik_desc'   => $itemName,
            'ik_cli'    => $visitor->email,
            'ik_x_item' => base64_encode($itemId),
            'ik_suc_u'  => $extraData['returnUrl'],
            'ik_suc_m'  => 'GET',
            'ik_fal_u'  => $options->bdPaygateInterkassa_FailUrl,
            'ik_fal_m'  => 'GET'
        );

        $payment['ik_sign'] = $this->ikMd5($payment, $interkassa_key, true);

        // Генерация формы
        $form = "<form action=\"{$formAction}\" method=\"POST\">";
        foreach ($payment as $item => $value){
            $form .= "<input type=\"hidden\" name=\"$item\" value=\"$value\" />";
        }
        $form .= "<input type=\"submit\" value=\"{$callToAction}\" class=\"button\"/></form>";

        return $form;
    }

    private function ikMd5($transactionDetails, $shopPassword, $generate = false){
        $receivedMd5 = $transactionDetails['ik_sign'];
        unset($transactionDetails['ik_sign'], $transactionDetails['p']);

        /// Генерация MD5 подписи
        // Сортировка эл-тов массива в алфавитном порядке
        ksort($transactionDetails, SORT_STRING);
        // Добавление секретного ключа в конец массива
        $transactionDetails['ik_sign'] = $shopPassword;
        // Конкатенация значений через символ ":"
        $crc = implode(':', $transactionDetails);
        // Кодирование MD5 хэша в BASE64
        $generatedMd5 = base64_encode(md5($crc, true));
        if($generate == true)
            return $generatedMd5;

        if ($receivedMd5 != $generatedMd5) {
            $this->log('interkassa_'.$transactionDetails['ik_inv_id']." Wait for md5:" . $generatedMd5 . ", received md5: " . $receivedMd5);
            return false;
        }
        return true;
    }

    private function log($str) {
        if(is_array($str) || is_object($str)) {
            $str = print_r($str,true);
        }
        $str = $str . "\n";
        file_put_contents('InterkassaErrors.txt', '[' . date("Y-m-d H:i:s") . '] ' . $str, FILE_APPEND);
    }
}