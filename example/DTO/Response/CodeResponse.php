<?php

namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\ApiDocs\Annotation\ApiVariable;

class CodeResponse
{
    public string $code = '200';

    #[ApiVariable]
    public mixed $data;

    #[ApiVariable]
    public mixed $content;

    public function __construct($data = null,$content = null)
    {
        $this->data = $data;
        $this->content = $content;
    }

}