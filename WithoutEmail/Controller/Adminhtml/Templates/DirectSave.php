<?php
namespace MagoArab\WithoutEmail\Controller\Adminhtml\Templates;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Cache\TypeListInterface;

class DirectSave extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    
    /**
     * @var Json
     */
    protected $json;
    
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ResourceConnection $resourceConnection
     * @param Json $json
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection,
        Json $json,
        TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
        $this->json = $json;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Save templates directly to database
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        try {
            // Get templates from request
            $templatesJson = $this->getRequest()->getParam('templates');
            $templates = $this->json->unserialize($templatesJson);
            
            if (!is_array($templates)) {
                throw new \Exception('Invalid templates data');
            }
            
            // Get connection
            $connection = $this->resourceConnection->getConnection();
            $tableName = $this->resourceConnection->getTableName('core_config_data');
            
            // Save each template to database
            foreach ($templates as $status => $template) {
                $path = "magoarab_withoutemail/notifications/template_{$status}";
                
                // Check if config exists
                $select = $connection->select()
                    ->from($tableName)
                    ->where('path = ?', $path)
                    ->where('scope = ?', 'default')
                    ->where('scope_id = ?', 0);
                
                $row = $connection->fetchRow($select);
                
                if ($row) {
                    // Update existing
                    $connection->update(
                        $tableName,
                        ['value' => $template],
                        [
                            'path = ?' => $path,
                            'scope = ?' => 'default',
                            'scope_id = ?' => 0
                        ]
                    );
                } else {
                    // Insert new
                    $connection->insert(
                        $tableName,
                        [
                            'path' => $path,
                            'value' => $template,
                            'scope' => 'default',
                            'scope_id' => 0
                        ]
                    );
                }
            }
            
            // Clear config cache
            $this->cacheTypeList->cleanType('config');
            
            return $result->setData([
                'success' => true,
                'message' => __('Templates saved successfully')
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}