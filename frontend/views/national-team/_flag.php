<?php
/**
 * WorldCup Journey - 球队国旗，复用公共标识组件
 * 模块：A/C 前端联调；负责人：A；依赖：TeamFlag、NationalTeam
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
use app\components\TeamFlag;
/** @var app\models\NationalTeam $team */
/** @var string $size */
echo TeamFlag::render($team->fifa_code, $team->name_zh, $team->flag_path, $size ?? 'sm');
