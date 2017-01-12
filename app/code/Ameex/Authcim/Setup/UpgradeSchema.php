<?php
namespace Ameex\Authcim\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
class UpgradeSchema implements  UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,
        ModuleContextInterface $context){
            $setup->startSetup();
           // $table = $setup->getConnection();
            $tableName = $setup->getTable('sales_order_payment');
            if ($setup->getConnection()->isTableExists($tableName) == true) {
            $setup->getConnection()->addColumn(
                $tableName,
                'auth_code',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,                    
                    'nullable' => false,
                    'default' => '',
                    'comment' => 'Authorizenet authcode'
                ]
            );
                // Changes here.
            }            
            
              
            $setup->endSetup();
    }
}