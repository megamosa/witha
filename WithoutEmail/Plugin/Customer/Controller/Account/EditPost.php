<?php
namespace MagoArab\WithoutEmail\Plugin\Customer\Controller\Account;

use Magento\Customer\Controller\Account\EditPost as EditPostController;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use MagoArab\WithoutEmail\Helper\Config;

class EditPost
{
    protected $request;
    protected $customerRepository;
    protected $customerSession;
    protected $configHelper;

    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        Config $configHelper
    ) {
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
    }

    public function beforeExecute(EditPostController $subject)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }

        $phoneNumber = $this->request->getParam('phone_number');
        $changePhone = $this->request->getParam('change_phone');
        
        if ($phoneNumber && $changePhone) {
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                try {
                    $customer = $this->customerRepository->getById($customerId);
                    $customer->setCustomAttribute('phone_number', $phoneNumber);
                    $this->customerRepository->save($customer);
                } catch (\Exception $e) {
                    // Handle error
                }
            }
        }
    }
}