<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\ArrayType;

class DemoBodyRequest
{

    public int $int = 12345;

    public string $str = 'hi';

    public bool $bo = true;

    public Address $address;

    /**
     * @var \HyperfTest\ApiDocs\Request\Address[]
     */
    private array $addressList1;

    /**
     * @var Address[]
     */
    private array $addressList2;


    #[ArrayType(Address::class)]
    private array $addressList3;

    /**
     * @var int[]
     */
    private array $intList1;

    #[ArrayType('int')]
    private array $intList2;


}
