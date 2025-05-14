<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Block\Form;

use Magento\Customer\Block\Form\Register as MagentoRegister;
use MagoArab\WithoutEmail\Helper\Config;

class Register extends MagentoRegister
{
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\App\Cache\Type\Config $configCacheType
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory
     * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\App\Cache\Type\Config $configCacheType,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $directoryHelper,
            $jsonEncoder,
            $configCacheType,
            $regionCollectionFactory,
            $countryCollectionFactory,
            $moduleManager,
            $customerSession,
            $customerUrl,
            $data
        );
        $this->configHelper = $configHelper;
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