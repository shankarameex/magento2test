<?php
namespace Ameex\Authcim\Model;

use Magento\Framework\Model\AbstractModel;

class Profdata extends AbstractModel {

	/**
	 * Define resource model
	 */
	protected function _construct() {
		$this->_init('Ameex\Authcim\Model\ResourceModel\Profdata');
	}

	public function getSavedCardCollection() {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->create('Magento\Customer\Model\Session');
		$customerid = $customerSession->getCustomer()->getId();
		//var_dump($customerid);exit;
		return $this->getCollection()->addFieldToFilter('customer_id', $customerid);
	}

	public function setAsPrimarycard($id) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$saved_cc_model = $objectManager->create('Ameex\Authcim\Model\Profdata');
		$col = $saved_cc_model->getCollection();
		foreach ($col as $dat) {
			if ($dat->getId() != $id) {
				$this->setNonPrimary($dat->getId());
			}
			if ($dat->getId() == $id) {
				$this->setPrimary($id);
			}
		}
	}
	public function getAllowedcards() {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$helper = $objectManager->create('Ameex\Authcim\Helper\Data');
		return $helper->getcctypes();
	}
	public function setNonPrimary($id) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$saved_cc_model = $objectManager->create('Ameex\Authcim\Model\Profdata');
		$saved_cc_model->setId($id)
			->setIsPrimary('0')
			->save();
	}

	public function setPrimary($id) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$saved_cc_model = $objectManager->create('Ameex\Authcim\Model\Profdata');
		$saved_cc_model->setId($id)
			->setIsPrimary('1')
			->save();
	}
}
?>