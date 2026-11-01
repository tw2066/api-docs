<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiModel;
use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\ApiDocs\Annotation\ApiVariable;

#[ApiModel('统一响应包装')]
class CodeResponse
{
    #[ApiModelProperty('状态码')]
    public string $code = '200';

    #[ApiVariable]
    public mixed $data = null;

    #[ApiVariable]
    public mixed $content = null;

    public function __construct(mixed $data = null, mixed $content = null)
    {
        $this->data = $data;
        $this->content = $content;
    }
}
