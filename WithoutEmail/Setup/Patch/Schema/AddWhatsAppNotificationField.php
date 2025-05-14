<?php
namespace MagoArab\WithoutEmail\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class AddWhatsAppNotificationField implements SchemaPatchInterface
{
    /**
     * @var SchemaSetupInterface
     */
    private $schemaSetup;

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup
    ) {
        $this->schemaSetup = $schemaSetup;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->schemaSetup->startSetup();
        
        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable('sales_order_status_history');
        
        if (!$connection->tableColumnExists($tableName, 'is_customer_notified_by_whatsapp')) {
            $connection->addColumn(
                $tableName,
                'is_customer_notified_by_whatsapp',
                [
                    'type' => Table::TYPE_SMALLINT,
                    'unsigned' => true,
                    'nullable' => false,
                    'default' => '0',
                    'comment' => 'Is Customer Notified By WhatsApp'
                ]
            );
        }
        
        $this->schemaSetup->endSetup();
        
        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}