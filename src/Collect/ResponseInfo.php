<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

use Hyperf\DTO\Scan\Property;

class ResponseInfo
{
    //responses: {200: {schema: {$ref: "#/definitions/ActivityResponse"}, description: "OK"},…}
    //200: {schema: {$ref: "#/definitions/ActivityResponse"}, description: "OK"}
    //description: "OK"
    //schema: {$ref: "#/definitions/ActivityResponse"}
    //$ref: "#/definitions/ActivityResponse"

    public string $httpCode = '200';

    public ?string $description = null;



    public Property $property;


//    public ?Schema $schema = null;



}
