<?php

class bdPaygateInterkassa_Audentio_DonationManager_Model_Campaign extends XFCP_bdPaygateInterkassa_Audentio_DonationManager_Model_Campaign
{
    public function getCurrencies($currency = false)
    {
        $currencies = parent::getCurrencies();

        $currencies[bdPaygateInterkassa_Processor::CURRENCY_RUB] = array(
            'name' => 'RUB',
            'suffix' => '₽'
        );
        $currencies[bdPaygateInterkassa_Processor::CURRENCY_UAH] = array(
            'name' => 'UAH',
            'suffix' => '₴'
        );

        if ($currency && array_key_exists($currency, $currencies)) {
            return $currencies[$currency];
        }

        return $currencies;
    }
}