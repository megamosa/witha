<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class WhatsappProvider implements OptionSourceInterface
{
    const PROVIDER_ULTRAMSG = 'ultramsg';
    const PROVIDER_DIALOG360 = 'dialog360';
    const PROVIDER_WATI = 'wati';
    const PROVIDER_TWILIO = 'twilio';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::PROVIDER_ULTRAMSG,
                'label' => __('UltraMsg')
            ],
            [
                'value' => self::PROVIDER_DIALOG360,
                'label' => __('360Dialog')
            ],
            [
                'value' => self::PROVIDER_WATI,
                'label' => __('WATI')
            ],
            [
                'value' => self::PROVIDER_TWILIO,
                'label' => __('Twilio')
            ]
        ];
    }
}