<?php

declare(strict_types=1);

namespace HyperfExample\ApiDocs\DTO\Request;

use Hyperf\DTO\Annotation\JSONField;

class DemoQuery
{

    #[JSONField('test')]
    public string $test123456 = 'tt12345';

    public function ttttttttttt()
    {
        return [
            'name11111' => $this->test123456,
            'name22222' => $this->get4444(),
        ];
    }
}
