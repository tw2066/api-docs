<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;

class TreeNode
{
    #[ApiModelProperty('节点名')]
    public string $name = '';

    #[ApiModelProperty('子节点')]
    public ?TreeNode $child = null;

    #[ApiModelProperty('兄弟节点')]
    public ?TreeSibling $sibling = null;
}
