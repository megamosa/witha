<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Model\Otp\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Encryption\EncryptorInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Helper\Data;
use MagoArab\WithoutEmail\Model\Otp\ServiceInterface;

class UltraMsgService implements ServiceInterface
{
    /**
     * @var Curl
     */
    protected $curl;
    
    /**
     * @var Json
     */
    protected $json;
    
    /**
     * @var Config
     */
    protected $configHelper;
    
    /**
     * @var Data
     */
    protected $dataHelper;
    
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * Constructor
     *
     * @param Curl $curl
     * @param Json $json
     * @param Config $configHelper
     * @param Data $dataHelper
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Curl $curl,
        Json $json,
        Config $configHelper,
        Data $dataHelper,
        EncryptorInterface $encryptor
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->configHelper = $configHelper;
        $this->dataHelper = $dataHelper;
        $this->encryptor = $encryptor;
    }

    /**
     * @inheritDoc
     */
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        if (!$this->isConfigured()) {
            throw new LocalizedException(__('UltraMsg API is not configured.'));
        }
        
        $apiKey = $this->encryptor->decrypt($this->configHelper->getUltraMsgApiKey());
        $instanceId = $this->configHelper->getUltraMsgInstanceId();
        
        // Format phone number (remove non-digit characters)
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        
        $params = [
            'token' => $apiKey,
            'to' => $phoneNumber,
            'body' => $message
        ];
        
        $url = "https://api.ultramsg.com/{$instanceId}/messages/chat";
        
        try {
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->post($url, $this->json->serialize($params));
            
            $response = $this->json->unserialize($this->curl->getBody());
            
            if (isset($response['error'])) {
                $this->dataHelper->logError('UltraMsg API error: ' . $response['error']);
                return false;
            }
            
            return isset($response['sent']) && $response['sent'] === true;
        } catch (\Exception $e) {
            $this->dataHelper->logError('UltraMsg API exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return 'UltraMsg';
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->configHelper->getUltraMsgApiKey();
        $instanceId = $this->configHelper->getUltraMsgInstanceId();
        
        return !empty($apiKey) && !empty($instanceId);
    }
}