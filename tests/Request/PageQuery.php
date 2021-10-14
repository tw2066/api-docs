<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

class PageQuery
{
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
