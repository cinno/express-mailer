<?php
namespace Deliveries\Service;

use Deliveries\Aware\Adapter\Storage\DataProviderInterface;

/**
 * StorageService class. Storage Data Service
 *
 * @package Deliveries
 * @subpackage Deliveries\Service
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Service/StorageService.php
 */
class StorageService {

    /**
     * Data provider
     *
     * @var \Deliveries\Aware\Adapter\Storage\DataProviderInterface
     */
    private $storage;

    /**
     * Assign data provider
     *
     * @param \Deliveries\Aware\Adapter\Storage\DataProviderInterface $storage
     */
    public function __construct(DataProviderInterface $storage) {

        $this->storage = $storage;
    }

    /**
     * Get subscribers statistics
     *
     * @return array
     */
    public function getSubscribersReports() {

        return $this->storage->countSubscribers();
    }

    /**
     * Get deliveries statistics
     *
     * @return array
     */
    public function getMailingsReports() {

        return $this->storage->countMailings();
    }

    /**
     * Get active mail statistics
     *
     * @return array
     */
    public function getActiveMailStatistics() {

        return $this->storage->activeMailsStat();
    }

    /**
     * Get queues by date
     *
     * @param string $date
     */
    public function getQueues($date) {

        return $this->storage->getQueues($date);
    }

    /**
     *Execute queries for create table
     *
     * @param string $query
     */
    public function importTables($query) {

        return $this->storage->importTables($query);
    }
}