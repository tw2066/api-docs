<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\JSONField;
use Hyperf\DTO\Annotation\Validation\Required;

class AccessorSchema
{
    public readonly string $id;

    public $tag;

    public ?SchemaPaymentInterface $payment = null;

    public array $ids = [];

    public mixed $payload = null;

    private string $status = '';

    #[JSONField('name_arr')]
    #[Required]
    private array $nameArr = [];

    private string $internal = 'hidden';

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setTag(Address $tag): void
    {
        $this->tag = $tag;
    }

    private function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
