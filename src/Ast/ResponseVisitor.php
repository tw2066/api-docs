<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Ast;

use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\DTO\Annotation\ArrayType;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;
use ReflectionProperty;

class ResponseVisitor extends NodeVisitorAbstract
{
    public BuilderFactory $factory;

    public function __construct(
        protected object $generateClass,
        protected string $generateClassName,
        protected array $propertyArr,
    ) {
        $this->factory = new BuilderFactory();
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Property) {
            $propertyName = $node->props[0]->name->name;
            // 存在可变变量
            if (isset($this->propertyArr[$propertyName])) {
                $propertyTypeName = $this->propertyArr[$propertyName];
                if (is_array($propertyTypeName)) {
                    $arrayType = $propertyTypeName[0];
                    $name = new Node\Name('\Hyperf\DTO\Annotation\ArrayType');
                    $node->attrGroups[] = new Node\AttributeGroup([
                        new Node\Attribute(
                            $name,
                            $this->buildAttributeArgs(new ArrayType($arrayType)),
                        ),
                    ]);

                    $propertyTypeName = 'array';
                }
                $node->type = new Node\Identifier($propertyTypeName);
                // $node->props[0]->default = null;
            }
        }
        if ($node instanceof Node\Stmt\Class_) {
            $node->name = $this->generateClassName;
        }
        if ($node instanceof Node\Stmt\Namespace_) {
            $name = new Node\Name('ApiDocs\\Proxy');
            $node->name = $name;
        }
    }

    protected function buildAttributeArgs(AbstractAnnotation $annotation, array $args = []): array
    {
        return $this->factory->args(array_merge($args, $this->getNotDefaultPropertyFromAnnotation($annotation)));
    }

    protected function getNotDefaultPropertyFromAnnotation(AbstractAnnotation $annotation): array
    {
        $properties = [];
        $ref = new ReflectionClass($annotation);
        foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->hasDefaultValue() && $property->getDefaultValue() === $property->getValue($annotation)) {
                continue;
            }
            $properties[$property->getName()] = $property->getValue($annotation);
        }
        return $properties;
    }
}
