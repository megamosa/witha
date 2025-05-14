<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;

class CustomerRegister implements ObserverInterface
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * @var SessionManagerInterface
     */
    protected $session;
    
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param CustomerRepositoryInterface $customerRepository
     * @param SessionManagerInterface $session
     * @param Config $configHelper
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        SessionManagerInterface $session,
        Config $configHelper
    ) {
        $this->customerRepository = $customerRepository;
        $this->session = $session;
        $this->configHelper = $configHelper;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
public function execute(Observer $observer)
{
    if (!$this->configHelper->isEnabled()) {
        return;
    }
    $customer = $observer->getEvent()->getCustomer();
    if (!$customer || !$customer->getId()) {
        return;
    }
    
    try {
        $customerData = $this->customerRepository->getById($customer->getId());
        $phoneNumber = $customerData->getCustomAttribute('phone_number');
        
        if (!$phoneNumber) {
            // Try to get phone number from request or session
            $event = $observer->getEvent();
            $request = $event->getRequest();
            
            // Check that the request object exists before using it
            if ($request) {
                $phoneNumber = $request->getParam('phone_number');
            } else {
               // Try to get the phone number from the session if it exists there
                $phoneNumber = $this->session->getData('customer_phone_number');
            }
            
            if (!$phoneNumber) {
                return;
            }
            
            // Validate OTP if needed
            $otpData = $this->session->getData('otp_' . $phoneNumber);
            if (!$otpData || !isset($otpData['verified']) || !$otpData['verified']) {
                throw new LocalizedException(__('Phone number has not been verified with OTP.'));
            }
            
            // Set phone number attribute
            $customerData->setCustomAttribute('phone_number', $phoneNumber);
            $this->customerRepository->save($customerData);
        }
    } catch (NoSuchEntityException $e) {
        // Customer not found, ignore
    }
}
}