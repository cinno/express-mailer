<?php
namespace Deliveries\Aware\Handlers;

/**
 * BaseException class. Base exception handler
 *
 * @package Deliveries
 * @subpackage Deliveries\Aware\Handlers
 * @since PHP >=5.5
 * @version 1.0
 * @author Stanislav WEB | Lugansk <stanisov@gmail.com>
 * @copyright Stanislav WEB
 * @filesource /Deliveries/Aware/Handlers/BaseException.php
 */
class BaseException extends \RuntimeException {

    /**
     * Constructor
     *
     * @param string $message If no message is given default from child
     * @param int $code Status code, default from child
     */
    public function __construct($message, $code) {

        // save an exception to log
        (new Logger())->critical($message, [
            'exception' =>  static::TYPE // use as late state binding
        ]);

        parent::__construct($message, $code);
    }
}