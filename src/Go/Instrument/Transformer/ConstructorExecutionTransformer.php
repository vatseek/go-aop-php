<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2012, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Go\Instrument\Transformer;

use Go\Aop\Intercept\ConstructorInvocation;
use TokenReflection\ReflectionFile;
use Go\Aop\Framework\ReflectionConstructorInvocation;

use TokenReflection\Broker;
use TokenReflection\Resolver;
use TokenReflection\Stream\StreamBase;

/**
 * Transforms the source code to add an ability to intercept new instances creation
 *
 * YAXX definitions:
 *
 * new_expr:
 *  T_NEW
 *  class_name_reference
 *  ctor_arguments
 *
 * class_name_reference:
 *  class_name
 *  | dynamic_class_name_reference
 *
 * class_name:
 *  T_STATIC
 *  | namespace_name
 *  | T_NAMESPACE T_NS_SEPARATOR namespace_name
 *  | T_NS_SEPARATOR namespace_name
 *
 * namespace_name:
 *  T_STRING
 *  | namespace_name T_NS_SEPARATOR T_STRING
 *
 * ctor_arguments:
 *  / empty /
 *  | function_call_parameter_list
 *
 * According to that ABNF, we can easily detect
 *
 * @package go
 * @subpackage instrument
 */
class ConstructorExecutionTransformer implements SourceTransformer
{
    /**
     * @var array|ConstructorInvocation[]
     */
    protected static $constructorInvocationsCache = array();

    /**
     * Reflection broker instance
     *
     * @var Broker
     */
    protected $broker;

    /**
     * List of include paths to process
     *
     * @var array
     */
    protected $includePaths = array();

    /**
     * Constructor execution transformer
     *
     * @param Broker $broker Instance of reflection broker to use
     */
    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    /**
     * Wrap all includes into rewrite filter
     *
     * @param string $source Source for class
     * @param StreamMetaData $metadata Metadata for source
     *
     * @return string Transformed source
     */
    public function transform($source, StreamMetaData $metadata = null)
    {
        if (strpos($source, 'new ')===false) {
            return $source;
        }

        $fileName     = $metadata->getResourceUri();
        $parsedSource = $this->broker->processString($source, $fileName, true);
        $tokenStream  = $this->broker->getFileTokens($fileName);

        $tokenStream->rewind();
        $startPosition     = 0;
        $transformedSource = '';

        while ($tokenStream->find(T_NEW)) {
            $position = $tokenStream->key();
            $transformedSource .= $tokenStream->getSourcePart($startPosition, $position -1);
            $transformedSource .= ' \\' . __CLASS__ . '::construct(';
            $tokenStream->skipWhitespaces();
            $startPosition = $tokenStream->key();
            $className     = $this->parseClassName($tokenStream, $parsedSource);
        }

        //$fqn = Resolver::resolveClassFQN($className, $aliases, $currentNamespace);
        return $transformedSource;
    }

    public static function construct($fullClassName, array $arguments = array())
    {
        if (!isset(self::$constructorInvocationsCache[$fullClassName])) {
            $invocation = null;
            try {
                $joinPointsRef = new \ReflectionProperty($fullClassName, '__joinPoints');
                $joinPointsRef->setAccessible(true);
                $joinPoints = $joinPointsRef->getValue();
                if (isset($joinPoints['init:dynamic'])) {
                    $invocation = $joinPoints['init:dynamic'];
                }
            } catch (\ReflectionException $e) {
                $invocation = new ReflectionConstructorInvocation($fullClassName, array());
            }
            self::$constructorInvocationsCache[$fullClassName] = $invocation;
        }
        return self::$constructorInvocationsCache[$fullClassName]->__invoke($arguments);
    }

    /**
     * Parse class name to construct from source
     *
     * @param StreamBase $tokenStream
     * @param ReflectionFile $reflectionFile
     * @return string
     */
    private function parseClassName(StreamBase $tokenStream, ReflectionFile $reflectionFile)
    {
        $isDynamic = false;
        $className = '';

        // Iterate over the token stream
        while (true) {
            $name = $tokenStream->getTokenName();
            $value = $tokenStream->getTokenValue();
            switch ($tokenStream->getType()) {
                // If the current token is a T_STRING, it is a part of the namespace name
                case T_STRING:
                case T_NS_SEPARATOR:
                    $className .= $tokenStream->getTokenValue();
                    break;

                // If the current token is a T_STATIC, it is a LSB initialization
                case T_STATIC:
                    $className = 'get_called_class()';
                    break 2;

                case T_VARIABLE:
                    $isDynamic = true;
                    $className = $value;
                    break 2;

                default:
                    // Stop iterating when other token than string or ns separator found
                    break 2;
            }

            $tokenStream->skipWhitespaces(true);
        }

        $className = ltrim($className, '\\');
        if (!$isDynamic) {
            /** @var $namespaces \TokenReflection\ReflectionFileNamespace[] */
            $namespaces = $reflectionFile->getNamespaces();
            $className = Resolver::resolveClassFQN($className, $namespaces[0]->getNamespaceAliases());
        }
        return $className;
    }
}
