<?php

namespace MagoArab\WithoutEmail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PhoneFormat implements OptionSourceInterface
{
    const FORMAT_LOCAL = 'local';
    const FORMAT_INTERNATIONAL = 'international';
    const FORMAT_ANY = 'any';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::FORMAT_LOCAL,
                'label' => __('Local Format (digits only)')
            ],
            [
                'value' => self::FORMAT_INTERNATIONAL,
                'label' => __('International Format (with +)')
            ],
            [
                'value' => self::FORMAT_ANY,
                'label' => __('Any Format')
            ]
        ];
    }
}