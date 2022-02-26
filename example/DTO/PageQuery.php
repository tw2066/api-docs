<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class PageQuery
{
    #[ApiModelProperty('页数')]
    public ?int $page = null;

    public ?int $size = null;

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(?int $page): void
    {
        $this->page = $page;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): void
    {
        $this->size = $size;
    }
}
