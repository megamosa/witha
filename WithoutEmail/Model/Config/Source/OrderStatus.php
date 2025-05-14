<?php
namespace MagoArab\WithoutEmail\Model\Config\Source;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class OrderStatus implements OptionSourceInterface
{
    protected $statusCollectionFactory;

    public function __construct(
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        
        // Get all order statuses
        $statuses = $this->statusCollectionFactory->create()->toOptionArray();
        
        foreach ($statuses as $status) {
            $options[] = [
                'value' => $status['value'],
                'label' => $status['label']
            ];
        }
        
        // Add custom shipping status if not in the list
        $shippingExists = false;
        foreach ($options as $option) {
            if ($option['value'] === 'shipped') {
                $shippingExists = true;
                break;
            }
        }
        
        if (!$shippingExists) {
            $options[] = [
                'value' => 'shipped',
                'label' => __('Shipped')
            ];
        }
        
        return $options;
    }
}