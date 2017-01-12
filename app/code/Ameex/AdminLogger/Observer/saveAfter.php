<?php
namespace Ameex\AdminLogger\Observer;
use Magento\Framework\Event\ObserverInterface;
class saveAfter implements ObserverInterface {

	protected $_adminSession;
	protected $_adminloggerFactory;
	protected $_orderCollectionFactory;

	public function __construct(
	    \Magento\Backend\Model\Auth\Session $adminSession,
	    \Magento\Store\Model\Store $store,
	    \Magento\Customer\Model\Customer $customer,
	    \Magento\Catalog\Model\Product $product,
	    \Magento\Cms\Model\Page $page,
	    \Magento\Cms\Model\Block $block,
	    \Magento\Framework\Message\ManagerInterface $messageManager,
	    \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
	    \Ameex\AdminLogger\Model\AdminloggerFactory $adminlogger, 
	    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
	    \Magento\Sales\Model\Order $orderCollectionFactory,
	    \Magento\Framework\App\Config\MutableScopeConfigInterface $config

	) {
	    $this->_adminSession = $adminSession;
	    $this->_store = $store;
	    $this->_customer = $customer;
	    $this->_product = $product;
	    $this->_page = $page;
	    $this->_block = $block;
	    $this->_messageManager = $messageManager;
	    $this->_timezoneInterface = $timezoneInterface;
	    $this->_adminloggerFactory = $adminlogger;
	    $this->_scopeConfig = $scopeConfig;
	    $this->_config  = $config;
	    $this->_orderCollectionFactory = $orderCollectionFactory;

	}

