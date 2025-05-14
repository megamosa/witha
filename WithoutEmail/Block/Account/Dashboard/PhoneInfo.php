<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Block\Account\Dashboard;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use MagoArab\WithoutEmail\Helper\Config;

class PhoneInfo extends Template
{
    /**
     * @var Session
     */
    protected $customerSession;
    
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $customerSession
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
    }

    /**
     * Get customer phone number
     *
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        $customer = $this->customerSession->getCustomer();
        if (!$customer || !$customer->getId()) {
            return null;
        }
        
        return $customer->getCustomAttribute('phone_number') ?
            $customer->getCustomAttribute('phone_number')->getValue() : null;
    }

    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isModuleEnabled(): bool
    {
        return $this->configHelper->isEnabled();
    }
}