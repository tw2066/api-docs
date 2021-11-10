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
namespace HyperfExample\ApiDocs\DTO\Response;

use Hyperf\Contract\LengthAwarePaginatorInterface;

class ActivityPage extends Page
{
    /**
     * @var \HyperfExample\ApiDocs\DTO\Response\ActivityResponse[]
     */
    public array $content;

    public static function from(LengthAwarePaginatorInterface $page): ActivityPage
    {
        $activityPage = new self();
        $arr = [];
        foreach ($page as $model) {
            $arr[] = ActivityResponse::from($model);
        }
        $activityPage->content = $arr;
        $activityPage->setTotal($page->total());
        $activityPage->setCurrentPage($page->currentPage());
        $activityPage->setPerPage($page->perPage());
        return $activityPage;
    }
}
