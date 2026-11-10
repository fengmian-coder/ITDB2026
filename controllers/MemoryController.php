<?php
/**
 * WorldCup Journey - 无注册纪念卡编辑页；个人感想和照片仅在浏览器内处理。
 * 模块：A 无注册纪念卡；负责人：A。
 * 依赖：MemoryCardService、views/memory/card、memory-card.js。
 * 最近修改：2026-11-10。
 */
declare(strict_types=1);
namespace app\controllers;

use app\services\MemoryCardService;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;

class MemoryController extends Controller
{
    public function behaviors(): array
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => ['card' => ['GET', 'HEAD']]]];
    }

    public function actionCard(mixed $team_id = null, mixed $match_id = null): string
    {
        $data = (new MemoryCardService())->getEditor($this->optionalId($team_id), $this->optionalId($match_id));
        return $this->render('card', $data);
    }

    private function optionalId(mixed $id): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        if ((!is_string($id) && !is_int($id)) || !preg_match('/\A[1-9][0-9]{0,18}\z/', (string) $id)
            || (strlen((string) $id) === 19 && strcmp((string) $id, (string) PHP_INT_MAX) > 0)) {
            throw new BadRequestHttpException('球队和比赛编号必须为有效正整数');
        }
        return (int) $id;
    }
}
