<?php
/**
 * WorldCup Journey - 团队资料的本机 HTTP / 数据库集成验收。
 * 模块：A 团队资料；负责人：A。
 * 依赖：运行中的本地 Web 服务、curl、C 的数据库结构与 D 的认证模块。
 * 最近修改：2026-11-10。
 * 测试使用随机临时管理员和团队；finally 仅清理本次创建的数据。
 */
declare(strict_types=1);

use app\models\AdminUser;
use app\models\ProjectMember;
use app\models\ProjectTeam;
use app\models\ProjectDocument;
use app\services\DocumentStorageService;

$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
defined('YII_DEBUG') || define('YII_DEBUG', false);
defined('YII_ENV') || define('YII_ENV', 'test');
$app = new yii\console\Application(require $root . '/config/console.php');
$base = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$parts = parse_url($base);
if (($parts['scheme'] ?? '') !== 'http' || !in_array($parts['host'] ?? '', ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('集成验收只允许连接本机 HTTP 服务。');
}
if ($app->db->createCommand('SELECT DATABASE()')->queryScalar() !== 'worldcup_journey') {
    throw new RuntimeException('请使用本地 worldcup_journey 开发数据库。');
}
$http = curl_init();
curl_setopt($http, CURLOPT_COOKIEFILE, '');
$request = static function (string $route, ?array $fields = null) use ($http, $base): array {
    curl_setopt_array($http, [
        CURLOPT_URL => $base . '/index.php?r=' . $route,
        CURLOPT_NOPROXY => '127.0.0.1,localhost',
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 15,
    ]);
    if ($fields === null) {
        curl_setopt($http, CURLOPT_HTTPGET, true);
    } else {
        curl_setopt($http, CURLOPT_POST, true);
        $multipart = count(array_filter($fields, static fn ($value): bool => $value instanceof CURLFile)) > 0;
        curl_setopt($http, CURLOPT_POSTFIELDS, $multipart ? $fields : http_build_query($fields));
    }
    $response = curl_exec($http);
    if ($response === false) {
        throw new RuntimeException('无法连接测试服务，请先启动本地网站。');
    }
    return [
        'status' => curl_getinfo($http, CURLINFO_RESPONSE_CODE),
        'headers' => substr($response, 0, curl_getinfo($http, CURLINFO_HEADER_SIZE)),
        'body' => substr($response, curl_getinfo($http, CURLINFO_HEADER_SIZE)),
    ];
};
$token = static function (array $page): string {
    if (!preg_match('/<meta name="csrf-token" content="([^"]+)"/', $page['body'], $match)) {
        throw new RuntimeException('页面缺少 CSRF token。');
    }
    return html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
$checks = 0;
$check = static function (bool $condition, string $label) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    ++$checks;
    echo 'PASS: ' . $label . PHP_EOL;
};
$marker = 'a_test_' . bin2hex(random_bytes(6));
$admin = null;
$teamId = null;
$documentId = null;
$uploadedDocumentId = null;
$uploadSource = tempnam($root . '/runtime', 'a-upload-');
$uploadPaths = [];
file_put_contents($uploadSource, "A module integration document.\n");
$password = Yii::$app->security->generateRandomString(40);

try {
    $check($request('project-team/index')['status'] === 200, '公开团队页可访问');
    $check($request('admin-project/index')['status'] === 302, '游客被重定向到管理员登录');
    $check($request('admin-document/index')['status'] === 302, '游客不能访问文件后台');
    $admin = new AdminUser([
        'username' => $marker, 'password_hash' => Yii::$app->security->generatePasswordHash($password),
        'auth_key' => Yii::$app->security->generateRandomString(), 'status' => 1,
    ]);
    $admin->insert(false);
    $loginToken = $token($request('auth/login'));
    $login = $request('auth/login', [
        '_csrf' => $loginToken, 'AdminLoginForm' => ['username' => $marker, 'password' => $password],
    ]);
    $check($login['status'] === 302, '复用 D 的数据库管理员登录');
    $index = $request('admin-project/index');
    $check($index['status'] === 200, '管理员可以访问团队后台');
    $csrf = $token($index);
    $post = static fn (string $route, array $fields): array => $request($route, ['_csrf' => $csrf] + $fields);

    $check($post('admin-project/create', ['ProjectTeam' => ['name' => '  ']])['status'] === 422, '空团队名称被拒绝');
    $check($post('admin-project/create', ['ProjectTeam' => ['name' => ['invalid']]])['status'] === 400, '数组字段被拒绝');
    $check($post('admin-project/create', ['ProjectTeam' => [
        'name' => $marker, 'logo_path' => '../config/db.local.php',
    ]])['status'] === 422, '越界图片路径被拒绝');
    $created = $post('admin-project/create', ['ProjectTeam' => [
        'name' => $marker, 'description' => '<script>alert("team")</script>', 'id' => '999999999',
    ]]);
    $team = ProjectTeam::findOne(['name' => $marker]);
    $teamId = $team?->id;
    $check($created['status'] === 303 && $teamId !== null && (string) $teamId !== '999999999', '新增团队且忽略伪造主键');

    $memberInput = [
        'display_name' => '测试成员<script>alert("member")</script>',
        'role_title' => '集成验收', 'bio' => "中文介绍\n第二行", 'sort_order' => '2',
        'project_team_id' => '999999999', 'updated_at' => '2000-01-01 00:00:00',
    ];
    $memberResult = $post('admin-project/create-member&team_id=' . $teamId, ['ProjectMember' => $memberInput]);
    $member = ProjectMember::findOne(['project_team_id' => $teamId]);
    $check($memberResult['status'] === 303 && $member !== null, '新增成员且忽略伪造团队归属');
    $memberId = $member->id;
    $check((string) $member->project_team_id === (string) $teamId && $member->updated_at !== '2000-01-01 00:00:00', '保护团队关联和审计字段');
    $memberInput['display_name'] = str_repeat('长', 81);
    $check($post('admin-project/update-member&id=' . $memberId, ['ProjectMember' => $memberInput])['status'] === 422, '超长成员姓名被拒绝');

    $public = $request('project-team/index')['body'];
    $check(str_contains($public, '&lt;script&gt;alert(&quot;team&quot;)&lt;/script&gt;')
        && str_contains($public, '&lt;script&gt;alert(&quot;member&quot;)&lt;/script&gt;')
        && str_contains($public, '中文介绍<br'), '团队及成员输出转义并保留中文换行');
    $check($post('admin-project/update&id=' . $teamId, ['ProjectTeam' => [
        'name' => $marker, 'description' => $marker . '_synced',
    ]])['status'] === 303 && str_contains($request('project-team/index')['body'], $marker . '_synced'), '后台编辑后前台同步更新');

    $second = $post('admin-project/create-member&team_id=' . $teamId, ['ProjectMember' => [
        'display_name' => $marker . '_first', 'role_title' => '排序验收', 'sort_order' => '1',
    ]]);
    $public = $request('project-team/index')['body'];
    $check($second['status'] === 303 && strpos($public, $marker . '_first') < strpos($public, '测试成员&lt;script'), '成员按显示顺序排列');
    $check($request('admin-project/update&id=-1')['status'] === 400, '负数 ID 返回 400');
    $check($request('admin-project/update&id%5B%5D=1')['status'] === 400, '数组 ID 返回 400');
    $check($request('admin-project/update&id=18446744073709551616')['status'] === 400, '越界 ID 返回 400');
    $check($request('admin-project/update&id=18446744073709551615')['status'] === 404, '不存在的团队返回 404');
    $check($request('admin-project/delete-member&id=' . $memberId)['status'] === 405, 'GET 不能删除成员');
    $check($request('admin-project/delete-member&id=' . $memberId, ['confirm_delete' => '1'])['status'] === 400
        && ProjectMember::findOne($memberId) !== null, '缺少 CSRF 不能删除成员');
    $check($post('admin-project/delete-member&id=' . $memberId, [])['status'] === 400, '删除需要明确勾选确认');
    $check($post('admin-project/delete&id=' . $teamId, ['confirm_delete' => '1'])['status'] === 303
        && ProjectTeam::findOne($teamId) !== null, '团队仍有关联成员时禁止删除');

    $fileFields = [
        'ProjectDocument[project_team_id]' => $teamId, 'ProjectDocument[member_id]' => '',
        'ProjectDocument[document_type]' => 'TEAM', 'ProjectDocument[title]' => $marker . '_file',
        'ProjectDocument[relative_path]' => '../config/db.local.php',
        'ProjectDocument[uploadFile]' => new CURLFile($uploadSource, 'text/plain', 'test.php'),
    ];
    $check($post('admin-document/create', $fileFields)['status'] === 422, '拒绝上传 PHP 文件');
    $fileFields['ProjectDocument[uploadFile]'] = new CURLFile($uploadSource, 'text/plain', 'test.txt');
    $uploaded = $post('admin-document/create', $fileFields);
    $document = ProjectDocument::findOne(['title' => $marker . '_file']);
    $uploadedDocumentId = $document?->id;
    $check($uploaded['status'] === 303 && $document !== null, '管理员可以上传课程文件');
    $storage = new DocumentStorageService();
    $uploadPaths[] = $storage->resolve($document);
    $check(preg_match('~\Ateam/[a-f0-9]{32}\.txt\z~', $document->relative_path) === 1, '随机存储名称且忽略客户端路径');
    $downloaded = $request('document/download&id=' . $document->id . '&path=../config/db.local.php');
    $check($downloaded['status'] === 200 && $downloaded['body'] === file_get_contents($uploadSource), '下载内容正确且不能用路径参数读取其他文件');
    $check(stripos($downloaded['headers'], 'Content-Disposition: attachment') !== false
        && stripos($downloaded['headers'], 'X-Content-Type-Options: nosniff') !== false, '下载强制附件并禁止 MIME 嗅探');
    $check($request('document/download&id=-1')['status'] === 400
        && $request('document/download&id%5B%5D=1')['status'] === 400, '非法下载 ID 返回 400');
    $check($post('admin-document/update&id=' . $document->id, ['ProjectDocument' => [
        'project_team_id' => $teamId, 'document_type' => 'PERSONAL', 'member_id' => '999999999', 'title' => $marker . '_file',
    ]])['status'] === 422, '个人作业必须绑定同团队的有效成员');
    $converted = $post('admin-document/update&id=' . $document->id, ['ProjectDocument' => [
        'project_team_id' => $teamId, 'document_type' => 'PERSONAL', 'member_id' => $memberId, 'title' => $marker . '_file',
    ]]);
    $document->refresh();
    $uploadPaths[] = $storage->resolve($document);
    $check($converted['status'] === 303 && str_starts_with($document->relative_path, 'personal/')
        && !is_file($uploadPaths[0]), '改变作业类型时同步迁移到对应受控目录');
    $realRelativePath = $document->relative_path;
    $document->updateAttributes(['relative_path' => '../config/db.local.php']);
    $check($request('document/download&id=' . $document->id)['status'] === 404, '数据库中的越界路径同样不能下载');
    $document->updateAttributes(['relative_path' => $realRelativePath]);
    rename($uploadPaths[1], $uploadPaths[1] . '.test-hold');
    try {
        $check($request('document/download&id=' . $document->id)['status'] === 404, '缺失文件返回 404');
    } finally {
        rename($uploadPaths[1] . '.test-hold', $uploadPaths[1]);
    }
    $check($request('admin-document/delete&id=' . $document->id)['status'] === 405, 'GET 不能删除课程文件');
    $check($post('admin-document/delete&id=' . $document->id, ['confirm_delete' => '1'])['status'] === 303
        && ProjectDocument::findOne($document->id) === null && !is_file($uploadPaths[1]), '删除课程文件同时移除记录与受控文件');
    $uploadedDocumentId = null;

    $app->db->createCommand()->insert('project_document', [
        'project_team_id' => $teamId, 'member_id' => $memberId, 'document_type' => 'PERSONAL',
        'title' => $marker, 'relative_path' => 'personal/' . $marker . '.txt',
        'original_name' => $marker . '.txt', 'file_size' => 0,
    ])->execute();
    $documentId = $app->db->getLastInsertID();
    $check($post('admin-project/delete-member&id=' . $memberId, ['confirm_delete' => '1'])['status'] === 303
        && ProjectMember::findOne($memberId) !== null, '已关联个人作业的成员不能删除');
    $app->db->createCommand()->delete('project_document', ['id' => $documentId])->execute();
    $documentId = null;
    $check($post('admin-project/delete-member&id=' . $memberId, ['confirm_delete' => '1'])['status'] === 303
        && ProjectMember::findOne($memberId) === null, '可以删除未被引用的成员');
    $secondMember = ProjectMember::findOne(['project_team_id' => $teamId]);
    $post('admin-project/delete-member&id=' . $secondMember->id, ['confirm_delete' => '1']);
    $check($post('admin-project/delete&id=' . $teamId, ['confirm_delete' => '1'])['status'] === 303
        && ProjectTeam::findOne($teamId) === null, '可以删除无关联资料的团队');
    $post('auth/logout', []);
    $check($request('admin-project/index')['status'] === 302, '退出后后台重新拦截访问');
    echo 'SUCCESS: ' . $checks . ' checks passed.' . PHP_EOL;
} finally {
    // 只使用本次创建的明确 ID；不会清理初始化资料或用户自己的记录。
    if ($uploadedDocumentId !== null) {
        $uploadedDocument = ProjectDocument::findOne($uploadedDocumentId);
        if ($uploadedDocument !== null) {
            (new DocumentStorageService())->delete($uploadedDocument);
        }
    }
    foreach ($uploadPaths as $path) {
        if (is_file($path)) unlink($path);
    }
    if (is_file($uploadSource)) unlink($uploadSource);
    if ($documentId !== null) {
        $app->db->createCommand()->delete('project_document', ['id' => $documentId])->execute();
    }
    if ($teamId !== null) {
        ProjectMember::deleteAll(['project_team_id' => $teamId]);
        ProjectTeam::deleteAll(['id' => $teamId]);
    }
    if ($admin !== null && !$admin->isNewRecord) {
        $admin->delete();
    }
    curl_close($http);
}
