<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Swagger;

use Hyperf\ApiDocs\Annotation\ApiVariable;
use Hyperf\ApiDocs\Ast\ResponseVisitor;
use Hyperf\ApiDocs\Exception\ApiDocsException;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Support\Composer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class GenerateProxyClass
{
    protected ?array $apiVariableClassArr = null;

    protected array $proxyClassArr = [];

    public function __construct(
        private SwaggerConfig $swaggerConfig,
        private SwaggerCommon $swaggerCommon,
    ) {
        $proxyDir = $this->swaggerConfig->getProxyDir();
        if (file_exists($proxyDir) === false) {
            if (mkdir($proxyDir, 0755, true) === false) {
                throw new ApiDocsException("Failed to create a directory : {$proxyDir}");
            }
        }
    }

    public function getGenericClass(string $newClassname)
    {
        $newClassname = trim($newClassname, '\\');
        if ($this->apiVariableClassArr === null) {
            $arr = [];
            $classes = AnnotationCollector::getPropertiesByAnnotation(ApiVariable::class);
            foreach ($classes as $class) {
                $classname = $class['class'];
                $arr[$classname][] = $class['property'];
            }
            $this->apiVariableClassArr = $arr;
        }
        return $this->apiVariableClassArr[$newClassname] ?? [];
    }

    /**
     * 生成代理类.
     */
    public function generate(object $obj): string
    {
        $ref = new \ReflectionClass($obj);
        $classname = $obj::class;
        $properties = $this->getGenericClass($classname);
        if (empty($properties)) {
            return $classname;
        }

        $propertyArr = [];
        foreach ($properties as $property) {
            // 获取变量
            $propertyObj = $obj->{$property};

            $type = $this->swaggerCommon->getPhpType($propertyObj);
            if (is_object($propertyObj) && $type != '\stdClass') {
                $propertyClassname = $type;
                if ($this->getGenericClass($propertyClassname)) {
                    $propertyClassname = $this->generate($propertyObj);
                }
                $type = $propertyClassname;
            }
            $propertyArr[$property] = $type;
            if (is_array($propertyObj) && count($propertyObj) > 0) {
                $arrayType = $this->swaggerCommon->getPhpType($propertyObj[0]);
                if (is_object($propertyObj[0]) && $propertyObj[0]::class != '\stdClass') {
                    $propertyClassname = $arrayType;
                    if ($this->getGenericClass($propertyClassname)) {
                        $propertyClassname = $this->generate($propertyObj[0]);
                    }
                    $arrayType = $propertyClassname;
                }
                $propertyArr[$property] = [$arrayType];
            }
            if (is_array($propertyObj) && count($propertyObj) == 0) {
                $propertyArr[$property] = 'array';
            }
        }

        $file = new \SplFileInfo($ref->getFileName());
        $realPath = $file->getRealPath();
        [$generateNamespaceClassName, $content] = $this->phpParser($obj, $realPath, $propertyArr);

        if (! isset($this->proxyClassArr[$generateNamespaceClassName])) {
            $this->putContents($generateNamespaceClassName, $content);
            $this->proxyClassArr[$generateNamespaceClassName] = true;
        }

        return '\\' . $generateNamespaceClassName;
    }

    protected function putContents($generateNamespaceClassName, $content): void
    {
        $outputDir = $this->swaggerConfig->getProxyDir();
        $generateClassName = str_replace('\\', '_', $generateNamespaceClassName);
        $filename = $outputDir . $generateClassName . '.dto.proxy.php';
        file_put_contents($filename, $content);
        $classLoader = Composer::getLoader();
        $classLoader->addClassMap([$generateNamespaceClassName => $filename]);
    }

    protected function phpParser(object $generateClass, $filePath, $propertyArr): array
    {
        $code = file_get_contents($filePath);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $parser->parse($code);

        $simpleClassName = $this->swaggerCommon->getSimpleClassName($generateClass::class);
        $generateClassName = $simpleClassName;
        foreach ($propertyArr as $type) {
            if (is_array($type)) {
                $generateClassName .= 'Array';
                $type = $type[0];
            }
            $type = $this->swaggerCommon->getSimpleClassName($type);
            $generateClassName .= $type;
        }
        $fullGenerateClassName = 'ApiDocs\\Proxy\\' . $generateClassName;
        if (isset($this->proxyClassArr[$fullGenerateClassName])) {
            return [$fullGenerateClassName, ''];
        }

        $traverser = new NodeTraverser();
        $resVisitor = \Hyperf\Support\make(ResponseVisitor::class, [$generateClass, $generateClassName, $propertyArr]);
        $traverser->addVisitor($resVisitor);
        $ast = $traverser->traverse($ast);

        $prettyPrinter = new PrettyPrinter\Standard();
        $content = $prettyPrinter->prettyPrintFile($ast);
        return [$fullGenerateClassName, $content];
    }
}
