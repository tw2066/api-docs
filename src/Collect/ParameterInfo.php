<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

use Hyperf\DTO\Scan\Property;

class ParameterInfo
{

    //{
    //  "name": "token",
    //  "in": "header",
    //  "description": "token to be passed as a header",
    //  "required": true,
    //  "schema": {
    //    "type": "array",
    //    "items": {
    //      "type": "integer",
    //      "format": "int64"
    //    }
    //  },
    //  "style": "simple"
    //}



    public string $name;

    public string $in;

    public ?string $description = null;

    public ?bool $required= null;

    public ?string $type = null;

    public mixed $default = null;

    public mixed $example = null;

    public bool $hidden = false;

    public ?array $enum = null;



//    public ?Schema $schema = null;


    public ?Property $property = null;

    //类型
    public bool $isSimpleType;

    public ?string $phpType= null;

    public ?string $className = null;


}
