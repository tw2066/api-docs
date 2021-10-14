<?php

declare(strict_types=1);

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Max;
use Hyperf\DTO\Annotation\Validation\Regex;
use Hyperf\DTO\Annotation\Validation\StartsWith;
use Hyperf\DTO\Annotation\Validation\Str;
use Hyperf\DTO\Annotation\Validation\Validation;

class DemoQuery extends PageQuery
{
    public string $test = 'string';

    public ?bool $isNew;

    #[max(5)]
    #[In(['qq', 'aa'])]
    public string $name;

    #[Str]
    #[Regex('/^.+@.+$/i')]
    #[StartsWith('aa,bb')]
    #[max(10)]
    public string $email;

    #[Integer]
    #[Between(1, 5)]
    public int $num;

//    #[Validation('foo')]
//    public string $foo;
}
