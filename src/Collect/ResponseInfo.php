<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\DTO\DtoCommon;
use Hyperf\DTO\Scan\Property;

class ResponseInfo
{
    use DtoCommon;

    public mixed $code = '200';

    public ?string $description = null;

    public ?Property $property = null;

    public static function form(ApiResponse $apiResponse): ResponseInfo
    {
        $responseInfo = new ResponseInfo();
        $responseInfo->code = $apiResponse->code;
        $responseInfo->description = $apiResponse->description;
        $className = $apiResponse->className;
        $type = $apiResponse->type;

        $property = new Property();
        //存在type && 简单类型
        if (! empty($type) && static::isSimpleType($type)) {
            $property->phpSimpleType = $type;
            //判断是数组
            if ($type == 'array' && ! empty($className)) {
                if (class_exists($className)) {
                    $property->isSimpleType = false;
                    $property->arrClassName = $className;
                }
                if (static::isSimpleType($className)) {
                    $property->isSimpleType = false;
                    $property->arrSimpleType = $className;
                }
            }
            $responseInfo->property = $property;
        } elseif (! empty($className) && class_exists($className)) {
            $property->isSimpleType = false;
            $property->className = $className;
            $responseInfo->property = $property;
        }
        return $responseInfo;
    }
}
