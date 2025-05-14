<?php
namespace MagoArab\WithoutEmail\Plugin\Sales\Order\Grid;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;

class Collection
{
    /**
     * Add phone number to select query
     *
     * @param OrderGridCollection $subject
     * @param bool $printQuery
     * @param bool $logQuery
     * @return array
     */
    public function beforeLoad(OrderGridCollection $subject, $printQuery = false, $logQuery = false)
    {
        if ($subject->isLoaded()) {
            return [$printQuery, $logQuery];
        }

        $subject->getSelect()->joinLeft(
            ['sales_order_address' => $subject->getTable('sales_order_address')],
            'main_table.entity_id = sales_order_address.parent_id AND sales_order_address.address_type = "billing"',
            ['telephone' => 'sales_order_address.telephone']
        );

        return [$printQuery, $logQuery];
    }

    /**
     * Add phone number to search fields
     *
     * @param OrderGridCollection $subject
     * @param \Closure $proceed
     * @param string|array $field
     * @param null $condition
     * @return OrderGridCollection
     */
    public function aroundAddFieldToFilter(
        OrderGridCollection $subject,
        \Closure $proceed,
        $field,
        $condition = null
    ) {
        if (is_array($field)) {
            foreach ($field as $key => $value) {
                if ($value == 'telephone') {
                    $field[$key] = 'sales_order_address.telephone';
                }
            }
        } elseif ($field == 'telephone') {
            $field = 'sales_order_address.telephone';
        }

        return $proceed($field, $condition);
    }
}