<?php
namespace MagoArab\WithoutEmail\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use MagoArab\WithoutEmail\Helper\Config;

class PhoneConfig implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $configHelper;

    /**
     * Constructor
     *
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }

    /**
     * Check if OTP is enabled
     *
     * @return bool
     */
    public function isOtpEnabled(): bool
    {
        return $this->configHelper->isOtpEnabled();
    }

    /**
     * Get minimum phone length
     *
     * @return int
     */
    public function getMinPhoneLength(): int
    {
        return $this->configHelper->getMinPhoneLength();
    }

    /**
     * Get maximum phone length
     *
     * @return int
     */
    public function getMaxPhoneLength(): int
    {
        return $this->configHelper->getMaxPhoneLength();
    }

    /**
     * Get phone format
     *
     * @return string
     */
    public function getPhoneFormat(): string
    {
        return $this->configHelper->getPhoneFormat();
    }
}