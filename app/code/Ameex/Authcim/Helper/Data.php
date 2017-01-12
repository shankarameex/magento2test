<?php

/**
 * Copyright © 2015 Ameex . All rights reserved.
 */
namespace Ameex\Authcim\Helper;
class Data extends \Magento\Framework\App\Helper\AbstractHelper {

	/**
	 *
	 * @param \Magento\Framework\App\Helper\Context $context
	 */
	const CGI_URL = 'https://api.authorize.net/xml/v1/request.api';

	const CGI_URL_TD = 'https://apitest.authorize.net/xml/v1/request.api';
	const REQUEST_TYPE_AUTH_CAPTURE = 'AUTH_CAPTURE';
	const REQUEST_TYPE_AUTH_ONLY = 'AUTH_ONLY';
	const REQUEST_TYPE_CAPTURE_ONLY = 'CAPTURE_ONLY';
	public function __construct(
		\Magento\Framework\App\Helper\Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\HTTP\Client\Curl $curl,
		\Magento\Customer\Model\Customer $customer,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Ameex\Authcim\Model\Profdata $ccmodel,
		\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $requestHttp,
		\Ameex\Authcim\Model\Profdata $profdata,
		\Magento\Customer\Model\Session $customerSession
	) {
		$this->customer = $customer;
		$this->scopeconfig = $scopeConfig;
		$this->_curl = $curl;
		$this->_encryptor = $encryptor;
		$this->_ccmodel = $ccmodel;
		$this->_customersession = $customerSession;
		$this->_requestHttp = $requestHttp;
		$this->_profModel = $profdata;
		parent::__construct($context);
	}

