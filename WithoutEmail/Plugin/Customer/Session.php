<?php
/**
 * MagoArab_WithoutEmail extension
 *
 * @category  MagoArab
 * @package   MagoArab_WithoutEmail
 * @author    MagoArab
 */
declare(strict_types=1);

namespace MagoArab\WithoutEmail\Plugin\Customer;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Model\PhoneNumber;

class Session
{
    /**
     * @var RequestInterface
     */
    protected $request;
    
    /**
     * @var Config
     */
    protected $configHelper;
    
    /**
     * @var PhoneNumber
     */
    protected $phoneNumber;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param Config $configHelper
     * @param PhoneNumber $phoneNumber
     */
    public function __construct(
        RequestInterface $request,
        Config $configHelper,
        PhoneNumber $phoneNumber
    ) {
        $this->request = $request;
        $this->configHelper = $configHelper;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Before login by username and password
     *
     * @param CustomerSession $subject
     * @param string $username
     * @param string $password
     * @return array
     */
public function beforeLogin(
    CustomerSession $subject,
    $username,
    $password
) {
    if (!$this->configHelper->isEnabled()) {
        return [$username, $password];
    }
    
    // Check if username is a phone number
    if (is_numeric($username) || (substr($username, 0, 1) === '+' && is_numeric(substr($username, 1)))) {
        // Generate email from phone number
        $domain = $this->getDomainFromRequest();
        
        // Clean the phone number
        $phoneNumber = preg_replace('/\D/', '', $username);
        
        $email = $this->phoneNumber->generateEmailFromPhone($phoneNumber, $domain);
        
        return [$email, $password];
    }
    
    // In hybrid mode, allow email login
    if ($this->configHelper->isHybridMode()) {
        // If username contains @ symbol, it's probably an email - let it pass through
        if (strpos($username, '@') !== false) {
            return [$username, $password];
        }
    }
    
    return [$username, $password];
}

    /**
     * Get domain from current URL
     *
     * @return string
     */
    protected function getDomainFromRequest(): string
    {
        $host = $this->request->getHttpHost();
        return $host ?: 'example.com';
    }
}