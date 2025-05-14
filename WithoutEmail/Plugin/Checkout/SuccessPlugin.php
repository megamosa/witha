<?php
namespace MagoArab\WithoutEmail\Plugin\Checkout;

use Magento\Checkout\Block\Onepage\Success;
use Magento\Framework\App\ObjectManager;

class SuccessPlugin
{
    /**
     * Replace email address with phone tracking message
     *
     * @param Success $subject
     * @param string $result
     * @return string
     */
    public function afterGetAdditionalInfoHtml(Success $subject, $result)
{
    try {
        // Load the order using ObjectManager
        $objectManager = ObjectManager::getInstance();
        $configHelper = $objectManager->get(\MagoArab\WithoutEmail\Helper\Config::class);
        
        // If module is not enabled, return original result
        if (!$configHelper->isEnabled()) {
            return $result;
        }
        
        $orderId = $subject->getOrderId();
        if ($orderId) {
            $orderRepository = $objectManager->get(\Magento\Sales\Api\OrderRepositoryInterface::class);
            $order = $orderRepository->get($orderId);
            
            // Get the phone number from the order
            $shippingAddress = $order->getShippingAddress();
            $phoneNumber = $shippingAddress ? $shippingAddress->getTelephone() : '';
            
            if ($phoneNumber) {
                // Create custom HTML with phone number
                $phoneMessage = '<div class="phone-tracking-message">You can track your order using your phone number: <strong>' . $phoneNumber . '</strong></div>';
                
                // Add our message to the result instead of replacing
                if (empty($result)) {
                    $result = $phoneMessage;
                } else {
                    $result .= $phoneMessage;
                }
            }
        }
        
        return $result;
    } catch (\Exception $e) {
        // If anything goes wrong, return the original content
        $objectManager = ObjectManager::getInstance();
        $logger = $objectManager->get(\Psr\Log\LoggerInterface::class);
        $logger->error('WithoutEmail Success Page Error: ' . $e->getMessage());
        return $result;
    }
}
}