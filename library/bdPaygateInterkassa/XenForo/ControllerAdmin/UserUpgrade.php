<?php

class bdPaygateInterkassa_XenForo_ControllerAdmin_UserUpgrade extends XFCP_bdPaygateInterkassa_XenForo_ControllerAdmin_UserUpgrade
{
	public function actionIndex()
	{
		$optionModel = $this->getModelFromCache('XenForo_Model_Option');
		$optionModel->bdPaygateInterkassa_hijackOptions();
		
		return parent::actionIndex();
	}
}