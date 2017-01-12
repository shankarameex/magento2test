<?php
namespace Ameex\Authcim\Model;
use Magento\Framework\DataObject;
use Magento\Quote\Api\Data\PaymentInterface;

class Payment extends \Magento\Payment\Model\Method\Cc {

	const CODE = 'ameex_authcim';
	const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
	const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
	const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';

	protected $_code = self::CODE;
	protected $_isGateway = true;
	protected $_canCapture = true;
	protected $_canRefund = true;
	protected $_canVoid = true;
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;
	protected $_canFetchTransactionInfo = true;
	protected $_canReviewPayment = true;
	protected $_isOffline = false;
	protected $_supportedCurrencyCodes = array(
		'USD',
	);

	protected $_debugReplacePrivateDataKeys = [
		'number',
		'exp_month',
		'exp_year',
		'cvc',
	];

	public function __construct(\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Directory\Model\CountryFactory $countryFactory,
		\Ameex\Authcim\Helper\Data $helper,
		\Ameex\Authcim\Model\Profdata $profdata,
		\Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $builder,
		\Magento\Backend\Model\Auth\Session $authSession,
		array $data = array()) {
		parent::__construct($context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData, $scopeConfig, $logger, $moduleList, $localeDate, null, null, $data);

		$this->_countryFactory = $countryFactory;
		$this->_helper = $helper;
		$this->_transactionBuilder = $builder;
		$this->_profModel = $profdata;
		$this->authSession = $authSession;
		// print_r($this->_helper->getSavedcc());
	}
	public function validate() {
		/*
			         * calling parent validate function
		*/
		//parent::validate();

		$info = $this->getInfoInstance();
		$errorMsg = false;
		$availableTypes = explode(',', $this->getConfigData('cctypes'));

		$ccNumber = $info->getCcNumber();
		$savedprofile = $info->getCcSavedCard();
		// remove credit card number delimiters such as "-" and space
		if ($savedprofile == 'NULL') {
			$ccNumber = preg_replace('/[\-\s]+/', '', $ccNumber);
			$info->setCcNumber($ccNumber);

			$ccType = '';

			if (in_array($info->getCcType(), $availableTypes)) {
				if ($this->validateCcNum(
					$ccNumber
				) || $this->otherCcType(
					$info->getCcType()
				) && $this->validateCcNumOther(
					// Other credit card type number validation
					$ccNumber
				)
				) {
					$ccTypeRegExpList = [
						//Solo, Switch or Maestro. International safe
						'SO' => '/(^(6334)[5-9](\d{11}$|\d{13,14}$))|(^(6767)(\d{12}$|\d{14,15}$))/',
						'SM' => '/(^(5[0678])\d{11,18}$)|(^(6[^05])\d{11,18}$)|(^(601)[^1]\d{9,16}$)|(^(6011)\d{9,11}$)' .
						'|(^(6011)\d{13,16}$)|(^(65)\d{11,13}$)|(^(65)\d{15,18}$)' .
						'|(^(49030)[2-9](\d{10}$|\d{12,13}$))|(^(49033)[5-9](\d{10}$|\d{12,13}$))' .
						'|(^(49110)[1-2](\d{10}$|\d{12,13}$))|(^(49117)[4-9](\d{10}$|\d{12,13}$))' .
						'|(^(49118)[0-2](\d{10}$|\d{12,13}$))|(^(4936)(\d{12}$|\d{14,15}$))/',
						// Visa
						'VI' => '/^4[0-9]{12}([0-9]{3})?$/',
						// Master Card
						'MC' => '/^5[1-5][0-9]{14}$/',
						// American Express
						'AE' => '/^3[47][0-9]{13}$/',
						// Discover
						'DI' => '/^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})' .
						'|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}' .
						'|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}' .
						'|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}' .
						'|5[0-9]{14}))$/',
						// JCB
						'JCB' => '/^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}' .
						'|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}' .
						'|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}' .
						'|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}' .
						'|5[0-9]{14}))$/',
						'MI' => '/^(5(0|[6-9])|63|67(?!59|6770|6774))\d*$/',
						'MD' => '/^(6759(?!24|38|40|6[3-9]|70|76)|676770|676774)\d*$/',
					];

					$ccNumAndTypeMatches = isset(
						$ccTypeRegExpList[$info->getCcType()]
					) && preg_match(
						$ccTypeRegExpList[$info->getCcType()],
						$ccNumber
					);
					$ccType = $ccNumAndTypeMatches ? $info->getCcType() : 'OT';

					if (!$ccNumAndTypeMatches && !$this->otherCcType($info->getCcType())) {
						$errorMsg = __('The credit card number doesn\'t match the credit card type.');
					}
				} else {
					$errorMsg = __('Invalid Credit Card Number');
				}
			} else {
				$errorMsg = __('This credit card type is not allowed for this payment method.');
			}

			//validate credit card verification number
			if ($errorMsg === false && $this->hasVerification()) {
				$verifcationRegEx = $this->getVerificationRegEx();
				$regExp = isset($verifcationRegEx[$info->getCcType()]) ? $verifcationRegEx[$info->getCcType()] : '';
				if (!$info->getCcCid() || !$regExp || !preg_match($regExp, $info->getCcCid())) {
					$errorMsg = __('Please enter a valid credit card verification number.');
				}
			}

			if ($ccType != 'SS' && !$this->_validateExpDate($info->getCcExpYear(), $info->getCcExpMonth())) {
				$errorMsg = __('Please enter a valid credit card expiration dateeeee.');
			}

			if ($errorMsg) {
				throw new \Magento\Framework\Exception\LocalizedException($errorMsg);
			}
		}
		return $this;
	}
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null) {
		/* Module to be visible only to logged in customer, to save the credit card */

		if (($this->_helper->isLoggedin() && $this->_helper->isActive()) || $this->authSession->isLoggedIn()) {
			return true;
		}
		return false;
	}
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {

		$profileid = $payment->getAdditionalInformation('profile_id');
		$savethiscard = $payment->getAdditionalInformation('save_cc');
		if ($savethiscard == '1') {
			$cardData = $this->frameCardDataToSave($payment);
			$customerid = $this->_helper->getCustomerId();
			$result = $this->_helper->createProfile($cardData, $customerid);
			if ($result['result_code'] == 'Ok') {
				$paymentprofilerequest = $this->_helper->_framecustomerPaymentProfilecreationRequest($customerid, $cardData, $result['cim_profid']);
				if ($paymentprofilerequest['result_code'] == 'Ok') {
					try {
						$this->_profModel->setCustomerId($customerid)
							->setProfid($paymentprofilerequest['cim_profid'])
							->setCcnum($result['ccnum'])
							->setCctype($cardData['cctype'])
							->setIsSaved('1')
							->setPaymentProfid($paymentprofilerequest['payment_prof_id'])
							->setCreatedAt(date('m/d/Y h:i:s a', time()))
							->save();
					} catch (\Exception $e) {
						$this->messageManager->addError(__('Error occured while saving data'));
					}
				} else {
					$this->messageManager->addError(__('An error occured during cc save ' . $result['result_code'] . ' With Error code ' . $result['response_code']));
				}
				// $profileid=$paymentprofilerequest['cim_profid'];
			}
		}
		if ($profileid != '') {

			return $this->processCimCapture($payment, $amount, $profileid);
		} else {
			return $this->processCapture($payment, $amount);
		}
	}
	public function void(\Magento\Payment\Model\InfoInterface $payment) {
		$profileid = $payment->getAdditionalInformation('profile_id');
		if ($profileid != '') {
			return $this->processCimVoidRequest($payment, $profileid);
		} else {
			return $this->processVoidRequest($payment);
		}

	}
	protected function frameCardDataToSave($payment) {
		return array(
			'ccnumber' => $payment->getCcNumber(),
			'ccyear' => $payment->getCcExpYear(),
			'ccmonth' => $payment->getCcExpMonth(),
			'cctype' => $payment->getCcType(),
		);
	}
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
		$profileid = $payment->getAdditionalInformation('profile_id');
		$savethiscard = $payment->getAdditionalInformation('save_cc');
		if ($savethiscard == '1') {
			$cardData = $this->frameCardDataToSave($payment);
			$customerid = $this->_helper->getCustomerId();
			$result = $this->_helper->createProfile($cardData, $customerid);
			if ($result['result_code'] == 'Ok') {
				$paymentprofilerequest = $this->_helper->_framecustomerPaymentProfilecreationRequest($customerid, $cardData, $result['cim_profid']);
				if ($paymentprofilerequest['result_code'] == 'Ok') {
					try {
						$this->_profModel->setCustomerId($customerid)
							->setProfid($paymentprofilerequest['cim_profid'])
							->setCcnum($result['ccnum'])
							->setCctype($cardData['cctype'])
							->setIsSaved('1')
							->setPaymentProfid($paymentprofilerequest['payment_prof_id'])
							->setCreatedAt(date('m/d/Y h:i:s a', time()))
							->save();
					} catch (\Exception $e) {
						$this->messageManager->addError(__('Error occured while saving data'));
					}
				} else {
					$this->messageManager->addError(__('An error occured during cc save ' . $result['result_code'] . ' With Error code ' . $result['response_code']));
				}
				// $profileid=$paymentprofilerequest['cim_profid'];
			}
		}
		if ($profileid != '') {
			$this->_placeCimOrder($payment, $amount, $profileid);
		} else {
			return $this->_placeOrder($payment, $amount);
		}
	}
	public function canUseForCurrency($currencyCode) {
		return true;
	}
	public function getConfigPaymentAction() {
		return $this->getConfigData('order_status') == 'pending' ? null : parent::getConfigPaymentAction();
	}
	protected function _placeOrder($payment, $amount) {
		$request = $this->_helper->frameTransactionRequest($payment, $amount);
		$this->processrequest($request, $payment, 'authorize');
		return $this;

	}
	protected function processVoidRequest($payment) {
		$request = $this->_helper->frameVoidRequest($payment);
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$transactionid = (string) $responseXmlDocument->transactionResponse->transId;
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		if ($responsemsg == 'Error') {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));

		}
		$payment->setIsTransactionClosed(true);
		return $this;
	}
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
		$profileid = $payment->getAdditionalInformation('profile_id');

		if ($profileid != '') {
			return $this->ProcessCimCreditMemo($payment, $amount, $profileid);
		} else {
			return $this->ProcessCreditMemo($payment, $amount);
		}
	}
	protected function processrequest($xmlstring, $payment, $paymentaction) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstring);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);

		$this->updateResult($responseXmlDocument, $payment, $paymentaction);

	}
	protected function updateResult($responseXmlDocument, $payment, $paymentaction) {
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$transactionid = (string) $responseXmlDocument->transactionResponse->transId;
		$AuthorizationCode = (string) $responseXmlDocument->transactionResponse->authCode;
		$order = $payment->getOrder();
		if ($responsemsg == 'Error') {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));

		}
		if ($responsemsg == 'Ok') {
			$payment->setLastTransId($transactionid);
			$payment->setAuthCode($AuthorizationCode);
			$payment->setParentTransactionId($transactionid);
			$payment->setTransactionId($transactionid);
			if ($paymentaction == 'authorize') {
				$payment->setIsTransactionClosed(false);
			} elseif ($paymentaction == 'capture') {
				$payment->setParentTransactionId($transactionid);
				$payment->setTransactionId($transactionid);
				$payment->setIsTransactionClosed(true);
			}

		}
	}
	protected function processCapture($payment, $amount) {
		$request = $this->_helper->frameCaptureTransactionRequest($payment, $amount);
		$this->processrequest($request, $payment, 'capture');
		return $this;

	}
	protected function ProcessCreditMemo($payment, $amount) {
		$request = $this->_helper->frameRefundTransactionRequest($payment, $amount);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$transactionid = (string) $responseXmlDocument->transactionResponse->transId;
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		if ($responsemsg == 'Error') {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));

		}
		return $this;
	}

	public function assignData(\Magento\Framework\DataObject $data) {
		$additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
		if (!is_object($additionalData)) {
			$additionalData = new DataObject($additionalData ?: []);
		}
		/** @var DataObject $info */
		$info = $this->getInfoInstance();
		$info->addData(
			[
				'cc_type' => $additionalData->getCcType(),
				'cc_owner' => $additionalData->getCcOwner(),
				'cc_last_4' => substr($additionalData->getCcNumber(), -4),
				'cc_number' => $additionalData->getCcNumber(),
				'cc_cid' => $additionalData->getCcCid(),
				'cc_exp_month' => $additionalData->getCcExpMonth(),
				'cc_exp_year' => $additionalData->getCcExpYear(),
				'cc_ss_issue' => $additionalData->getCcSsIssue(),
				'cc_ss_start_month' => $additionalData->getCcSsStartMonth(),
				'cc_ss_start_year' => $additionalData->getCcSsStartYear(),
				'cc_saved_card' => $additionalData->getCcSavedCard(),
				'save_cc' => $additionalData->getSaveccCcStatus(),
			]
		);
		$paymentprofileid = $this->getPaymentProfileId($additionalData->getCcSavedCard());
		$info->setAdditionalInformation('profile_id', $additionalData->getCcSavedCard());
		$info->setAdditionalInformation('payment_profile_id', $paymentprofileid);
		if ($additionalData->getSaveccCcStatus() == '1') {
			$info->setAdditionalInformation('save_cc', $additionalData->getSaveccCcStatus());
		}
		return $this;
	}
	protected function _placeCimOrder($payment, $amount, $profileid) {
		$paymentprofileid = $payment->getAdditionalInformation('payment_profile_id');
		$request = $this->_helper->frameCimAuthoriseTransactionRequest($payment, $amount, $profileid, $paymentprofileid);
		return $this->processCimAuthRequest($request, $payment);

	}
	protected function getPaymentProfileId($profileid) {
		$profmodel = $this->_profModel;
		$paymentprofileid = $profmodel->getCollection()->addFieldToFilter('profid', $profileid)->getFirstItem()->getPaymentProfid();
		return $paymentprofileid;
	}
	protected function processCimAuthRequest($xmlstring, $payment) {
		$order = $payment->getOrder();
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstring);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$directResponse = $responseXmlDocument->directResponse;
		$directResponseData = explode(',', $directResponse);
		$transactionid = $directResponseData['6'];
		$authcode = $directResponseData['4'];
		if ($responsemsg == 'Ok') {
			// $order->addStatusHistoryComment('Successfully authorized with Transaction id : '.$transactionid);
			$payment->setLastTransId($transactionid);
			$payment->setAuthCode($authcode);
			$payment->setParentTransactionId($transactionid);
			$payment->setTransactionId($transactionid);
			$payment->setCcLast4($directResponseData['50']);
			$payment->setCcType($directResponseData['51']);
			$payment->setIsTransactionClosed(false);
			$order->addStatusHistoryComment('Order Placed Using Saved Credit card');
		} else {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));
		}
		return $this;
	} //CIM Authorise END
	protected function processCimCptureRequest($xmlstring, $payment) {
		$order = $payment->getOrder();

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlstring);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$directResponse = $responseXmlDocument->directResponse;
		$directResponseData = explode(',', $directResponse);
		$transactionid = $directResponseData['6'];
		$authcode = $directResponseData['4'];
		if ($responsemsg == 'Ok') {
			//$order->addStatusHistoryComment('Successfully Captured with Transaction id : '.$transactionid);
			$payment->setLastTransId($transactionid);
			$payment->setAuthCode($authcode);
			$payment->setParentTransactionId($transactionid);
			$payment->setTransactionId($transactionid);
			$payment->setCcLast4($directResponseData['50']);
			$payment->setCcType($directResponseData['51']);
			$payment->setIsTransactionClosed(true);
			$order->addStatusHistoryComment('Order Placed Using Saved Credit card');
		} else {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));
		}
		return $this;
	} //CIM Capture END
	protected function processCimVoidRequest($payment, $profileid) {
		$paymentprofileid = $payment->getAdditionalInformation('payment_profile_id');
		$request = $this->_helper->frameCimVoidRequest($payment, $profileid, $paymentprofileid);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$transactionid = (string) $responseXmlDocument->transactionResponse->transId;
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		if ($responsemsg == 'Error') {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));

		}
		$payment->setIsTransactionClosed(true);
		return $this;
	}
	protected function ProcessCimCreditMemo($payment, $amount, $profileid) {
		$paymentprofileid = $payment->getAdditionalInformation('payment_profile_id');
		$request = $this->_helper->frameCimRefundTransactionRequest($payment, $amount, $profileid, $paymentprofileid);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->_helper->getCgiurl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$transactionid = (string) $responseXmlDocument->transactionResponse->transId;
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		if ($responsemsg == 'Error') {
			throw new \Magento\Framework\Exception\LocalizedException(__('Error Occured with Error code: ' . $responsecode . '-' . $responsetext));

		}
		$payment->setIsTransactionClosed(true);
		return $this;
	}

	protected function processCimCapture($payment, $amount, $profileid) {
		$paymentprofileid = $payment->getAdditionalInformation('payment_profile_id');
		$request = $this->_helper->frameCimCaptureTransactionRequest($payment, $amount, $profileid, $paymentprofileid);
		return $this->processCimCptureRequest($request, $payment);
	}
}
