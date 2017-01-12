<?php
namespace Ameex\Authcim\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface {

	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context) {
		$setup->startSetup();
		$table = $setup->getConnection()
			->newTable($setup->getTable('ameex_authcim'))
			->addColumn('id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, [
				'identity' => true,
				'unsigned' => true,
				'nullable' => false,
				'primary' => true,
			], 'Id')
			->addColumn('ccnum', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 18, [], 'Last4 digit ccnumber')
			->addColumn('cctype', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 18, [], 'cctype')
			->addColumn('is_saved', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 18, [], 'customer accepted to save')
			->addColumn('customer_id', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Customer Id')
			->addColumn('is_primary', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Status Primary card')
			->addColumn('profid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Saved cc token')
			->setComment('Tokenized cc')
			->addColumn('payment_profid', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, 255, [], 'Saved cc token')
			->setComment('Tokenized payment profile id')
			->addColumn('created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, [
				'nullable' => false,
			], 'Created At');
		$setup->getConnection()->createTable($table);

		$setup->endSetup();
	}
}
