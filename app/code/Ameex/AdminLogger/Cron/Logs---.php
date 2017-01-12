<?php
namespace Ameex\AdminLogger\Cron;
 
class Logs {
 
    protected $_logger;
 
    public function __construct(\Psr\Log\LoggerInterface $logger) {
        $this->_logger = $logger;
    }
 
    public function execute() {
	error_log("----Your execute!!!!", 3, "/var/www/magento2.1/my-errors.log");
        $this->_logger->info(__METHOD__);
        return $this;
    }
}