	public function execute(\Magento\Framework\Event\Observer $observer) 
	{


          $request = $observer->getEvent()->getRequest();
		$adminLoggerEnabled = $this->_scopeConfig->getValue('adminlogger/adminlogger_general_config/adminlogger_enabled',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($adminLoggerEnabled)
        {
			$adminLogData = array();
		 	$dateTime = "";
	        
	        $om = \Magento\Framework\App\ObjectManager::getInstance();
	  //       $storeManager = $om->get('Magento\Store\Model\StoreManagerInterface');
			// $storeId = (int) $request->getParam('store',0);
			// $store = $storeManager->getStore($storeId);
			// $storeManager->setCurrentStore($store->getCode());
			$obj = $om->get('Magento\Framework\HTTP\PhpEnvironment\RemoteAddress');
	        $remoteIp =  $obj->getRemoteAddress();
		   	$actionName = $request->getActionName();
	       	$controllerName = $request->getControllerName();
	        $moduleName = $request->getModulename();
	        $customer = $request->getParam('customer');
	       	$items = $request->getParams();
	        $today = $this->_timezoneInterface->date()->format('m/d/y H:i:s');
	        $dateTimeAsTimeZone = $this->_timezoneInterface
	                                        ->date(new \DateTime($dateTime))
	                                        ->format('m/d/y H:i:s');
	        $implodedControllerName = implode(' => ', array_map('ucfirst', explode('_', $controllerName)));
	        if(($controllerName == "index") && ($moduleName == "customer")){
	        	$controllerName = "customer";
	        }
	        $res = $request->getParams();
	        if ($this->_isLogNeeded($actionName, $implodedControllerName,$request)) {
	        	if($controllerName != "bookmark"){
	        	$storeId =  $request->getParam('store');
		        $currentStoreId = ($storeId)?$storeId:0;
			    $storeCollection = $this->_store->load($currentStoreId); 
			    $storeName = $storeCollection->getName() ? $storeCollection->getName() : 'All store views';

		       	$adminLogData['visited_path'] = ucfirst($moduleName) . " => " . ucfirst($controllerName) . " => " . ucfirst($actionName);
				$adminUserDetails = $this->_adminSession->getUser();

				if(!empty($adminUserDetails)){

			    $adminLogData['adminuser_email'] = $adminUserDetails->getEmail();
				}
			    $adminLogData['action_name'] = $actionName;
			    $websiteId = 1;
			    if($controllerName == "system_config"){
			    	$websiteId = $request->getParam('website');
			    }
			    if(!$websiteId){
			    $adminLogData['store_name'] = $currentStoreId. " / " .$storeName;
		        }else{
		        $storeCollection = $this->_store->load($websiteId); 
			    $web = $storeCollection->getWebsite();
		        $adminLogData['store_name'] = $websiteId. " / " . "Website Name : " .$web->getName();
		        }
		        //echo $adminLogData['store_name'];exit;
			    $adminLogData['remote_ip'] = $remoteIp;
			    $adminLogData['additional_info'] = "";
			    $adminLogData['logged_at'] = $dateTimeAsTimeZone;
			    $dynamicValues = $this->_getDynamicValues($controllerName);
			    if (!empty($dynamicValues)) {
			    		switch ($actionName) {
			    			case 'new':
			    				$adminLogData['additional_info'] = "Created a new " . $controllerName;			   
			    				break;
			   		// 		case 'save':
			   		// 		if($controllerName == "category"){
								// $adminLogData['additional_info'] = $dynamicValues[3] . $res['general'][$dynamicValues[4]];
			   		// 		}else{
			   		// 		$adminLogData['additional_info'] = $dynamicValues[3] . $request->getParam($dynamicValues[4]);}
			    	// 			break;	
			    			case 'delete':
			    				$adminLogData['additional_info'] = "Deleted the " . $controllerName . " Successfully : ";
			    				break;
			    			case 'duplicate':
			    				$productId = $request->getParam('id');
			    			    $adminLogData['additional_info'] = "Duplicated the " . $entity." ".$_product['sku'];
			    				break;
			    			case 'massDelete':
			    				$result = implode(",",$res['selected']);
	                            $adminLogData['additional_info'] = "The following ". $controllerName . " Id's was deleted :" . $result;
			    				break;
			    			case 'massSubscribe':
			    				$adminLogData['additional_info'] = "Customer Newletter subscription successfully completed";
			    				break;
			    			case 'massUnsubscribe':
			    				$adminLogData['additional_info'] = "Customer Newletter Unsubscription successfully completed";
			    				break;
			    			case 'massAssignGroup':
			    				$groupid = $res['group'];
	                        	$adminLogData['additional_info'] = "Customer group id ". $groupid . " was assigned to the following customer ids : ";
			    				break;
			    			case 'massDisable':
			    				 $result = implode(",",$res['selected']);
	                        	 $adminLogData['additional_info'] = "The following CMS". $controllerName . " Id's was disabled :" . $result;
			    				break;
			    			case 'massEnable':
			    				$result = implode(",",$res['selected']);
	                       		$adminLogData['additional_info'] = "The following ". $controllerName . " Id's was enabled :" . $result;
			    				break;
			    			case 'massStatus':
			    				$result = implode(",",$res['selected']);
	                        	$adminLogData['additional_info'] = "The following ". $controllerName . " Id status was updated :" . $result;
			    				break;
			    			case 'move':
			    				$result = $res['id'];
	                        	$adminLogData['additional_info'] = "The category id " . $result . " was moved successfully";
			    				break;
			    			case 'view':
			    				$orderId = $res['order_id'];
	                        	$currentOrder =$this->_orderCollectionFactory->load($orderId);
	                        	$orderIncId = $currentOrder->getIncrementId();
	                        	$adminLogData['additional_info'] = "Viewed order id is #" . $orderIncId;
			    				break;
			    			case 'refreshPath':
			    				$adminLogData['additional_info'] = "The category path was refreshed successfully";
			    				break;

			    			
			    			default:
			    				$adminLogData['additional_info'] = str_replace(" => ", " ", $implodedControllerName) . " section was Viewed/Modiefied Successfully";
			    				break;
			    		}
			    		if (($actionName == "save") && ($dynamicValues[1] == "product")) {
			    			$product = $request->getParam('product');
							$adminLogData['additional_info'] = $dynamicValues[3] . $product['sku'];
			    		} elseif (($actionName == "save") && (($dynamicValues[1] == "customer") && ($moduleName == "customer"))) {
						    $customer = $request->getParam('customer');
						$adminLogData['additional_info'] = $dynamicValues[3] . $customer['email'];
			    		} elseif (($actionName == "delete")) {
			    			if($controllerName == "page"){
			    				$pageId = $request->getParam('page_id');
			    				$adminLogData['additional_info'] = "Deleted the CMS Page Id is " . $pageId;
			    			}else{
	                            $id = $res['id'];
	                            $adminLogData['additional_info'] = ($id) ? "Deleted the following " . $controllerName . " id Successfully : ".$id : "Deleted the " . $controllerName . " Successfully : ";
			    		 	}
			    		}elseif($actionName == "inlineEdit"){
			    			$arrayKey = array_keys($items['items']);
			    			if($controllerName == 'page'){
			    				$pageIdentifier = $items['items'][$arrayKey[0]]['identifier'];
								$adminLogData['additional_info'] = "Identifier of the saved CMS Page is :" . $pageIdentifier;
			    			}
			    			if($controllerName == 'block'){
			    				$blockIdentifier = $items['items'][$arrayKey[0]]['identifier'];
								$adminLogData['additional_info'] = "Identifier of the saved CMS Block is : " . $blockIdentifier;
			    			}

			    			if($controllerName == 'customer'){
				    			$cusEmail = $items['items'][$arrayKey[0]]['email'];
				    			if($cusEmail){
				    			$adminLogData['additional_info'] = "Email of the saved customer is : " . $cusEmail;
				    			}else{
				    				$adminLogData['additional_info'] = "Customer Section Modified.";
				    			}
			    			}
			    		}
		    	} else {
		    		if($actionName != "save"){
		    		$adminLogData['additional_info'] = str_replace(" => ", " ", $implodedControllerName) . " section was " .$actionName. "ed";}else{
		    			$adminLogData['additional_info'] = str_replace(" => ", " ", $implodedControllerName) . " section was " .$actionName. "d";
		    		}
		    	}
		    	if(($controllerName == 'cache') && ($actionName == "massRefresh"))
	    			{
						$cacheCollection = implode(', ',$items['types']);
						$adminLogData['additional_info'] = "The following cache sections was cleared :" .$cacheCollection;
	    			}
    			if(($controllerName == 'indexer') && ($actionName == "massOnTheFly"))
    			{
					$indexCollection = implode(', ',$items['indexer_ids']);
					$adminLogData['additional_info'] = "The following index sections was cleared :" .$indexCollection;
    			}
            	if(!empty($adminLogData)){
					$adminLoggerModel = $this->_adminloggerFactory->create();
					$adminLoggerModel->setData($adminLogData)->save();
				}
			}
		    }	
		}
	}

	protected function _isLogNeeded($actionName, $implodedControllerName,$request)
    {
		/*Filter the log actions */
          
       $adminActions = $this->_scopeConfig->getValue('adminlogger/adminlogger_general_config/adminlogger_allowed_actions',\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
       $actionsArray = explode(",",$adminActions);
       $sectionName = $request->getParam('section');
       $result = $this->_addAction($adminActions,$actionName);
       return (!in_array($actionName, $actionsArray) && (($sectionName != 'adminlogger') || ($implodedControllerName != 'adminlogger')) && ($implodedControllerName != 'logs'));
    }

    protected function _addAction($actionArray, $currentAction)
    {
    	$test = array(
            array('value' => $currentAction, 'label' => __("$currentAction")),
        );
    	$result = $actionArray . "," . $currentAction;
    	$this->_config->setValue('adminlogger/adminlogger_general_config/adminlogger_actions',$result,\Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    protected function _getDynamicValues($controllerName)
	{
		switch ($controllerName) {
			case 'customer':
				return array('id', 'customer','name', 'Email of the saved customer is : ', 'firstname');
			case 'product':
				return array('id','product', 'sku', 'SKU of the saved product : ', 'sku');
			case 'page':
				return array('page_id','page', 'title', 'Identifier of the saved CMS Page is : ', 'identifier');
			case 'block':
				return array('block_id','block', 'title','Identifier of the saved CMS Block is : ', 'identifier');
			case 'category':
				return array('entity_id','category', 'name','Name of the saved Category is : ', 'name');
			case 'order':
				return array('order_id','Order', 'id','Id of the saved Order is : ', 'order_id');
			case 'system_config':
				return array('section','config','',	 'Saved System Config section is : ', 'section');
			case 'promo_catalog':
			    return array('rule_id','Catalog Rule','','Saved Catalog Rule Id is : ','rule_id');
			case 'product_attribute':
			    return array('attribute_code','Product Attribute','','Saved product attribute code is : ','attribute_code');
            case 'product_set':
			    return array('attribute_set_name','Product Attribute Set','','Saved product attribute set name is : ','attribute_set_name');
			case 'group':
			    return array('code','Customer Group','','Saved customer group name is : ','code');
			default:
				return '';
		}
	}
}