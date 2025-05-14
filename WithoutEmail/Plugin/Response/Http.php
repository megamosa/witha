<?php
namespace MagoArab\WithoutEmail\Plugin\Response;

use Magento\Framework\App\Response\Http as MagentoHttp;

class Http
{
    public function beforeSendResponse(MagentoHttp $subject)
    {
        $subject->setHeader('Access-Control-Allow-Origin', '*');
        $subject->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $subject->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        return null;
    }
}