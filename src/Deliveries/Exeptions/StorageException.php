<?php
namespace Deliveries\Exceptions;

use Deliveries\Aware\Handlers\BaseException;
use Deliveries\Aware\Helpers\FormatTrait;

/**
 * StorageException class. Storage exception class
 *
 * @package Deliveries
 * @subpackage Deliveries\Exceptions
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Exceptions/StorageException.php
 */
class StorageException extends BaseException {

    use FormatTrait;

    /**
     * Constructor
     *
     * @param string $message
     * @param int $code Status code
     */
    public function __construct($message, $code = self::BASE_TYPE) {

        parent::__construct($message, $code, $this->getExceptionType());
    }

    /**
     * Get current exception name as type
     *
     * @return string
     */
    public function getExceptionType() {

        return $this->getClassName($this);
    }
}