<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\ArrayType;

class DemoBodyRequest
{

    public int $int = 12345;

    public string $str = 'hi';

    private bool $bo = true;

    public Address $address;

    /**
     * @var \HyperfTest\ApiDocs\Request\Address[]
     */
    public array $addressList1;

    /**
     * @var Address[]
     */
    public array $addressList2;


    #[ArrayType(Address::class)]
    public array $addressList3;

    /**
     * @var int[]
     */
    public array $intList1;

    #[ArrayType('int')]
    public array $intList2;

    /**
     * @param bool $bo
     */
    public function setBo(bool $bo): void
    {
        $this->bo = $bo;
    }



}
