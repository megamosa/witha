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
use MagoArab\WithoutEmail\Helper\Config;

class RemoveEmailField implements ObserverInterface
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
     * Remove email field from form
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
        
        // Check if the block is a form that contains email fields
        if ($block instanceof \Magento\Customer\Block\Form\Register
            || $block instanceof \Magento\Customer\Block\Form\Login
            || $block instanceof \Magento\Customer\Block\Form\Edit
        ) {
            $layout = $block->getLayout();
            
            // Find the email field in the form
            $emailField = $layout->getChildName($block->getNameInLayout(), 'email_address');
            
            if ($emailField) {
                // Remove the email field
                $layout->unsetChild($block->getNameInLayout(), $emailField);
            }
        }
    }
}