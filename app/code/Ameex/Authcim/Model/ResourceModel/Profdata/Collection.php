<?php
namespace Ameex\Authcim\Model\ResourceModel\Profdata;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection {

	/**
	 * Define model & resource model
	 */
	protected function _construct() {
		$this->_init('Ameex\Authcim\Model\Profdata', 'Ameex\Authcim\Model\ResourceModel\Profdata');
	}
}
