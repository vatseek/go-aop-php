<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2012, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Go\Lang\Annotation;

/**
 * Pointcut annotation
 *
 * @Annotation
 * @Target("METHOD")
 *
 * @Attributes({
 *   @Attribute("id", type = "string", required=true),
 *   @Attribute("value", type = "string", required=true)
 * })
 */
class Pointcut extends BaseAnnotation
{
    /**
     * Identifier of pointcut
     *
     * @var string
     */
    public $id = null;
}
