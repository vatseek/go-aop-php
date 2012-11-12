<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2011, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Go\Aop\Framework;

use Go\Aop\Pointcut;
use Go\Aop\Framework\BaseAdvice;
use Go\Aop\Intercept\Interceptor;

/**
 * @package go
 */
class BaseInterceptor extends BaseAdvice implements Interceptor, \Serializable
{
    /**
     * Name of the aspect
     *
     * @var string
     */
    public $aspectName = '';

    /**
     * Pointcut instance
     *
     * @var null|Pointcut
     */
    public $pointcut = null;

    /**
     * Advice to call
     *
     * In Spring it's ReflectionMethod, but this will be slowly
     *
     * @var null|\Closure
     */
    protected $adviceMethod = null;

    /**
     * Default constructor for interceptor
     *
     * @param callable $adviceMethod Interceptor advice to call
     * @param Pointcut $pointcut Pointcut instance where interceptor should be called
     */
    public function __construct($adviceMethod, Pointcut $pointcut = null)
    {
        assert('is_callable($adviceMethod) /* Advice method should be callable */');

        $this->adviceMethod = $adviceMethod;
        $this->pointcut     = $pointcut;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $refAdvice = new \ReflectionFunction($this->adviceMethod);
        $aspect = $refAdvice->getClosureThis();
        $dataToSerialize = get_object_vars($this) + array(
            'refMethod' => array(
                $aspect,
                $refAdvice->name,
            )
        );
        unset($dataToSerialize['adviceMethod']);
        return serialize($dataToSerialize);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return mixed the original value unserialized.
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        list ($aspect, $adviceName) = $data['refMethod'];
        $refMethod = new \ReflectionMethod($aspect, $adviceName);
        $adviceMethod = $refMethod->getClosure($aspect);
        $this->__construct($adviceMethod, $data['pointcut']);
    }
}
