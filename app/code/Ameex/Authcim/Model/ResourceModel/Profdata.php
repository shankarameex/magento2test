<?php
namespace Ameex\Authcim\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Profdata extends AbstractDb {

	/**
	 * Define main table
	 */
	protected function _construct() {
		$this->_init('ameex_authcim', 'id');
	}
}
