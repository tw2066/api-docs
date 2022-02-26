<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

use Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\DTO\Scan\Property;

class ResponseInfo
{

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
        if (! empty($className) || ! empty($type)) {
            $property = new Property();
            $property->isSimpleType = true;
            $property->phpType = $type;
            $property->className = $className;
            if (class_exists($className)) {
                $property->isSimpleType = false;
            }
            $responseInfo->property = $property;
        }
        return $responseInfo;
    }
}
