<?php
namespace MagoArab\WithoutEmail\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface;
use MagoArab\WithoutEmail\Helper\WhatsappService;
use MagoArab\WithoutEmail\Helper\Config;
use Psr\Log\LoggerInterface;

class GuestNotificationService
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;
    
    /**
     * @var WhatsappService
     */
    protected $whatsappService;
    
    /**
     * @var Config
     */
    protected $configHelper;
    
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param ResourceConnection $resourceConnection
     * @param OrderRepositoryInterface $orderRepository
     * @param WhatsappService $whatsappService
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        OrderRepositoryInterface $orderRepository,
        WhatsappService $whatsappService,
        Config $configHelper,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
        $this->whatsappService = $whatsappService;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Send notification to guest customer
     *
     * @param string $orderId
     * @param string $status
     * @return bool
     */
    public function sendGuestNotification($orderId, $status)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            
            // Get phone from order addresses
            $phoneNumber = null;
            if ($order->getShippingAddress()) {
                $phoneNumber = $order->getShippingAddress()->getTelephone();
            } elseif ($order->getBillingAddress()) {
                $phoneNumber = $order->getBillingAddress()->getTelephone();
            }
            
            if (!$phoneNumber) {
                $this->logger->error('No phone number found for guest order #' . $order->getIncrementId());
                return false;
            }
            
            // Format phone number
            $phoneNumber = $this->whatsappService->formatPhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                return false;
            }
            
            // Prepare parameters
            $params = $this->prepareNotificationParams($order);
            
            // Send notification
            return $this->whatsappService->sendOrderStatusNotification($phoneNumber, $params, $status);
        } catch (\Exception $e) {
            $this->logger->error('Error sending guest notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prepare notification parameters
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return array
     */
    protected function prepareNotificationParams($order)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $dateTime = $objectManager->get(\Magento\Framework\Stdlib\DateTime\DateTime::class);
        $urlBuilder = $objectManager->get(\Magento\Framework\UrlInterface::class);
        
        $customerName = 'Valued Customer';
        if ($order->getBillingAddress()) {
            $customerName = $order->getBillingAddress()->getFirstname() . ' ' . $order->getBillingAddress()->getLastname();
        }
        
        $params = [
            'order_id' => $order->getIncrementId(),
            'customer_name' => $customerName,
            'order_total' => $order->getOrderCurrency()->formatPrecision(
                $order->getGrandTotal(),
                2,
                [],
                false
            ),
            'order_date' => $dateTime->date('M d, Y', strtotime($order->getCreatedAt())),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'shipping_method' => $order->getShippingDescription(),
            'order_link' => $urlBuilder->getUrl('sales/guest/view', [
                'order_id' => $order->getId(),
                '_nosid' => true
            ])
        ];
        
        // Add tracking number if available
        $tracksCollection = $order->getTracksCollection();
        if ($tracksCollection && $tracksCollection->getSize() > 0) {
            $track = $tracksCollection->getFirstItem();
            $params['tracking_number'] = $track->getTrackNumber();
        }
        
        return $params;
    }
}