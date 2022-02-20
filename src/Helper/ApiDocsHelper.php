<?php
/**
 * Created by PhpStorm.
 * Date:  2022/2/18
 * Time:  7:08 PM
 */

declare(strict_types=1);

namespace Hyperf\ApiDocs\Helper;

use Hyperf\ApiDocs\Annotation\ApiModelProperty;
use Hyperf\DTO\ApiAnnotation;

class ApiDocsHelper
{

    /**
     * @param string $classname
     *
     * @return array
     */
    public function columns(string $classname): array
    {
        $data = [];
        $result = ApiAnnotation::propertyMetadata($classname);
        foreach ($result as $filed => $items) {
            /* @var ApiModelProperty|null $item */
            $item = $items[ApiModelProperty::class] ?? null;
            if (is_null($item) || !$item->column) {
                continue;
            }
            $data[] = is_string($item->column) ? $item->column : $filed;
        }
        return $data;
    }
}
