<?php

class bdPaygateInterkassa_bdPaygate_Model_Processor extends XFCP_bdPaygateInterkassa_bdPaygate_Model_Processor
{
    public function getCurrencies()
    {
        $currencies = parent::getCurrencies();

        $currencies[bdPaygateInterkassa_Processor::CURRENCY_RUB] = 'RUB';
        $currencies[bdPaygateInterkassa_Processor::CURRENCY_UAH] = 'UAH';

        return $currencies;
    }

    public function getProcessorNames()
    {
        $names = parent::getProcessorNames();

        $names['interkassa'] = 'bdPaygateInterkassa_Processor';

        return $names;
    }
}