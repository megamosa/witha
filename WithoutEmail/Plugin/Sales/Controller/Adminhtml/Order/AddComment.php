<?php
namespace MagoArab\WithoutEmail\Plugin\Sales\Controller\Adminhtml\Order;

use Magento\Sales\Controller\Adminhtml\Order\AddComment as AddCommentController;
use Magento\Framework\App\RequestInterface;

class AddComment
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request
    ) {
        $this->request = $request;
    }

    /**
     * Before execute plugin
     */
    public function beforeExecute(AddCommentController $subject)
    {
        $data = $this->request->getPostValue('history');
        
        if (isset($data['is_customer_notified_by_whatsapp'])) {
            // Handle WhatsApp notification
            $orderId = $this->request->getParam('order_id');
            // TODO: Send WhatsApp notification logic
            
            // Log for testing
            error_log('WhatsApp notification requested for order: ' . $orderId);
        }
        
        return [];
    }
}