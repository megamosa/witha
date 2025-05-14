<?php
namespace MagoArab\WithoutEmail\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdatePhoneAttributeRequired implements DataPatchInterface
{
    private $moduleDataSetup;
    private $customerSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        
        // جعل phone_number اختياري
        $customerSetup->updateAttribute(
            Customer::ENTITY,
            'phone_number',
            'is_required',
            false
        );
        
        $this->moduleDataSetup->getConnection()->endSetup();
        return $this;
    }

    public static function getDependencies()
    {
        return [AddPhoneAttribute::class];
    }

    public function getAliases()
    {
        return [];
    }
}