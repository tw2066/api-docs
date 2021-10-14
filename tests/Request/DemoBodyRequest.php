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

namespace HyperfTest\ApiDocs\Request;

use Hyperf\DTO\Annotation\Validation\BaseValidation;
use HyperfTest\DTO;
use Hyperf\DTO\Annotation\Validation\Between;
use Hyperf\DTO\Annotation\Validation\Email;
use Hyperf\DTO\Annotation\Validation\In;
use Hyperf\DTO\Annotation\Validation\Integer;
use Hyperf\DTO\Annotation\Validation\Nullable;
use Hyperf\DTO\Annotation\Validation\Required;
use Hyperf\DTO\Annotation\Validation\Validation;

class DemoBodyRequest
{

    public const IN = ['A', 'B', 'C'];

    private int $int = 5;

    private string $string = 'string';

    /**
     * @var \HyperfTest\ApiDocs\Request\Address[]
     */
    private array $arr;

    private object $obj;

}
