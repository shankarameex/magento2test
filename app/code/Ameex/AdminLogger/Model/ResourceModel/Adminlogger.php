<?php
namespace Ameex\AdminLogger\Model\ResourceModel;

class Adminlogger extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
  
    protected function _construct()
    {
        $this->_init('adminlogger_activities', 'id');
    }
}
