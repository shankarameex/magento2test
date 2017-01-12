<?php
namespace Ameex\AdminLogger\Cron;
 
class Logs {

    protected $_adminloggerFactory;
    protected $date;
    protected $_timezoneInterface;
    protected $_storeManager;
 
	public function __construct(
        \Ameex\AdminLogger\Model\AdminloggerFactory $db,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
	) {
        $this->_adminloggerFactory = $db;
        $this->date = $date;
        $this->_timezoneInterface = $timezoneInterface;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
	}
 
	public function execute() {

	error_log("***......Your execute!!!!......*****", 3, "/var/www/magento2.1/my-errors.log");

		$isExpire = $this->_scopeConfig->getValue('adminlogger/adminlogger_general_config/adminlogger_cron_days_settings',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $currentDate = (new \DateTime());
        $toDate = $currentDate->format('Y-m-d');
        $adminLoggerModel = $this->_adminloggerFactory->create()->getCollection();
        $logs = $adminLoggerModel->addFieldToSelect('id')
                    ->addFieldToSelect('logged_at');
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
		$this->logger->debug('cron schedule run');
	}
}
