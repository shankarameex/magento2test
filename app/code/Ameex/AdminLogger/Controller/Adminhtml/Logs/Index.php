<?php
 
namespace Ameex\AdminLogger\Controller\Adminhtml\Logs;
 
use Ameex\AdminLogger\Controller\Adminhtml\Logs;
 
class Index extends Logs
{
    /**
     * @return void
     */
   public function execute()
   {
      if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
	
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Ameex_AdminLogger::main_menu');
        $resultPage->getConfig()->getTitle()->prepend(__('Admin Logs'));
 	//echo 'Check index action'; exit;
        
        return $resultPage;
   }
}
