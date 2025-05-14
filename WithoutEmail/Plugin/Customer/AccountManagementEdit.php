<?php
namespace MagoArab\WithoutEmail\Plugin\Customer;

use Magento\Customer\Controller\Account\EditPost;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;

class AccountManagementEdit
{
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    
    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManagerInterface $messageManager
     * @param Config $configHelper
     */
    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $messageManager,
        Config $configHelper
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->configHelper = $configHelper;
    }

    /**
     * Add phone number to customer data before saving
     *
     * @param EditPost $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundExecute(EditPost $subject, \Closure $proceed)
    {
        $result = $proceed();
        
        if (!$this->configHelper->isEnabled()) {
            return $result;
        }
        
        $phoneNumber = $this->request->getParam('phone_number');
        
        if ($phoneNumber) {
            try {
                $customerId = $subject->getSession()->getCustomerId();
                if ($customerId) {
                    $customer = $this->customerRepository->getById($customerId);
                    $customer->setCustomAttribute('phone_number', $phoneNumber);
                    $this->customerRepository->save($customer);
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Could not save phone number: %1', $e->getMessage()));
            }
        }
        
        return $result;
    }
}