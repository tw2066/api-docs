<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Max;
use Hyperf\DTO\Annotation\Validation\Regex;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\StartsWith;
use Hyperf\DTO\Annotation\Validation\Str;
use HyperfExample\ApiDocs\DTO\PageQuery;

class DemoQuery extends PageQuery
{
    #[ApiModelProperty('测试', 'bb')]
    public string $test = 'tt';

    #[ApiModelProperty('测试2')]
    public ?bool $isNew;

    #[ApiModelProperty('名称')]
    #[max(5)]
    #[In(['qq', 'aa'])]
    public string $name;

    #[ApiModelProperty('邮箱')]
    #[Str]
    #[Regex('/^.+@.+$/i')]
    #[StartsWith('aa,bb')]
    #[max(10)]
    public string $email;

    #[ApiModelProperty('数量')]
    #[Integer]
    #[Between(1, 5)]
    #[Required]
    private int $num;

    /**
     * @return int
     */
    public function getNum(): int
    {
        return $this->num;
    }

    /**
     * @param int $num
     */
    public function setNum(int $num): void
    {
        $this->num = $num;
    }


}
