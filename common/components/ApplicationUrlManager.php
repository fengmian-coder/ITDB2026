<?php
/** WorldCup Journey - 保留原站点 URL，并支持为独立入口生成跨应用链接。 */
declare(strict_types=1);

namespace common\components;

use yii\web\UrlManager;

class ApplicationUrlManager extends UrlManager
{
    public ?string $frontendEntry = null;
    public ?string $backendEntry = null;

    public static function isBackendRoute(mixed $route): bool
    {
        if (!is_string($route)) {
            return false;
        }

        $controller = explode('/', trim($route, '/'), 2)[0];
        return $controller === 'auth' || str_starts_with($controller, 'admin-');
    }

    public function createUrl($params)
    {
        $params = (array) $params;
        $entry = self::isBackendRoute($params[0] ?? null) ? $this->backendEntry : $this->frontendEntry;
        if ($entry === null) {
            return parent::createUrl($params);
        }

        return (new UrlManager(['scriptUrl' => $entry]))->createUrl($params);
    }
}
