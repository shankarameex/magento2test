<?php
namespace Ameex\AdminLogger\Controller\Index;
 
class Index extends \Magento\Framework\App\Action\Action {

    protected $resultPageFactory;
    protected $_adminloggerFactory;
    protected $date;
    protected $_timezoneInterface;
    protected $_storeManager;

    /**
     * Constructor
     * 
     * @param \Magento\Framework\App\Action\Context  $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Ameex\AdminLogger\Model\AdminloggerFactory $db,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->_adminloggerFactory = $db;
        $this->date = $date;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Execute view action
     * 
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        $isExpire = $this->_scopeConfig->getValue('adminlogger/adminlogger_general_config/adminlogger_cron_days_settings',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $currentDate = (new \DateTime());
        $toDate = $currentDate->format('Y-m-d');
        $adminLoggerModel = $this->_adminloggerFactory->create()->getCollection();
        $logs = $adminLoggerModel->addFieldToSelect('id')
                    ->addFieldToSelect('logged_at')
                    ->addFieldToSelect('additional_info');
        foreach ($logs as $log) {
            $fromDate = $this->_timezoneInterface->convertConfigTimeToUtc($log->getLoggedAt());
            $fromDate = (new \DateTime($fromDate));
            $fromDate = $fromDate->format('Y-m-d');
            $diff=date_diff(date_create($fromDate),date_create($toDate));
            $daysExpire = $diff->d;
            if($daysExpire >= $isExpire) {
                $log->delete();
                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/Adminlogger.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info(print_r($log->getData(), true));
            }
        }
    }
}