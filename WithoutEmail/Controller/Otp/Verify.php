<?php
namespace MagoArab\WithoutEmail\Controller\Otp;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Model\OtpRateLimit;
use Psr\Log\LoggerInterface;

class Verify implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private RequestInterface $request;
    private JsonFactory $resultJsonFactory;
    private CustomerSession $customerSession;
    private Config $configHelper;
    private OtpRateLimit $otpRateLimit;
    private LoggerInterface $logger;
    private SessionManagerInterface $sessionManager;
	
    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        SessionManagerInterface $sessionManager,
        Config $configHelper,
        OtpRateLimit $otpRateLimit,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
        $this->configHelper = $configHelper;
        $this->otpRateLimit = $otpRateLimit;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $phoneNumber = $this->request->getParam('phone_number');
            $otpCode = $this->request->getParam('otp_code');
            
            if (empty($phoneNumber) || empty($otpCode)) {
                throw new \Exception('Phone number and OTP code are required.');
            }
            
            // Registrar información para depuración - IMPORTANTE
            $this->logger->info('OTP Verify Request', [
                'phone_number' => $phoneNumber,
                'otp_code' => $otpCode,
                'session_id' => $this->sessionManager->getSessionId(),
                'customer_logged_in' => $this->customerSession->isLoggedIn(),
                'session_data' => [
                    'specific_otp' => $this->sessionManager->getData('otp_' . $phoneNumber),
                    'general_otp_code' => $this->sessionManager->getData('otp_code'),
                    'general_otp_phone' => $this->sessionManager->getData('otp_phone'),
                    'last_otp_code' => $this->sessionManager->getData('last_otp_code'),
                    'last_otp_phone' => $this->sessionManager->getData('last_otp_phone')
                ]
            ]);
            
            // Enfoque simplificado: intentar todos los métodos de almacenamiento
            $otpFromSpecific = $this->sessionManager->getData('otp_' . $phoneNumber);
            $otpFromCustomer = $this->customerSession->getData('otp_' . $phoneNumber);
            $otpCode_general = $this->sessionManager->getData('otp_code');
            $lastOtpCode = $this->sessionManager->getData('last_otp_code');

            // Super método directo: aceptar cualquier OTP válido de cualquier fuente
            $isValidOTP = false;
            
            // Verificar OTP específico para el teléfono
            if ($otpFromSpecific && isset($otpFromSpecific['code'])) {
                if ($otpFromSpecific['code'] === $otpCode) {
                    $isValidOTP = true;
                    // Marcar como verificado
                    $otpFromSpecific['verified'] = true;
                    $this->sessionManager->setData('otp_' . $phoneNumber, $otpFromSpecific);
                }
            }
            
            // Verificar OTP de la sesión del cliente
            if (!$isValidOTP && $otpFromCustomer && isset($otpFromCustomer['code'])) {
                if ($otpFromCustomer['code'] === $otpCode) {
                    $isValidOTP = true;
                    // Marcar como verificado
                    $otpFromCustomer['verified'] = true;
                    $this->customerSession->setData('otp_' . $phoneNumber, $otpFromCustomer);
                }
            }
            
            // Verificar OTP general de la sesión
            if (!$isValidOTP && $otpCode_general === $otpCode) {
                $isValidOTP = true;
                $this->sessionManager->setData('otp_verified', true);
            }
            
            // Verificar último OTP guardado
            if (!$isValidOTP && $lastOtpCode === $otpCode) {
                $isValidOTP = true;
                $this->sessionManager->setData('otp_verified', true);
            }
            
            // Si no hay coincidencia en ningún lugar
            if (!$isValidOTP) {
                // Registrar el fallo
                $this->logger->error('OTP Validation Failed', [
                    'provided_code' => $otpCode,
                    'stored_codes' => [
                        'specific' => $otpFromSpecific['code'] ?? 'not set',
                        'customer' => $otpFromCustomer['code'] ?? 'not set',
                        'general' => $otpCode_general ?? 'not set',
                        'last' => $lastOtpCode ?? 'not set'
                    ]
                ]);
                
                throw new \Exception('Invalid OTP code. Please try again.');
            }
            
            // Si llegamos aquí, el OTP es válido
            $this->sessionManager->setData('otp_verified', true);
            
            return $resultJson->setData([
                'success' => true,
                'message' => __('OTP verified successfully.')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('OTP Verify Error: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}