<?php
namespace MagoArab\WithoutEmail\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use MagoArab\WithoutEmail\Block\Adminhtml\System\Config\Templates as ConfigTemplates;

class Templates extends Template
{
    /**
     * @var ConfigTemplates
     */
    protected $configTemplates;

    /**
     * Constructor
     *
     * @param Context $context
     * @param ConfigTemplates $configTemplates
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigTemplates $configTemplates,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->configTemplates = $configTemplates;
    }

    /**
     * Get available placeholders
     *
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->configTemplates->getPlaceholders();
    }
    
    /**
     * Get default templates
     *
     * @return array
     */
    public function getDefaultTemplates()
    {
        return $this->configTemplates->getDefaultTemplates();
    }
    
    /**
     * Get template value
     *
     * @param string $status
     * @return string
     */
    public function getTemplateValue($status)
    {
        return $this->configTemplates->getTemplateValue($status);
    }
    
    /**
     * Get form key
     *
     * @return string
     */
    public function getFormKey()
    {
        return $this->configTemplates->getFormKey();
    }
    
    /**
     * Get form action
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('magoarab_withoutemail/templates/save');
    }
}