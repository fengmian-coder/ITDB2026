<?php
/**
 * WorldCup Journey - 初始化可在后台编辑的课程团队资料。
 * 模块：A 团队资料；负责人：A。
 * 依赖：ProjectTeam、ProjectMember、C 的 15 表结构。
 * 最近修改：2026-11-13，B 协助接入个人作业资料包。
 */
declare(strict_types=1);

namespace app\commands;

use app\models\ProjectMember;
use app\models\ProjectTeam;
use app\models\ProjectDocument;
use app\services\DocumentStorageService;
use RuntimeException;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\web\UploadedFile;

class ProjectController extends Controller
{
    /** 在其他成员电脑上登记仓库自带的课程文件；不依赖本机数据库 ID。 */
    public function actionRegisterBundle(int $teamId, int $memberId, string $manifest = 'a-documents-manifest.json'): int
    {
        if (!in_array($manifest, ['a-documents-manifest.json', 'b-documents-manifest.json'], true)) {
            throw new RuntimeException('不支持的课程资料清单。');
        }
        $bundleFile = Yii::getAlias('@app/data/' . $manifest);
        $bundle = json_decode(file_get_contents($bundleFile), true, 512, JSON_THROW_ON_ERROR);
        Yii::$app->db->transaction(static function () use ($bundle, $teamId, $memberId): void {
            foreach ($bundle['documents'] as $entry) {
                if (ProjectDocument::find()->where(['relative_path' => $entry['relative_path']])->exists()) {
                    continue;
                }
                $document = new ProjectDocument([
                    'project_team_id' => $teamId,
                    'member_id' => $entry['document_type'] === 'PERSONAL' ? $memberId : null,
                    'document_type' => $entry['document_type'], 'title' => $entry['title'],
                    'relative_path' => $entry['relative_path'], 'original_name' => $entry['original_name'],
                    'file_size' => $entry['file_size'],
                ]);
                if (!$document->validate(['project_team_id', 'member_id', 'document_type', 'title'])) {
                    throw new RuntimeException('资料归属校验失败，请核对团队及资料所属成员编号。');
                }
                $path = (new DocumentStorageService())->resolve($document);
                if (!hash_equals($entry['sha256'], hash_file('sha256', $path)) || filesize($path) !== $entry['file_size']) {
                    throw new RuntimeException('资料文件校验失败，请检查仓库文件是否完整。');
                }
                if (!$document->save(false)) {
                    throw new RuntimeException('资料记录登记失败。');
                }
            }
        });
        $this->stdout("资料包登记完成，已有记录保持不变。\n");
        return ExitCode::OK;
    }

    /** 将本机已完成的作业复制到受控目录；同标题及原文件名的记录不会重复导入。 */
    public function actionImport(string $file, string $title, int $teamId, ?int $memberId = null): int
    {
        if (!is_file($file) || !is_readable($file)) {
            $this->stderr("源文件不存在或不可读。\n");
            return ExitCode::NOINPUT;
        }
        $name = basename(str_replace('\\', '/', $file));
        if (ProjectDocument::find()->where([
            'project_team_id' => $teamId, 'member_id' => $memberId,
            'title' => $title, 'original_name' => $name,
        ])->exists()) {
            $this->stdout("已有同名作业，跳过导入。\n");
            return ExitCode::OK;
        }
        $document = new ProjectDocument([
            'project_team_id' => $teamId, 'member_id' => $memberId,
            'document_type' => $memberId === null ? 'TEAM' : 'PERSONAL', 'title' => $title,
        ]);
        $document->uploadFile = new UploadedFile([
            'name' => $name, 'tempName' => $file, 'size' => filesize($file), 'error' => UPLOAD_ERR_OK,
        ]);
        if (!(new DocumentStorageService())->save($document)) {
            $this->stderr("导入失败：" . implode('；', $document->getFirstErrors()) . "\n");
            return ExitCode::DATAERR;
        }
        $this->stdout("已复制并登记课程资料，原文件保留。\n");
        return ExitCode::OK;
    }

    /** 仅在团队表为空时初始化；已有团队及成员资料不会被覆盖。 */
    public function actionInit(): int
    {
        if (ProjectTeam::find()->exists()) {
            $this->stdout("已有团队资料，跳过初始化。\n");
            return ExitCode::OK;
        }
        Yii::$app->db->transaction(static function (): void {
            $team = new ProjectTeam([
                'name' => 'WorldCup Journey 开发小组',
                'description' => 'WorldCup Journey 是一个以 2026 年世界杯为主题的课程项目，提供球队与比赛信息查询、纪念卡制作和球迷留言等功能',
            ]);
            if (!$team->save()) {
                throw new RuntimeException('团队初始化校验失败。');
            }
            $members = json_decode(file_get_contents(Yii::getAlias('@app/data/project-members.json')),
                true, 512, JSON_THROW_ON_ERROR);
            foreach ($members as $index => $profile) {
                $member = new ProjectMember([
                    'project_team_id' => $team->id, 'display_name' => $profile['display_name'],
                    'role_title' => $profile['role_title'], 'bio' => $profile['bio'], 'sort_order' => $index + 1,
                ]);
                if (!$member->save()) {
                    throw new RuntimeException('成员初始化校验失败。');
                }
            }
        });
        $this->stdout("已初始化 1 个团队和 4 位成员。资料可继续在后台维护。\n");
        return ExitCode::OK;
    }
}
