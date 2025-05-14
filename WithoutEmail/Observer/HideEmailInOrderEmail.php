<?php
namespace MagoArab\WithoutEmail\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use MagoArab\WithoutEmail\Helper\Config;

class HideEmailInOrderEmail implements ObserverInterface
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
     * Modify email template variables to hide email address
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }
        
        $transportObject = $observer->getTransport();
        $order = $transportObject->getOrder();
        
        if ($order) {
            // Get customer email and extract phone number
            $email = $order->getCustomerEmail();
            $phone = '';
            
            // Extract phone number from email if it matches the pattern
            if (preg_match('/^([0-9]+)@/', $email, $matches)) {
                $phone = $matches[1];
                
                // Add phone number as a separate variable for email templates
                $transportObject->setData('customer_phone', $phone);
                
                // Optionally modify email for display in templates
                // This won't change the actual email address used for sending
                $transportObject->setData('customer_email_for_display', '(hidden)');
            }
        }
    }
}