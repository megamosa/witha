<?php
namespace MagoArab\WithoutEmail\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\DateTime;

class MessageLogger
{
    protected $resource;
    protected $connection;
    protected $dateTime;

    public function __construct(
        ResourceConnection $resource,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->dateTime = $dateTime;
    }

    public function logMessage($phoneNumber, $provider, $message, $status, $response = null)
    {
        $table = $this->resource->getTableName('magoarab_whatsapp_messages');
        
        $this->connection->insert($table, [
            'phone_number' => $phoneNumber,
            'provider' => $provider,
            'message' => $message,
            'status' => $status,
            'response' => $response,
            'created_at' => $this->dateTime->gmtDate()
        ]);
    }

    public function getFailureStats($hours = 24)
    {
        $table = $this->resource->getTableName('magoarab_whatsapp_messages');
        $since = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
        
        $select = $this->connection->select()
            ->from($table, [
                'provider',
                'status',
                'count' => 'COUNT(*)'
            ])
            ->where('created_at > ?', $since)
            ->group(['provider', 'status']);
            
        return $this->connection->fetchAll($select);
    }
}