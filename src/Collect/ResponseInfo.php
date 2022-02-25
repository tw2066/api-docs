<?php

declare(strict_types=1);

namespace Hyperf\ApiDocs\Collect;

class ResponseInfo
{
    //responses: {200: {schema: {$ref: "#/definitions/ActivityResponse"}, description: "OK"},…}
    //200: {schema: {$ref: "#/definitions/ActivityResponse"}, description: "OK"}
    //description: "OK"
    //schema: {$ref: "#/definitions/ActivityResponse"}
    //$ref: "#/definitions/ActivityResponse"

    public string $httpCode = '200';

    public ?string $description = null;



    public bool $isSimpleType = true;

    public ?string $phpType= null;

    public ?string $className = null;


//    public ?Schema $schema = null;



}
