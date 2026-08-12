<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class TreeSibling
{
    #[ApiModelProperty('标签')]
    public string $label = '';

    #[ApiModelProperty('回指节点')]
    public ?TreeNode $node = null;
}
