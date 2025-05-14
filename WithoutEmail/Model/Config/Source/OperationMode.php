<?php
namespace MagoArab\WithoutEmail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OperationMode implements OptionSourceInterface
{
    const MODE_PHONE_ONLY = 'phone_only';
    const MODE_HYBRID = 'hybrid';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::MODE_PHONE_ONLY,
                'label' => __('Phone Only')
            ],
            [
                'value' => self::MODE_HYBRID,
                'label' => __('Hybrid Mode')
            ]
        ];
    }
}