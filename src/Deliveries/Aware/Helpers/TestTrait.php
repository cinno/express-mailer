<?php
namespace Deliveries\Aware\Helpers;

/**
 * TestTrait trait.
 *
 * @package Deliveries
 * @subpackage Deliveries\Aware\Helpers
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Aware/Helpers/TestTrait.php
 */
trait TestTrait {

    /**
     * Mail Adapter instance
     *
     * @var object $mailInstance
     */
    private $mailInstance;

    /**
     * Queue Adapter instance
     *
     * @var object $queueInstance
     */
    private $queueInstance;

    /**
     * Storage Adapter instance
     *
     * @var object $storageInstance
     */
    private $storageInstance;

    /**
     * Get mail instance object
     *
     * @param array $config
     * @return \Deliveries\Aware\Adapter\Mail\MailProviderInterface
     */
    private function getMailInstance($config = null) {

        return (null === $this->mailInstance) ?
            $this->isMailConnectSuccess($config)
            : $this->mailInstance;
    }

    /**
     * Get storage instance object
     *
     * @param array $config
     * @return \Deliveries\Aware\Adapter\Storage\DataProviderInterface
     */
    private function getStorageInstance($config = null) {
        return (null === $this->storageInstance) ?
            $this->isStorageConnectSuccess($config)->setTables($config['prefix'])
            : $this->storageInstance;
    }

    /**
     * Get queue instance object
     *
     * @param array $config
     * @return \Deliveries\Aware\Adapter\Broker\QueueProviderInterface
     */
    private function getQueueInstance($config = null) {
        return (null === $this->queueInstance) ?
            $this->isQueueConnectSuccess($config)
            : $this->queueInstance;
    }

    /**
     * Testing for connect to Mail Server
     *
     * @throws \RuntimeException
     * @return boolean
     */
    public function isMailConnectSuccess(array $config) {

        if(empty($config) === false) {

            $Mail = "\\Deliveries\\Adapter\\Mail\\".$config["adapter"];

            if(true === class_exists($Mail)) {

                $this->mailInstance = (new $Mail())->connect($config);

                if($this->mailInstance === false) {
                    throw new \RuntimeException('Connection to mail server: '.$config["adapter"].' is not allow. Check configurations');
                }
                return $this->mailInstance;
            }
            throw new \RuntimeException($config["adapter"]. ' mail adapter is not exist');
        }
        throw new \RuntimeException('Mail config is not exist');
    }

    /**
     * Testing for connect to Queue
     *
     * @throws \RuntimeException
     * @return boolean
     */
    public function isQueueConnectSuccess(array $config) {

        if(empty($config) === false) {

            $Broker = "\\Deliveries\\Adapter\\Broker\\".$config["adapter"];

            if(true === class_exists($Broker)) {

                $this->queueInstance = (new $Broker())->connect($config);

                if($this->queueInstance === false) {
                    throw new \RuntimeException('Connection to AMQP server: '.$config["adapter"].' is not allow. Check configurations');
                }
                return $this->queueInstance;
            }
            throw new \RuntimeException($config["adapter"]. ' broker adapter is not exist');
        }
        throw new \RuntimeException('Broker config is not exist');
    }

    /**
     * Testing for connect to DB Storage
     *
     * @param array $config
     * @throws \RuntimeException
     * @return boolean
     */
    public function isStorageConnectSuccess(array $config) {

        $Storage = "\\Deliveries\\Adapter\\Storage\\".$config["adapter"];

        if(true === class_exists($Storage)) {

            $this->storageInstance = new $Storage();

            if($this->storageInstance->isSupport()) {
                return $this->storageInstance->connect($config);
            }
            throw new \RuntimeException($config["adapter"]. ' is not supported');
        }
        throw new \RuntimeException($config["adapter"]. ' is not exist');
    }

}