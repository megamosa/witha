<?php
namespace MagoArab\WithoutEmail\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Customer\Model\Session;
use MagoArab\WithoutEmail\Helper\Config;

class AddPhoneFieldToCustomerForm implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $customerSession;
    
    /**
     * @var Config
     */
    protected $configHelper;

    /**
     * Constructor
     *
     * @param Session $customerSession
     * @param Config $configHelper
     */
    public function __construct(
        Session $customerSession,
        Config $configHelper
    ) {
        $this->customerSession = $customerSession;
        $this->configHelper = $configHelper;
    }

    /**
     * Add phone field to customer form
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }

        $block = $observer->getEvent()->getBlock();
        
        // تأكد من أن البلوك هو صفحة تحرير حساب العميل
        if ($block instanceof \Magento\Customer\Block\Form\Edit) {
            // إضافة سكريبت JavaScript في نهاية البلوك
            $transport = $observer->getEvent()->getTransport();
            if ($transport && $transport->getHtml() !== null) {
                $html = $transport->getHtml();
                $script = $this->getPhoneFieldScript();
                
                // تأكد من أن HTML هو نص وليس null
                if (is_string($html) && strpos($html, 'phone_number') === false) {
                    // إضافة السكريبت قبل نهاية form
                    $html = str_replace('</form>', $script . '</form>', $html);
                    $transport->setHtml($html);
                }
            }
        }
    }

    /**
     * Get phone field script
     *
     * @return string
     */
    protected function getPhoneFieldScript()
    {
        $customer = $this->customerSession->getCustomer();
        $phoneNumber = '';
        if ($customer && $customer->getId()) {
            $phoneAttr = $customer->getCustomAttribute('phone_number');
            if ($phoneAttr) {
                $phoneNumber = $phoneAttr->getValue();
            }
        }
        
        $minLength = $this->configHelper->getMinPhoneLength();
        $maxLength = $this->configHelper->getMaxPhoneLength();
        
        return "
        <script>
        require(['jquery', 'domReady!'], function($) {
            // إضافة حقل الهاتف بعد الاسم الأول مباشرة
            var firstNameField = $('.fieldset.info .field.firstname');
            if (firstNameField.length) {
                var phoneField = $('<div class=\"field phone required phone-field-highlight\">' +
                    '<label class=\"label\" for=\"phone_number\"><span>" . __('Phone Number') . "</span></label>' +
                    '<div class=\"control\">' +
                    '<input type=\"tel\" name=\"phone_number\" id=\"phone_number\" value=\"" . $phoneNumber . "\" ' +
                    'title=\"" . __('Phone Number') . "\" ' +
                    'class=\"input-text\" ' +
                    'data-validate=\"{required:true, \'validate-number\':true, minlength:" . $minLength . ", maxlength:" . $maxLength . "}\">' +
                    '</div>' +
                    '</div>');
                
                phoneField.insertBefore(firstNameField);
            }
        });
        </script>";
    }
}