	public function createProfile($carddata, $customerid) {
		$login = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$transkey = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/transactionkey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$last4ccnumber = "XXXX" . substr($carddata['ccnumber'], -4);
		$customeremail = $this->customer->load($customerid)->getEmail();
		$expdate = $carddata['ccyear'] . '-' . $carddata['ccmonth'];
		$newexpirydate = date('Y-m', strtotime($expdate));
		$ccnumber = $carddata['ccnumber'];
		$customer_id = mt_rand(1, 99999999) . $customerid;
		$description = 'Credit card saved for customer email ' . $customeremail;
		$requestBody = sprintf('<?xml version="1.0" encoding="utf-8"?>' . '<createCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">' . '<merchantAuthentication><name>%s</name><transactionKey>%s</transactionKey></merchantAuthentication>' . '<profile><merchantCustomerId>%s</merchantCustomerId><description>%s</description><email>%s</email><paymentProfiles><customerType>individual</customerType><payment><creditCard><cardNumber>%s</cardNumber><expirationDate>%s</expirationDate></creditCard></payment></paymentProfiles></profile>' . '</createCustomerProfileRequest>', $login, $transkey, $customer_id, $description, $customeremail, $ccnumber, $newexpirydate);

		// $client = new Varien_Http_Client();
		$url = $this->scopeconfig->getValue('payment/ameex_authcim/gatewayurl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (strpos($url, 'test') !== false) {
			$uri = self::CGI_URL_TD;
		} else {
			$uri = self::CGI_URL;
		}
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$debugData = array(
			'request' => $result,
		);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$customerprofileid = (string) $responseXmlDocument->customerProfileId;
		// echo $responsemsg.' cim '.$customerprofileid;exit;
		$returndata = array(
			'result_code' => $responsemsg,
			'response_code' => $responsecode,
			'response_text' => $responsetext,
			'ccnum' => $last4ccnumber,
			'cim_profid' => $customerprofileid,
		);
		return $returndata;
	}
	public function _framecustomerPaymentProfilecreationRequest($cusid, $ccdet, $profid) {
		$login = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$transkey = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/transactionkey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$customer = $this->customer->load($cusid);
		$customerfname = $customer->getFirstname();
		$customerlname = $customer->getLastname();
		$expdate = $ccdet['ccyear'] . '-' . $ccdet['ccmonth'];
		$newexpirydate = date('Y-m', strtotime($expdate));
		$requestBody = sprintf(
			'<?xml version="1.0" encoding="utf-8"?>'
			. '<createCustomerPaymentProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">'
			. '<merchantAuthentication><name>%s</name><transactionKey>%s</transactionKey></merchantAuthentication>'
			. '<customerProfileId>%s</customerProfileId>' .
			'<paymentProfile>' .
			'<billTo>' .
			'<firstName>%s</firstName>' .
			'<lastName>%s</lastName>' .
			'</billTo>' .
			'<payment>' .
			'<creditCard>' .
			'<cardNumber>%s</cardNumber>' .
			'<expirationDate>%s</expirationDate>' .
			'</creditCard>' .
			'</payment>' .
			'</paymentProfile>' .
			'</createCustomerPaymentProfileRequest>',
			$login,
			$transkey,
			$profid,
			$customerfname,
			$customerlname,
			$ccdet['ccnumber'],
			$newexpirydate
		);

		$url = $this->scopeconfig->getValue('payment/ameex_authcim/gatewayurl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (strpos($url, 'test') !== false) {
			$uri = self::CGI_URL_TD;
		} else {
			$uri = self::CGI_URL;
		}
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$debugData = array(
			'request' => $result,
		);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$customerprofileid = (string) $responseXmlDocument->customerProfileId;
		$paymentprofid = (string) $responseXmlDocument->customerPaymentProfileId;
		// echo $responsemsg.' cim '.$customerprofileid;exit;
		$returndata = array(
			'result_code' => $responsemsg,
			'response_code' => $responsecode,
			'response_text' => $responsetext,
			'cim_profid' => $customerprofileid,
			'payment_prof_id' => $paymentprofid,
		);
		return $returndata;
	}
	public function deleteProfile($profid) {
		$login = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$transkey = $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/transactionkey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
		$requestBody = sprintf('<?xml version="1.0" encoding="utf-8"?>' . '<deleteCustomerProfileRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">' . '<merchantAuthentication><name>%s</name><transactionKey>%s</transactionKey></merchantAuthentication>' . '<customerProfileId>%s</customerProfileId>' . '</deleteCustomerProfileRequest>', $login, $transkey, $profid);
		$url = $this->scopeconfig->getValue('payment/authorizenet_directpost/cgi_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (strpos($url, 'test') !== false) {
			$uri = self::CGI_URL_TD;
		} else {
			$uri = self::CGI_URL;
		}
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $uri);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: text/plain',
		));
		$result = curl_exec($ch);
		$responseXmlDocument = simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOWARNING);
		$responsemsg = (string) $responseXmlDocument->messages->resultCode;
		$responsecode = (string) $responseXmlDocument->messages->message->code;
		$responsetext = (string) $responseXmlDocument->messages->message->text;
		$customerprofileid = (string) $responseXmlDocument->customerProfileId;
		$returndata = array(
			'result_code' => $responsemsg,
			'response_code' => $responsecode,
			'response_text' => $responsetext,
		);
		return $returndata;
	}
	public function getCgiurl() {
		$url = $this->scopeconfig->getValue('payment/ameex_authcim/gatewayurl', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if (strpos($url, 'test') !== false) {
			$uri = self::CGI_URL_TD;
		} else {
			$uri = self::CGI_URL;}
		return $uri;
	}
	public function getApiLoginKey() {
		return $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/api_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
	}

	public function getTransactionKey() {
		return $this->_encryptor->decrypt($this->scopeconfig->getValue('payment/ameex_authcim/transactionkey', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
	}

	public function getcctypes() {
		$cctypes = $this->scopeconfig->getValue('payment/ameex_authcim/cctypes', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		return explode(',', $cctypes);
	}

	public function Requirecvv() {
		return $this->scopeconfig->getValue('payment/ameex_authcim/cvv', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function getSavedcc() {
		$Model = $this->_ccmodel;
		$collection = $Model->getSavedCardCollection();
		$cardcount = $collection->getSize();
		if ($cardcount > 0) {
			foreach ($collection as $data) {
				$return[] = array('profid' => $data->getProfid(), 'ccnum' => $data->getCcnum() . ' - ' . $this->getFullCctypename($data->getCctype()));
			}
			return $return;
		}
	}
	public function getFullCctypename($cctype) {
		if ($cctype == 'VI') {
			return 'Visa';
		}
		if ($cctype == 'AE') {
			return 'American Express';
		}
		if ($cctype == 'MC') {
			return 'MasterCard';
		}

		if ($cctype == 'DI') {

			return 'Discover';
		}
		if ($cctype == 'OT') {
			return 'Other';
		}
	}
	public function hasSavedcardData() {
		$Model = $this->_ccmodel;
		$collection = $Model->getSavedCardCollection();
		$cardcount = $collection->getSize();
		//echo $cardcount;exit;
		if ($cardcount > 0) {
			return 1;
		} else {
			return 0;
		}
	}
	public function isLoggedin() {
		if ($this->_customersession->isLoggedIn()) {
			return true;
		} else {
			return false;
		}
	}
	public function getCustomerId() {
		if ($this->isLoggedin()) {
			return $this->_customersession->getCustomer()->getId();
		}
	}
	public function getPrimaryProfId() {
		$customerid = $this->getCustomerId();
		$primarycard = $this->_profModel->getCollection()
			->addFieldToFilter('customer_id', $customerid)
			->addFieldToFilter('is_primary', '1')
			->getFirstItem()->getProfid();
		if ($primarycard != '') {
			return $primarycard;
		} else {
			return '0';
		}

	}
	public function isActive() {
		$active = $this->scopeconfig->getValue('payment/ameex_authcim/active', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if ($active == '1') {
			return true;
		} else {
			return false;
		}
	}
	public function getPaymentaction() {
		$paymentaction = $this->scopeconfig->getValue('payment/ameex_authcim/payment_action', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		if ($paymentaction == 'authorize') {
			return 'authOnlyTransaction';
		} else if ($paymentaction == 'authorize_capture') {
			return 'authCaptureTransaction';
		} else {
			return 'captureOnlyTransaction';
		}
	}
	public function getFormateddate($date) {
		return date('Y-m', strtotime($date));
	}
	public function IsRequiredLineitems() {
		return $this->scopeconfig->getValue('payment/ameex_authcim/displaylineitems', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
	}
	public function frameTransactionRequest($payment, $amount) {
		$order = $payment->getOrder();
		$billingaddress = $order->getBillingAddress();
		$shippingaddress = $order->getShippingAddress();
		$expdate = $this->getFormateddate($payment->getCcExpYear() . '-' . $payment->getCcExpMonth());
		$items = $order->getAllVisibleItems();
		$reqString = '';
		$reqString = $reqString . '
       <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
          <merchantAuthentication>
            <name>' . $this->getApiLoginKey() . '</name>
            <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
          </merchantAuthentication>
          <refId>123456</refId>
          <transactionRequest>
            <transactionType>' . $this->getPaymentaction() . '</transactionType>
            <amount>' . $amount . '</amount>
            <payment>
              <creditCard>
                <cardNumber>' . $payment->getCcNumber() . '</cardNumber>
                <expirationDate>' . $expdate . '</expirationDate>';
		if ($this->Requirecvv() == '1') {
			$reqString = $reqString . '<cardCode>' . $payment->getCcCid() . '</cardCode>';
		}
		$reqString = $reqString . '</creditCard>
            </payment>
            <order>
             <invoiceNumber>' . $order->getIncrementId() . '</invoiceNumber>
             <description>' . $order->getIncrementId() . '</description>
            </order>';
		if (!empty($billingaddress)) {
			$street = $billingaddress->getStreet();
			$reqString = $reqString . '
            <billTo>
              <firstName>' . $billingaddress->getFirstname() . '</firstName>
              <lastName>' . $billingaddress->getLastname() . '</lastName>
              <company>' . $billingaddress->getCompany() . '</company>
              <address>' . $street[0] . '</address>
              <city>' . $billingaddress->getCity() . '</city>
              <state>' . $billingaddress->getRegion() . '</state>
              <zip>' . $billingaddress->getPostcode() . '</zip>
              <country>' . $billingaddress->getCountryId() . '</country>
            </billTo>';
		}
		if (!empty($shippingaddress)) {
			$shipstreet = $shippingaddress->getStreet();
			$reqString = $reqString . '<shipTo>
              <firstName>' . $shippingaddress->getFirstname() . '</firstName>
              <lastName>' . $shippingaddress->getLastname() . '</lastName>
              <company>' . $shippingaddress->getCompany() . '</company>
              <address>' . $shipstreet[0] . '</address>
              <city>' . $shippingaddress->getCity() . '</city>
              <state>' . $shippingaddress->getRegion() . '</state>
              <zip>' . $shippingaddress->getPostcode() . '</zip>
              <country>' . $shippingaddress->getCountryId() . '</country>
            </shipTo>';
		}
		$reqString = $reqString . '<customerIP>' . $this->_requestHttp->getRemoteAddress() . '</customerIP>
            <!-- Uncomment this section for Card Present Sandbox Accounts -->
            <!-- <retail><marketType>2</marketType><deviceType>1</deviceType></retail> -->
          </transactionRequest>
      </createTransactionRequest>';
		return $reqString;
	}
	public function frameCaptureTransactionRequest($payment, $amount) {
		//<transactionType>captureOnlyTransaction</transactionType><authCode>authorization code here</authCode>;
		$order = $payment->getOrder();
		$billingaddress = $order->getBillingAddress();
		$shippingaddress = $order->getShippingAddress();
		$expdate = $this->getFormateddate($payment->getCcExpYear() . '-' . $payment->getCcExpMonth());
		$items = $order->getAllVisibleItems();
		$paymentaction = $this->getPaymentaction();
		$req = '';
		$req = $req . '
      <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
          <merchantAuthentication>
            <name>' . $this->getApiLoginKey() . '</name>
            <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
          </merchantAuthentication>
          <refId>123456</refId>
          <transactionRequest>';
		if ($paymentaction == 'authOnlyTransaction') {
			$req = $req . '
           <transactionType>priorAuthCaptureTransaction</transactionType>
            <amount>' . $amount . '</amount>
            <refTransId>' . $payment->getLastTransId() . '</refTransId>
            <order>
              <invoiceNumber>' . $order->getIncrementId() . '</invoiceNumber>
            </order>';
		}
		if ($paymentaction == 'authCaptureTransaction') {
			$req = $req . '
                    <transactionType>authCaptureTransaction</transactionType>
                    <amount>' . $amount . '</amount>
                    <payment>
                      <creditCard>
                        <cardNumber>' . $payment->getCcNumber() . '</cardNumber>
                        <expirationDate>' . $expdate . '</expirationDate>';
			if ($this->Requirecvv() == '1') {
				$req = $req . '<cardCode>' . $payment->getCcCid() . '</cardCode>';
			}
			$req = $req . '</creditCard>
                    </payment>
                    <order>
                     <invoiceNumber>' . $order->getIncrementId() . '</invoiceNumber>
                    </order>';
			if (!empty($billingaddress)) {
				$street = $billingaddress->getStreet();
				$req = $req . '<billTo>
                      <firstName>' . $billingaddress->getFirstname() . '</firstName>
                      <lastName>' . $billingaddress->getLastname() . '</lastName>
                      <company>' . $billingaddress->getCompany() . '</company>
                      <address>' . $street[0] . '</address>
                      <city>' . $billingaddress->getCity() . '</city>
                      <state>' . $billingaddress->getRegion() . '</state>
                      <zip>' . $billingaddress->getPostcode() . '</zip>
                      <country>' . $billingaddress->getCountryId() . '</country>
                    </billTo>';
			}
			if (!empty($shippingaddress)) {
				$shipstreet = $shippingaddress->getStreet();
				$req = $req . '<shipTo>
                      <firstName>' . $shippingaddress->getFirstname() . '</firstName>
                      <lastName>' . $shippingaddress->getLastname() . '</lastName>
                      <company>' . $shippingaddress->getCompany() . '</company>
                      <address>' . $shipstreet[0] . '</address>
                      <city>' . $shippingaddress->getCity() . '</city>
                      <state>' . $shippingaddress->getRegion() . '</state>
                      <zip>' . $shippingaddress->getPostcode() . '</zip>
                      <country>' . $shippingaddress->getCountryId() . '</country>
                    </shipTo>';
			}
			$req = $req . '   <customerIP>' . $this->_requestHttp->getRemoteAddress() . '</customerIP>
                  ';
		}
		$req = $req . '</transactionRequest>
        </createTransactionRequest>';
		return $req;
	}
	public function frameVoidRequest($payment) {
		$request = '';
		$request = $request . '
                <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                  <merchantAuthentication>
                    <name>' . $this->getApiLoginKey() . '</name>
                    <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                  </merchantAuthentication>
                  <transactionRequest>
                    <transactionType>voidTransaction</transactionType>
                    <refTransId>' . $payment->getLastTransId() . '</refTransId>
                   </transactionRequest>
                </createTransactionRequest>
            ';
		return $request;
	}
	public function frameRefundTransactionRequest($payment, $amount) {
		$request = '';
		$request = $request . '
                <createTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                  <merchantAuthentication>
                    <name>' . $this->getApiLoginKey() . '</name>
                    <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                  </merchantAuthentication>
                  <transactionRequest>
                    <transactionType>refundTransaction</transactionType>
                    <amount>' . $amount . '</amount>
                    <payment>
                      <creditCard>
                        <cardNumber>' . $payment->getCcLast4() . '</cardNumber>
                        <expirationDate>XXXX</expirationDate>
                      </creditCard>
                    </payment>
                    <refTransId>' . $payment->getLastTransId() . '</refTransId>
                    <order>
                        <invoiceNumber>' . $payment->getOrder()->getIncrementId() . '</invoiceNumber>
                    </order>
                   </transactionRequest>
                </createTransactionRequest>
            ';
		return $request;
	}
	public function frameCimAuthoriseTransactionRequest($payment, $amount, $profileid, $paymentprofileid) {
		$order = $payment->getOrder();
		$requestBody = sprintf(
			'<?xml version="1.0" encoding="utf-8"?>'
			. '<createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">' .
			'<merchantAuthentication><name>%s</name><transactionKey>%s</transactionKey></merchantAuthentication>' .
			'<transaction>' .
			'<profileTransAuthOnly>' .
			'<amount>%s</amount>' .
			'<customerProfileId>%s</customerProfileId>' .
			'<customerPaymentProfileId>%s</customerPaymentProfileId>' .
			'<order>' .
			'<invoiceNumber>%s</invoiceNumber>' .
			'</order>' .
			'</profileTransAuthOnly>' .
			'</transaction>' .
			'</createCustomerProfileTransactionRequest>',
			$this->getApiLoginKey(),
			$this->getTransactionKey(),
			$amount,
			$profileid,
			$paymentprofileid,
			$order->getIncrementId()
		);
		return $requestBody;
	}
	public function frameCimCaptureTransactionRequest($payment, $amount, $profileid, $paymentprofileid) {
		$order = $payment->getOrder();
		$paymentaction = $this->getPaymentaction();
		if ($paymentaction == 'authOnlyTransaction') {
			$requestBody = '
                  <?xml version="1.0" encoding="utf-8"?>
                  <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                      <merchantAuthentication>
                          <name>' . $this->getApiLoginKey() . '</name>
                          <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                      </merchantAuthentication>
                      <transaction>
                          <profileTransPriorAuthCapture>
                              <amount>' . $amount . '</amount>
                              <customerProfileId>' . $profileid . '</customerProfileId>
                              <customerPaymentProfileId>' . $paymentprofileid . '</customerPaymentProfileId>
                              <transId>' . $payment->getLastTransId() . '</transId>
                          </profileTransPriorAuthCapture>
                      </transaction>
                  </createCustomerProfileTransactionRequest>
            ';
		}
		if ($paymentaction == 'authCaptureTransaction') {
			$requestBody = '
                  <?xml version="1.0" encoding="utf-8"?>
                  <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                      <merchantAuthentication>
                          <name>' . $this->getApiLoginKey() . '</name>
                          <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                      </merchantAuthentication>
                      <transaction>
                          <profileTransAuthCapture>
                              <amount>' . $amount . '</amount>
                              <customerProfileId>' . $profileid . '</customerProfileId>
                              <customerPaymentProfileId>' . $paymentprofileid . '</customerPaymentProfileId>
                              <order>
                                  <invoiceNumber>' . $order->getIncrementId() . '</invoiceNumber>
                              </order>
                          </profileTransAuthCapture>
                      </transaction>
                  </createCustomerProfileTransactionRequest>
            ';
		}
		return $requestBody;
	}
	public function frameCimVoidRequest($payment, $profileid, $paymentprofileid) {
		$requestBody = '
                  <?xml version="1.0" encoding="utf-8"?>
                  <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                      <merchantAuthentication>
                          <name>' . $this->getApiLoginKey() . '</name>
                          <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                      </merchantAuthentication>
                      <transaction>
                         <profileTransVoid>
                              <customerProfileId>' . $profileid . '</customerProfileId>
                              <customerPaymentProfileId>' . $paymentprofileid . '</customerPaymentProfileId>
                              <transId>' . $payment->getLastTransId() . '</transId>
                          </profileTransVoid>
                      </transaction>
                  </createCustomerProfileTransactionRequest>
            ';
		return $requestBody;
	}
	public function frameCimRefundTransactionRequest($payment, $amount, $profileid, $paymentprofileid) {
		$requestbody = '
            <?xml version="1.0" encoding="utf-8"?>
            <createCustomerProfileTransactionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">
                <merchantAuthentication>
                    <name>' . $this->getApiLoginKey() . '</name>
                    <transactionKey>' . $this->getTransactionKey() . '</transactionKey>
                </merchantAuthentication>
                <transaction>
                    <profileTransRefund>
                        <amount>' . $amount . '</amount>
                        <customerProfileId>' . $profileid . '</customerProfileId>
                        <customerPaymentProfileId>' . $paymentprofileid . '</customerPaymentProfileId>
                        <creditCardNumberMasked>' . $payment->getCcLast4() . '</creditCardNumberMasked>
                        <order>
                            <invoiceNumber>' . $payment->getOrder()->getIncrementId() . '</invoiceNumber>
                        </order>
                        <transId>' . $payment->getLastTransId() . '</transId>
                    </profileTransRefund>
                </transaction>
            </createCustomerProfileTransactionRequest>
      ';
		return $requestbody;
	}

}
