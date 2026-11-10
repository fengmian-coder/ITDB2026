<?php
/**
 * WorldCup Journey - 统一后台的入口、表单、认证和留言操作回归验收。
 * 模块：A 后台公共界面；负责人：A。
 * 依赖：本机 Web 服务、D 的认证/留言模块、当前数据库结构。
 * 最近修改：2026-11-10。临时管理员与留言在 finally 中按 ID 清理。
 */
declare(strict_types=1);
use app\components\AdminNavigation;
use app\models\AdminUser;
use app\models\GuestbookMessage;
use app\models\GuestbookReply;

$root = dirname(__DIR__, 2);
defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_ENV') || define('YII_ENV', 'test');
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
$app = new yii\console\Application(require $root . '/config/console.php');
if ($app->db->createCommand('SELECT DATABASE()')->queryScalar() !== 'worldcup_journey') {
    throw new RuntimeException('请使用本机 worldcup_journey 开发数据库。');
}
$http = curl_init();
curl_setopt($http, CURLOPT_COOKIEFILE, '');
$request = static function (string $route, ?array $fields = null) use ($http): array {
    curl_setopt_array($http, [CURLOPT_URL => 'http://127.0.0.1:8080/index.php?r=' . $route,
        CURLOPT_NOPROXY => '127.0.0.1', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
        CURLOPT_HEADER => true, CURLOPT_FOLLOWLOCATION => false]);
    if ($fields === null) curl_setopt($http, CURLOPT_HTTPGET, true);
    else { curl_setopt($http, CURLOPT_POST, true); curl_setopt($http, CURLOPT_POSTFIELDS, http_build_query($fields)); }
    $response = curl_exec($http);
    if ($response === false) throw new RuntimeException('本机 Web 服务不可用。');
    return ['status' => curl_getinfo($http, CURLINFO_RESPONSE_CODE),
        'body' => substr($response, curl_getinfo($http, CURLINFO_HEADER_SIZE))];
};
$csrfOf = static function (array $response): string {
    if (!preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response['body'], $match)) throw new RuntimeException('页面缺少 CSRF token。');
    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    echo 'PASS: ' . $label . PHP_EOL;
    ++$checks;
};
$activeController = static function (string $html): ?string {
    $before = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    $links = (new DOMXPath($dom))->query('//aside//a[@aria-current="page"]');
    $href = $links->length === 1 ? $links->item(0)->getAttribute('href') : '';
    libxml_clear_errors(); libxml_use_internal_errors($before);
    parse_str(parse_url($href, PHP_URL_QUERY) ?: '', $parameters);
    return isset($parameters['r']) ? explode('/', $parameters['r'])[0] : null;
};
$marker = 'a_workspace_' . bin2hex(random_bytes(5));
$password = Yii::$app->security->generateRandomString(40);
$admin = null;
$message = null;
try {
    foreach (AdminNavigation::groups() as $items) {
        foreach ($items as $item) {
            $guest = $request($item['id'] . '/index');
            $check(in_array($guest['status'], [302, 403], true), $item['label'] . '：游客访问被拦截');
        }
    }
    $login = $request('auth/login');
    $check($login['status'] === 200 && str_contains($login['body'], 'class="auth-body"')
        && str_contains($login['body'], 'admin-login-form'), '登录页使用独立品牌布局与 D 的表单');
    $admin = new AdminUser(['username' => $marker,
        'password_hash' => Yii::$app->security->generatePasswordHash($password),
        'auth_key' => Yii::$app->security->generateRandomString(), 'status' => 1]);
    $admin->insert(false);
    $bad = $request('auth/login', ['_csrf' => $csrfOf($login), 'AdminLoginForm' => ['username' => $marker, 'password' => 'wrong-password']]);
    $check($bad['status'] === 200 && str_contains($bad['body'], '账号或密码不正确'), '错误密码仍由 D 的认证逻辑拒绝');
    $loggedIn = $request('auth/login', ['_csrf' => $csrfOf($bad), 'AdminLoginForm' => ['username' => $marker, 'password' => $password]]);
    $check($loggedIn['status'] === 302, '正确密码可以登录管理工作台');
    $dashboard = $request('admin-dashboard/index');
    $csrf = $csrfOf($dashboard);
    $post = static fn (string $route, array $fields): array => $request($route, ['_csrf' => $csrf] + $fields);
    foreach (AdminNavigation::groups() as $items) {
        foreach ($items as $item) {
            $page = $request($item['id'] . '/index');
            $check($page['status'] === 200 && str_contains($page['body'], 'class="admin-body"')
                && str_contains($page['body'], 'css/admin.css') && $activeController($page['body']) === $item['id'],
                $item['label'] . '：共用布局、样式与正确侧栏高亮');
            $check(str_contains($page['body'], 'admin-logout-form') && !str_contains($page['body'], $password),
                $item['label'] . '：提供退出表单且不回显密码');
        }
    }
    foreach (['admin-project', 'admin-document', 'admin-tournament', 'admin-match', 'admin-stadium', 'admin-story'] as $controller) {
        $page = $request($controller . '/create');
        $check($page['status'] === 200 && str_contains($page['body'], 'class="admin-body"')
            && str_contains($page['body'], 'name="_csrf"') && $activeController($page['body']) === $controller,
            $controller . '：新增表单正常显示并保留 CSRF');
    }
    $message = new GuestbookMessage(['nickname' => $marker, 'content' => '<script>测试内容</script>']);
    if (!$message->save()) throw new RuntimeException('临时留言校验失败。');
    $message->refresh();
    $moderation = $request('admin-guestbook/index');
    $check(str_contains($moderation['body'], '&lt;script&gt;测试内容&lt;/script&gt;')
        && str_contains($moderation['body'], 'admin-status pending'), '新留言以待审核状态展示且内容转义');
    $approved = $post('admin-guestbook/moderate&id=' . $message->id, ['status' => GuestbookMessage::APPROVED]);
    $message->refresh();
    $check($approved['status'] === 303 && $message->status === GuestbookMessage::APPROVED, '新版审核操作可以通过留言');
    $approvedPage = $request('admin-guestbook/index');
    $check(str_contains($approvedPage['body'], 'id="reply-' . $message->id . '"'), '审核通过后展示回复表单');
    $reply = $post('admin-guestbook/reply&id=' . $message->id, ['content' => '后台统一界面回归测试回复。']);
    $check($reply['status'] === 303 && GuestbookReply::find()->where(['message_id' => $message->id])->exists(), '新版回复表单可以正常发布');
    $rejected = $post('admin-guestbook/moderate&id=' . $message->id, ['status' => GuestbookMessage::REJECTED]);
    $public = $request('guestbook/index');
    $check($rejected['status'] === 303 && !str_contains($public['body'], $marker), '拒绝留言后前台不公开该留言与回复');
    $check($request('auth/logout')['status'] === 405, '退出仍然仅允许 POST');
    $check($post('auth/logout', [])['status'] === 302
        && $request('admin-dashboard/index')['status'] === 302, '退出后后台重新拦截访问');
    echo 'SUCCESS: ' . $checks . ' checks passed.' . PHP_EOL;
} finally {
    if ($message !== null && !$message->isNewRecord) {
        GuestbookReply::deleteAll(['message_id' => $message->id]);
        $message->delete();
    }
    if ($admin !== null && !$admin->isNewRecord) $admin->delete();
    curl_close($http);
}
