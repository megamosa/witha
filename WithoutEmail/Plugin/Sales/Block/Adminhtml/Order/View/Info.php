<?php
namespace MagoArab\WithoutEmail\Plugin\Sales\Block\Adminhtml\Order\View;

use Magento\Sales\Block\Adminhtml\Order\View\Info as OrderInfo;

class Info
{
    /**
     * Add phone number to account information
     *
     * @param OrderInfo $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(OrderInfo $subject, $result)
    {
        $order = $subject->getOrder();
        $address = $order->getBillingAddress();
        
        if ($address && $address->getTelephone()) {
            $phoneNumber = $address->getTelephone();
            
            // Add phone number after email in account information
            $result = preg_replace(
                '/(<a[^>]*href="mailto:[^"]*"[^>]*>.*?<\/a>)(\s*<\/td>\s*<\/tr>)/s',
                '$1</td></tr><tr><th>' . __('Phone Number') . ':</th><td>' . $phoneNumber . '$2',
                $result
            );
        }
        
        return $result;
    }
}