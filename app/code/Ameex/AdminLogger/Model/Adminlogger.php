<?php
namespace Ameex\AdminLogger\Model;

class Adminlogger extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Ameex\AdminLogger\Model\ResourceModel\Adminlogger');
    }
}