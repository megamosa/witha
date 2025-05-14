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

class Dialog360Service implements ServiceInterface
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
            throw new LocalizedException(__('360Dialog API is not configured.'));
        }
        
        $apiKey = $this->encryptor->decrypt($this->configHelper->getDialog360ApiKey());
        $phoneNumberId = $this->configHelper->getDialog360PhoneNumberId();
        
        // Format phone number (remove non-digit characters)
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        
        $url = "https://waba.360dialog.io/v1/messages";
        
        $payload = [
            'to' => $phoneNumber,
            'type' => 'text',
            'text' => [
                'body' => $message
            ]
        ];
        
        try {
            $this->curl->addHeader('Content-Type', 'application/json');
            $this->curl->addHeader('D360-API-KEY', $apiKey);
            $this->curl->post($url, $this->json->serialize($payload));
            
            $response = $this->json->unserialize($this->curl->getBody());
            
            if (isset($response['errors'])) {
                $this->dataHelper->logError('360Dialog API error: ' . $this->json->serialize($response['errors']));
                return false;
            }
            
            return isset($response['messages']) && !empty($response['messages'][0]['id']);
        } catch (\Exception $e) {
            $this->dataHelper->logError('360Dialog API exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getProviderName(): string
    {
        return '360Dialog';
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        $apiKey = $this->configHelper->getDialog360ApiKey();
        $phoneNumberId = $this->configHelper->getDialog360PhoneNumberId();
        
        return !empty($apiKey) && !empty($phoneNumberId);
    }
}