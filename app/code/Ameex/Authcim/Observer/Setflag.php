<?php
namespace Ameex\Authcim\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Customer login observer
 */
class Setflag implements ObserverInterface
{
    public function __construct(
       \Magento\Framework\App\Request\Http $request
        )
    {
        $this->_request = $request;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order=$observer->getEvent()->getOrder();
       // echo "<pre/>";
        //print_r($order->getData());die();
        //echo "Hello world";exit;
    }    


        //exit;
    }