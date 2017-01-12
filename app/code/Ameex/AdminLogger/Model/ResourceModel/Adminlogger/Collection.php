<?php
namespace Ameex\AdminLogger\Model\ResourceModel\Adminlogger;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_idFieldName = 'id';

    protected function _construct()
    {
        $this->_init('Ameex\AdminLogger\Model\Adminlogger', 'Ameex\AdminLogger\Model\ResourceModel\Adminlogger');
        $this->_map['fields']['id'] = 'main_table.id';
    }
    
    public function getAvailableStatuses()
    {
        return [self::STATUS_ENABLED => __('Enabled'), self::STATUS_DISABLED => __('Disabled')];
    }
}