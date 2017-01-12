<?php
namespace Ameex\Authcim\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

class AuthConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface {

	/**
	 *
	 * @var ResolverInterface
	 */
	private $icons = [];

	public function __construct(\Ameex\Authcim\Helper\Data $helper, \Magento\Payment\Model\CcConfig $paymentconfig, \Magento\Payment\Helper\Data $PaymentHelper, \Magento\Framework\View\Asset\Source $source) {
		$this->_helper = $helper;
		$this->_paymentconfig = $paymentconfig;
		$this->methods['ameex_authcim'] = $PaymentHelper->getMethodInstance('ameex_authcim');
		$this->assetSource = $source;
	}

	public function getConfig() {
		$config = [];
		$cctypes = $this->getCcAvailableTypes('ameex_authcim');
		$ccmonth = $this->_paymentconfig->getCcMonths();
		$ccyears = $this->_paymentconfig->getCcYears();
		$verification = $this->_helper->Requirecvv();
		$config = array_merge_recursive($config, [
			'payment' => [
				'ccform1' => [
					'availableTypes' => [
						'ameex_authcim' => $cctypes,
					],
					'months' => [
						'ameex_authcim' => $ccmonth,
					],
					'years' => [
						'ameex_authcim' => $ccyears,
					],
					'hasVerification' => [
						'ameex_authcim' => $verification,
					],
					'cvvImageUrl' => [
						'ameex_authcim' => $this->getCvvImageUrl(),
					],
					'savedcard' => [
						'ameex_authcim' => $this->getSavedCc(),
					],
					'primarycard' => [
						'ameex_authcim' => $this->getPrimaryData(),
					],
					'hasSavedcards' => [
						'ameex_authcim' => $this->hasSavedcards(),
					],
					'icons' => $this->getIcons(),
				],
			],
		]);

		// print_r($config);exit;
		return $config;
	}

	public function getCvvImageUrl() {
		return $this->_paymentconfig->getViewFileUrl('Magento_Checkout::cvv.png');
	}
	public function getPrimaryData() {
		return $this->_helper->getPrimaryProfId();
	}
	protected function getCcAvailableTypes($methodCode) {
		$types = $this->_paymentconfig->getCcAvailableTypes();
		$availableTypes = $this->methods[$methodCode]->getConfigData('cctypes');
		if ($availableTypes) {
			$availableTypes = explode(',', $availableTypes);
			foreach (array_keys($types) as $code) {
				if (!in_array($code, $availableTypes)) {
					unset($types[$code]);
				}
			}
		}

		return $types;
	}

	public function getIcons() {
		if (!empty($this->icons)) {
			return $this->icons;
		}

		$types = $this->_paymentconfig->getCcAvailableTypes();
		foreach (array_keys($types) as $code) {
			// if (! array_key_exists($code, $this->icons)) {
			$asset = $this->_paymentconfig->createAsset('Magento_Payment::images/cc/' . strtolower($code) . '.png');
			$placeholder = $this->assetSource->findSource($asset);
			if ($placeholder) {
				list($width, $height) = getimagesize($asset->getSourceFile());
				$imgurl = explode(',', $asset->getUrl());
				$this->icons[$code] = [
					'url' => $imgurl[0],
					'width' => $width,
					'height' => $height,
				];
			}
			// }
		}
		// print_r($this->icons);exit;
		return $this->icons;
	}
	public function getSavedCc() {
		return $this->_helper->getSavedcc();
	}
	public function hasSavedcards() {
		return $this->_helper->hasSavedcardData();
	}
}
