<?php
namespace MagoArab\WithoutEmail\Plugin\Checkout\Block;

use Magento\Checkout\Block\Onepage\Success as SuccessBlock;
use MagoArab\WithoutEmail\Helper\Config;

class SuccessPagePlugin
{
    /**
     * @var Config
     */
    protected $configHelper;

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
     * After get order
     *
     * @param SuccessBlock $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetOrder(SuccessBlock $subject, $result)
    {
        if (!$this->configHelper->isEnabled()) {
            return $result;
        }

        if ($result && $result->getShippingAddress()) {
            $phoneNumber = $result->getShippingAddress()->getTelephone();
            if ($phoneNumber) {
                // Override email with phone for display purposes
                $result->setCustomerEmail($phoneNumber);
            }
        }
        
        return $result;
    }
}