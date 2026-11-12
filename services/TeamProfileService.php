<?php
/**
 * WorldCup Journey - 2026 届球队轻量档案与安全增量登记
 * 模块：A/C 资料联调；负责人：A；依赖：tournament_team.profile_json、FIFA 资料包
 * 最近修改：2026-11-12
 */
declare(strict_types=1);
namespace app\services;
use app\models\NationalTeam;
use app\models\TeamProfileForm;
use yii\db\Query;
use yii\web\NotFoundHttpException;
use Yii;

class TeamProfileService
{
    public function hasColumn(): bool
    {
        return isset(Yii::$app->db->schema->getTableSchema('tournament_team', true)->columns['profile_json']);
    }

    /** 仅由显式控制台命令升级；HTTP 请求不会修改表结构。 */
    public function prepareSchema(): void
    {
        if (!$this->hasColumn()) {
            Yii::$app->db->createCommand('ALTER TABLE {{%tournament_team}} ADD COLUMN [[profile_json]] JSON NULL')->execute();
            Yii::$app->db->schema->refreshTableSchema('tournament_team');
        }
    }

    private function participation(int $teamId): ?array
    {
        return (new Query())->select(['tt.tournament_id', 'tt.team_id'])
            ->from(['tt' => 'tournament_team'])->innerJoin(['t' => 'tournament'], '[[t.id]] = [[tt.tournament_id]]')
            ->where(['tt.team_id' => $teamId, 't.year' => 2026])->one() ?: null;
    }

    public function get(NationalTeam $team): ?array
    {
        $key = $this->participation((int) $team->id);
        if ($key === null) return null;
        $profile = null;
        if ($this->hasColumn()) {
            $value = (new Query())->select('profile_json')->from('tournament_team')->where($key)->scalar();
            if (is_string($value) && $value !== '') $profile = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            elseif (is_array($value)) $profile = $value;
        }
        if ($profile === null) $profile = $this->bundle()['teams'][$team->fifa_code] ?? null;
        if (!is_array($profile)) return null;
        $form = new TeamProfileForm(); $form->setAttributes($profile);
        if (!$form->validate()) {
            Yii::warning(['operation' => 'team-profile-read', 'team_id' => $team->id], self::class);
            return null;
        }
        return $form->profile();
    }

    public function save(int $teamId, TeamProfileForm $form): void
    {
        if (!$form->validate()) throw new \InvalidArgumentException('档案内容未通过校验');
        if (!$this->hasColumn()) throw new \RuntimeException('请先运行 php yii team-profile/prepare 升级档案字段');
        $key = $this->participation($teamId);
        if ($key === null) throw new NotFoundHttpException('该球队没有 2026 届参赛记录');
        Yii::$app->db->createCommand()->update('tournament_team',
            ['profile_json' => $form->profile()], $key)->execute();
    }

    public function importDefaults(): array
    {
        $updated = 0; $skipped = 0;
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            foreach ($this->bundle()['teams'] as $code => $profile) {
                $form = new TeamProfileForm(); $form->setAttributes($profile);
                if (!$form->validate()) throw new \RuntimeException('档案资料包校验失败：' . $code);
                $team = NationalTeam::findOne(['fifa_code' => $code]);
                $key = $team === null ? null : $this->participation((int) $team->id);
                if ($key === null) { ++$skipped; continue; }
                $count = $db->createCommand()->update('tournament_team',
                    ['profile_json' => $form->profile()],
                    ['and', $key, ['profile_json' => null]])->execute();
                $updated += $count; $skipped += (int) ($count === 0);
            }
            $transaction->commit();
        } catch (\Throwable $error) { $transaction->rollBack(); throw $error; }
        return compact('updated', 'skipped');
    }

    private function bundle(): array
    {
        return json_decode(file_get_contents(Yii::getAlias('@app/data/team-profiles-2026.json')), true, 512, JSON_THROW_ON_ERROR);
    }
}
