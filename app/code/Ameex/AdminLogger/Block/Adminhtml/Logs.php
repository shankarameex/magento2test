<?php
 
namespace Ameex\AdminLogger\Block\Adminhtml;
 
use Magento\Backend\Block\Widget\Grid\Container;
 
class Logs extends Container
{
   /**
     * Constructor
     *
     * @return void
     */
   protected function _construct()
    {
        $this->_controller = 'adminhtml_logs';
        $this->_blockGroup = 'Ameex_AdminLogger';
        $this->_headerText = __('Manage Logs');
        parent::_construct();
    }
}