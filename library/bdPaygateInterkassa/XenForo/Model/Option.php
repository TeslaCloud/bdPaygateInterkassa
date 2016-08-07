<?php

class bdPaygateInterkassa_XenForo_Model_Option extends XFCP_bdPaygateInterkassa_XenForo_Model_Option
{
	// this property must be static because XenForo_ControllerAdmin_UserUpgrade::actionIndex
	// for no apparent reason use XenForo_Model::create to create the optionModel
	// (instead of using XenForo_Controller::getModelFromCache)
	private static $_bdPaygateInterkassa_hijackOptions = false;
	
	public function getOptionsByIds(array $optionIds, array $fetchOptions = array())
	{
		if (self::$_bdPaygateInterkassa_hijackOptions === true)
		{
			$optionIds[] = 'bdPaygateInterkassa_ID';
			$optionIds[] = 'bdPaygateInterkassa_SecretKey';
            $optionIds[] = 'bdPaygateInterkassa_SecretKey_Test';
			$optionIds[] = 'bdPaygateInterkassa_SuccessUrl';
            $optionIds[] = 'bdPaygateInterkassa_FailUrl';
		}
		
		$options = parent::getOptionsByIds($optionIds, $fetchOptions);
		
		self::$_bdPaygateInterkassa_hijackOptions = false;

		return $options;
	}
	
	public function bdPaygateInterkassa_hijackOptions()
	{
		self::$_bdPaygateInterkassa_hijackOptions = true;
	}
}