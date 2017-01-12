<?php

/**
Copyright Ameex/Commercebees
 */
namespace Ameex\Authcim\Controller\Savedcc;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;

class Savecc extends \Magento\Framework\App\Action\Action {

	public function __construct(Context $context, \Ameex\Authcim\Helper\Data $helper) {
		$this->helper = $helper;
		parent::__construct($context);
	}

	public function execute() {
		$post = $this->getRequest()->getParams();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$customerSession = $objectManager->get('Magento\Customer\Model\Session');
		$profdatamodel = $objectManager->get('Ameex\Authcim\Model\Profdata');
		$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		if (!isset($post['action'])) {

			if ($customerSession->isLoggedIn()) {
				$customerid = $customerSession->getCustomer()->getId();
				$result = $this->helper->createProfile($post, $customerid);
				if ($result['result_code'] == 'Ok') {
					$paymentprofilerequest = $this->helper->_framecustomerPaymentProfilecreationRequest($customerid, $post, $result['cim_profid']);
					if ($paymentprofilerequest['result_code'] == 'Ok') {
						try {
							$profdatamodel->setCustomerId($customerid)
								->setProfid($paymentprofilerequest['cim_profid'])
								->setCcnum($result['ccnum'])
								->setCctype($post['cctype'])
								->setIsSaved('1')
								->setPaymentProfid($paymentprofilerequest['payment_prof_id'])
								->setCreatedAt(date('m/d/Y h:i:s a', time()))
								->save();
							$this->messageManager->addSuccess(__('Card successfully saved '));
							$resultRedirect->setUrl($this->_redirect->getRefererUrl());
							return $resultRedirect;
						} catch (\Exception $e) {
							$this->messageManager->addError(__('Error occured while saving data'));
							$resultRedirect->setUrl($this->_redirect->getRefererUrl());
							return $resultRedirect;
						}
					} else {
						$this->messageManager->addError(__('An error occured during cc save ' . $result['result_code'] . ' With Error code ' . $result['response_code']));
						$resultRedirect->setUrl($this->_redirect->getRefererUrl());
						return $resultRedirect;
					}
				} else {
					$this->messageManager->addError(__('An error occured during cc save ' . $result['result_code'] . ' With Error code ' . $result['response_code']));
					$resultRedirect->setUrl($this->_redirect->getRefererUrl());
					return $resultRedirect;
				}
			} else {
				$this->messageManager->addError(__('Please login to continue.'));
				$resultRedirect = $this->resultRedirectFactory->create();
				$resultRedirect->setPath('customer/account/login');
				return $resultRedirect;
			}
		} elseif (isset($post['profid'])) {
			$action = $post['action'];
			$id = $post['id'];
			$profid = $post['profid'];
			if ($id != '' && $action == 'delete' && $profid != '') {
				$deletecard = $this->helper->deleteProfile($profid);
				if ($deletecard['result_code'] != 'Error') {
					try {

						$profdatamodel->setId($id)->delete();
						$this->messageManager->addSuccess(__('Credit Card Deleted '));
						$resultRedirect->setUrl($this->_redirect->getRefererUrl());
						return $resultRedirect;
					} catch (\Exception $e) {
						$this->messageManager->addError(__('Error occured while deleting card data ' . $e->getMessage()));
						$resultRedirect->setUrl($this->_redirect->getRefererUrl());
						return $resultRedirect;
					}
				} else {
					$this->messageManager->addError(__('Error occured while deleting card data ' . $deletecard['result_code'] . ' With Error code ' . $deletecard['response_code']));
					$resultRedirect->setUrl($this->_redirect->getRefererUrl());
					return $resultRedirect;
				}
			}
		} else {
			try {
				$action = $post['action'];
				$id = $post['id'];
				$profdatamodel->setAsPrimarycard($id);
				$this->messageManager->addSuccess(__('Credit Card Successfully Set as primary card '));
				$resultRedirect->setUrl($this->_redirect->getRefererUrl());
				return $resultRedirect;
			} catch (\Exception $e) {
				$this->messageManager->addError(__('Error occured while updating primary ' . $e->getMessage()));
				$resultRedirect->setUrl($this->_redirect->getRefererUrl());
				return $resultRedirect;
			}
		}
	}
}
