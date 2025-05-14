<?php
namespace MagoArab\WithoutEmail\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Templates extends Field
{
    protected $_template = 'MagoArab_WithoutEmail::system/config/templates.phtml';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the HTML
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
    
    /**
     * Get available placeholders
     */
    public function getPlaceholders()
    {
        return [
            '{{order_id}}' => __('Order ID'),
            '{{customer_name}}' => __('Customer Name'),
            '{{order_total}}' => __('Order Total'),
            '{{tracking_number}}' => __('Tracking Number'),
            '{{business_name}}' => __('Business Name'),
            '{{support_phone}}' => __('Support Phone'),
            '{{order_date}}' => __('Order Date'),
            '{{delivery_date}}' => __('Estimated Delivery Date'),
            '{{payment_method}}' => __('Payment Method'),
            '{{shipping_method}}' => __('Shipping Method'),
            '{{order_status}}' => __('Order Status'),
            '{{order_link}}' => __('Order Link')
        ];
    }
    
    /**
     * Get default templates
     */
    public function getDefaultTemplates()
    {
        return [
                'pending' => 'Hello {{customer_name}}, your order #{{order_id}} has been received. Thank you for shopping with {{business_name}}!',
                'processing' => 'Hello {{customer_name}}, your order #{{order_id}} is now being processed. We will notify you once it ships.',
                'complete' => 'Hello {{customer_name}}, your order #{{order_id}} has been completed. Thank you for shopping with {{business_name}}!',
                'canceled' => 'Hello {{customer_name}}, your order #{{order_id}} has been canceled. If you have any questions, please contact us at {{support_phone}}.',
                'holded' => 'Hello {{customer_name}}, your order #{{order_id}} is currently on hold. Our team will contact you soon.',
                'shipped' => 'Hello {{customer_name}}, your order #{{order_id}} has been shipped! Tracking number: {{tracking_number}}',
                'refunded' => 'Hello {{customer_name}}, your refund for order #{{order_id}} has been processed. The amount will be credited to your account within 5-7 business days.'
        ];
    }

    /**
     * Get template value
     */
    public function getTemplateValue($status)
    {
        $value = $this->_scopeConfig->getValue(
            "magoarab_withoutemail/notifications/template_{$status}",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        
        if (!$value) {
            $defaultTemplates = $this->getDefaultTemplates();
            return $defaultTemplates[$status] ?? '';
        }
        
        return $value;
    }
}