<?php
 
namespace Ameex\AdminLogger\Controller\Adminhtml\Logs;
 
use Ameex\AdminLogger\Controller\Adminhtml\Logs;
 
class Grid extends Logs
{
   /**
     * @return void
     */
   public function execute()
   {
      return $this->_resultPageFactory->create();
   }
}