<?php
/**
 * WorldCup Journey - 本地一键还原入口
 * 模块：C 数据库与 D 部署；负责人：C/D，B 协助实现
 * 依赖：DatabaseSetupService、MatchDataImportService、A 的 project/init
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\commands;

use app\services\DatabaseSetupService;
use app\services\MatchDataImportService;
use app\models\AdminUser;
use app\models\ProjectMember;
use app\models\ProjectTeam;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class DeployController extends Controller
{
    /** php yii deploy/prepare，按 C 的 SQL、统一 CSV 和 A 的初始化命令依次执行。 */
    public function actionPrepare(): int
    {
        try {
            $schema = (new DatabaseSetupService())->prepare();
            $this->stdout(json_encode($schema, JSON_UNESCAPED_UNICODE) . PHP_EOL);
            $result = (new MatchDataImportService())->import(Yii::getAlias('@app/data/import'));
            $this->stdout(json_encode($result, JSON_UNESCAPED_UNICODE) . PHP_EOL);
            $profiles = new \app\services\TeamProfileService();
            $profiles->prepareSchema();
            $this->stdout(json_encode($profiles->importDefaults(), JSON_UNESCAPED_UNICODE) . PHP_EOL);
            if (Yii::$app->runAction('project/init') !== ExitCode::OK) {
                return ExitCode::DATAERR;
            }
            $this->registerDocuments();
            if (!AdminUser::find()->exists() && Yii::$app->runAction('admin/create', ['wcj_admin']) !== ExitCode::OK) {
                return ExitCode::DATAERR;
            }
            $this->stdout("数据库、赛事数据与团队资料已就绪。\n");
            return ExitCode::OK;
        } catch (Throwable $exception) {
            Yii::error(['operation' => 'deploy/prepare', 'exception' => $exception], self::class);
            $message = $exception instanceof \RuntimeException
                ? $exception->getMessage() : '数据库初始化失败，请检查连接、权限及 runtime/logs/app.log。';
            $this->stderr($message . PHP_EOL);
            return ExitCode::DATAERR;
        }
    }

    /** 按 A 的资料清单定位成员，避免假定任何数据库内部 ID。 */
    private function registerDocuments(): void
    {
        $profiles = json_decode(file_get_contents(Yii::getAlias('@app/data/project-members.json')), true, 512, JSON_THROW_ON_ERROR);
        $bundle = json_decode(file_get_contents(Yii::getAlias('@app/data/a-documents-manifest.json')), true, 512, JSON_THROW_ON_ERROR);
        $owners = array_values(array_filter($profiles, static fn ($row) => $row['code'] === $bundle['personal_owner']));
        if ((int) ProjectTeam::find()->count() !== 1 || count($owners) !== 1) {
            $this->stdout("请按现有团队归属运行 project/register-bundle 登记课程文件。\n");
            return;
        }
        $team = ProjectTeam::find()->one();
        $members = ProjectMember::find()->where(['project_team_id' => $team->id,
            'display_name' => $owners[0]['display_name'], 'role_title' => $owners[0]['role_title']])->all();
        if (count($members) !== 1) {
            $this->stdout("请按现有成员归属运行 project/register-bundle 登记课程文件。\n");
            return;
        }
        if (Yii::$app->runAction('project/register-bundle', [(int) $team->id, (int) $members[0]->id]) !== ExitCode::OK) {
            throw new \RuntimeException('课程文件登记失败。');
        }
    }
}
