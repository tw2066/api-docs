<?php

declare(strict_types=1);

namespace Hyperf\DTO\Scan;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\DTO\Annotation\Contracts\RequestBody;
use Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Hyperf\DTO\Annotation\Contracts\Valid;
use Hyperf\DTO\Annotation\Validation\BaseValidation;
use Hyperf\DTO\ApiAnnotation;
use Hyperf\DTO\Exception\DtoException;
use Hyperf\DTO\Reflection;
use JsonMapper;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Throwable;

class ScanAnnotation extends JsonMapper
{
    private static array $scanClassArray = [];

    private MethodDefinitionCollectorInterface $methodDefinitionCollector;

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->methodDefinitionCollector = $this->container->get(MethodDefinitionCollectorInterface::class);
    }

    /**
     * 扫描控制器中的方法.
     * @param $className
     * @param $methodName
     * @throws ReflectionException
     */
    public function scan($className, $methodName)
    {
        $this->setMethodParameters($className, $methodName);
        $definitionParamArr = $this->methodDefinitionCollector->getParameters($className, $methodName);
        $definitionReturn = $this->methodDefinitionCollector->getReturnType($className, $methodName);
        array_push($definitionParamArr, $definitionReturn);
        foreach ($definitionParamArr as $definition) {
            $parameterClassName = $definition->getName();
            if ($this->container->has($parameterClassName)) {
                $this->scanClass($parameterClassName);
            }
        }
    }

    public function clearScanClassArray()
    {
        self::$scanClassArray = [];
    }

    public function scanClass(string $className)
    {
        if (in_array($className, self::$scanClassArray)) {
            return;
        }
        self::$scanClassArray[] = $className;
        $rc = ReflectionManager::reflectClass($className);
        $context = new Context($rc->getNamespaceName(), Reflection::getUseStatements($rc));
        $strNs = $rc->getNamespaceName();
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $propertyClassName = $type = $this->getTypeName($reflectionProperty);
            $fieldName = $reflectionProperty->getName();
            $isSimpleType = true;
            if ($type == 'array') {
                $arrType = null;
                $docblock = $reflectionProperty->getDocComment();
                $annotations = static::parseAnnotations($docblock);
                if (! empty($annotations)) {
                    //support "@var type description"
                    [$varType] = explode(' ', $annotations['var'][0]);
                    $varType = $this->getFullNamespace($varType, $strNs);
                    if ($this->isArrayOfType($varType)) {
                        $arrType = substr($varType, 0, -2);
                        $isSimpleType = $this->isSimpleType($arrType);
                        if (!$isSimpleType) {
                            $factory = DocBlockFactory::createInstance();
                            $block = $factory->create($docblock, $context);
                            $tags = $block->getTagsByName('var');
                            $tag = current($tags);
                            $arrType = $tag->getType()->getValueType()->getFqsen()->__toString();
                        }
                        if (! $this->isSimpleType($arrType) && $this->container->has($arrType)) {
                            $this->scanClass($arrType);
                            PropertyManager::setNotSimpleClass($className);
                        }
                    }
                }
                $propertyClassName = $arrType;
            }
            if (! $this->isSimpleType($type)) {
                $this->scanClass($type);
                $isSimpleType = false;
                $propertyClassName = $type;
                PropertyManager::setNotSimpleClass($className);
            }

            $property = new Property();
            $property->type = $type;
            $property->isSimpleType = $isSimpleType;
            $property->className = $propertyClassName ? trim($propertyClassName, '\\') : null;
            PropertyManager::setContent($className, $fieldName, $property);

            $this->generateValidation($className, $fieldName);
        }
    }

    /**
     * generateValidation.
     */
    protected function generateValidation(string $className, string $fieldName)
    {
        /** @var BaseValidation[] $validation */
        $validationArr = [];
        $annotationArray = ApiAnnotation::getClassProperty($className, $fieldName);

        foreach ($annotationArray as $annotation) {
            if ($annotation instanceof BaseValidation) {
                $validationArr[] = $annotation;
            }
        }
        $ruleArray = [];
        foreach ($validationArr as $validation) {
            if (empty($validation->getRule())) {
                continue;
            }
            $ruleArray[] = $validation->getRule();
            if (empty($validation->messages)) {
                continue;
            }
            [$messagesRule,] = explode(':', $validation->getRule());
            $key = $fieldName . '.' . $messagesRule;
            ValidationManager::setMessages($className, $key, $validation->messages);
        }
        if (! empty($ruleArray)) {
            ValidationManager::setRule($className, $fieldName, $ruleArray);
            foreach ($annotationArray as $annotation) {
                if ($annotation instanceof ApiModelProperty && ! empty($annotation->value)) {
                    ValidationManager::setAttributes($className, $fieldName, $annotation->value);
                }
            }
        }
    }

    protected function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }
        return $type;
    }

    /**
     * 设置方法中的参数.
     * @param $className
     * @param $methodName
     * @throws ReflectionException
     */
    private function setMethodParameters($className, $methodName)
    {
        // 获取方法的反射对象
        $ref = new ReflectionMethod($className . '::' . $methodName);
        // 获取方法上指定名称的全部注解
        $attributes = $ref->getParameters();
        $methodMark = 0;
        foreach ($attributes as $attribute) {
            $methodParameters = new MethodParameter();
            $paramName = $attribute->getName();
            $mark = 0;
            if ($attribute->getAttributes(RequestQuery::class)) {
                $methodParameters->setIsRequestQuery(true);
                ++$mark;
            }
            if ($attribute->getAttributes(RequestFormData::class)) {
                $methodParameters->setIsRequestFormData(true);
                ++$mark;
                ++$methodMark;
            }
            if ($attribute->getAttributes(RequestBody::class)) {
                $methodParameters->setIsRequestBody(true);
                ++$mark;
                ++$methodMark;
            }
            if ($attribute->getAttributes(Valid::class)) {
                $methodParameters->setIsValid(true);
            }
            if ($mark > 1) {
                throw new DtoException("Parameter annotation [RequestQuery RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}:{$paramName}]");
            }
            MethodParametersManager::setContent($className, $methodName, $paramName, $methodParameters);
        }
        if ($methodMark > 1) {
            throw new DtoException("Method annotation [RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}]");
        }
    }
}
