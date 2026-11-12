<?php
/**
 * WorldCup Journey - 本地球队国旗与安全自定义图片显示
 * 模块：A 公共前端；负责人：A；依赖：本地 flag-icons、Yii Html/Url
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
namespace app\components;

use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

final class TeamFlag
{
    /** 优先使用后台填写的现有站内图片，否则按 FIFA 代码使用随代码分发的国旗。 */
    public static function url(?string $code, ?string $customPath = null): ?string
    {
        $path = str_replace('\\', '/', trim((string) $customPath));
        if ($path !== '' && !str_starts_with($path, '/')
            && !preg_match('/[\x00-\x1F\x7F%:?#]/', $path)
            && !array_intersect(['.', '..', ''], explode('/', $path))
            && is_file(Yii::getAlias('@webroot/') . $path)) {
            return Url::to('@web/' . implode('/', array_map('rawurlencode', explode('/', $path))));
        }
        $code = strtoupper(trim((string) $code));
        if (preg_match('/^[A-Z]{3}$/D', $code) && is_file(Yii::getAlias('@webroot/images/flags/') . $code . '.svg')) {
            return Url::to('@web/images/flags/' . $code . '.svg');
        }
        return null;
    }

    public static function render(?string $code, string $name, ?string $path = null, string $size = 'sm'): string
    {
        $size = in_array($size, ['sm', 'lg'], true) ? $size : 'sm';
        $url = self::url($code, $path);
        if ($url === null) {
            return Html::tag('span', Html::encode(mb_substr($name, 0, 1)), [
                'class' => 'wc-flag wc-flag-' . $size . ' wc-flag-placeholder', 'aria-hidden' => 'true',
            ]);
        }
        return Html::img($url, ['alt' => $name . '国旗', 'class' => 'wc-flag wc-flag-' . $size,
            'width' => $size === 'lg' ? 96 : 48, 'height' => $size === 'lg' ? 72 : 36,
            'loading' => 'lazy', 'decoding' => 'async']);
    }
}
