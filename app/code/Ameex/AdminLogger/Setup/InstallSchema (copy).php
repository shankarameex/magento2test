<?php
namespace Ameex\AdminLogger\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class InstallSchema implements InstallSchemaInterface
{
	public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();

		$table = $setup->getConnection()->newTable(
		$setup->getTable('adminlogger_activities'))->addColumn(
		'id',
		\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
		null,
		['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
		'Custom Id'
		)->addColumn(
		'admin_userid',
		\Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
		11,
		[],
		'Admin Users Id')->addColumn(
		'logged_at',
		\Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
		null,
		['nullable' => false],
		'Logged At')->addColumn(
		'adminuser_email',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Admin User Email')->addColumn(
		'visited_path',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Admin User Visited Path')->addColumn(
		'controller_name',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Controller Name')->addColumn(
		'action_name',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Action Name')->addColumn(
		'store_name',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Store Name')->addColumn(
		'remote_ip',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		['nullable' => false,'default' => '0'],
		'Remote User IP')->addColumn(
		'additional_info',
		\Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
		255,
		[],
		'Additional Details')->setComment('Ameex AdminLogger Activities Table');

		$setup->getConnection()->createTable($table);
		$setup->endSetup();
	}
}