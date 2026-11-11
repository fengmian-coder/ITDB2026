<?php
/**
 * WorldCup Journey - 晋级流向只读聚合
 * 模块：B/C；负责人：B 协助 C；依赖：现有赛事、参赛与比赛 Model
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\services;

use app\models\Tournament;
use app\models\TournamentTeam;
use app\models\WcMatch;
use Yii;
use yii\web\ServerErrorHttpException;

/** 阶段节点统计球队数，同队同阶段只计一次，不根据未来赛程推断晋级。 */
class JourneyFlowService
{
    private const STAGES = ['GROUP' => '小组赛', 'R32' => '32 强赛', 'R16' => '16 强赛',
        'QF' => '四分之一决赛', 'SF' => '半决赛', 'THIRD' => '季军赛', 'FINAL' => '决赛'];
    private const DEPTHS = ['GROUP' => 1, 'R32' => 2, 'R16' => 3, 'QF' => 4,
        'SF' => 5, 'THIRD' => 6, 'FINAL' => 6];

    /**
     * @return array{nodes: array, links: array, teams: array}
     * nodes 包含 name/label/depth，links 包含 source/target/value/team_ids。
     * teams 包含 team_id/path/final_result_code，path 为实际已完赛阶段及成绩节点。
     */
    public function getFlow(): array
    {
        try {
            $tournament = Tournament::find()->select('id')->where(['year' => 2026])->scalar();
            if ($tournament === false) {
                throw new ServerErrorHttpException('2026 届赛事尚未初始化');
            }
            $teams = TournamentTeam::find()->select(['team_id', 'final_result_code'])
                ->where(['tournament_id' => $tournament])->orderBy(['team_id' => SORT_ASC])->asArray()->all();
            $matches = WcMatch::find()->select(['home_team_id', 'away_team_id', 'stage_code'])
                ->where(['tournament_id' => $tournament, 'status' => 'FINISHED'])->asArray()->all();
            $played = [];
            foreach ($matches as $match) {
                foreach (['home_team_id', 'away_team_id'] as $side) {
                    $played[$match[$side]][$match['stage_code']] = true;
                }
            }
            $nodes = [];
            $links = [];
            $paths = [];
            foreach ($teams as $team) {
                $id = $team['team_id'];
                $path = ['entry'];
                $nodes['entry'] = ['name' => 'entry', 'label' => '本届参赛', 'depth' => 0];
                $depth = 0;
                foreach (self::STAGES as $code => $label) {
                    if (isset($played[$id][$code])) {
                        $path[] = $code;
                        $depth = self::DEPTHS[$code];
                        $nodes[$code] = ['name' => $code, 'label' => $label, 'depth' => $depth];
                    }
                }
                $result = $team['final_result_code'];
                $last = end($path);
                $terminal = $result === null ? 'pending:' . $last : 'result:' . $result;
                $resultLabel = TournamentTeam::FINAL_RESULT_LABELS[$result] ?? null;
                $nodes[$terminal] = ['name' => $terminal,
                    'label' => $resultLabel === null ? ($depth === 0 ? '等待比赛' : '成绩待定')
                        : (in_array($result, ['GROUP', 'R32', 'R16', 'QF'], true) ? '止步' . $resultLabel : $resultLabel),
                    'depth' => max($nodes[$terminal]['depth'] ?? 0, $depth + 1)];
                $path[] = $terminal;
                for ($i = 1; $i < count($path); ++$i) {
                    $key = $path[$i - 1] . '>' . $path[$i];
                    $links[$key] ??= ['source' => $path[$i - 1], 'target' => $path[$i], 'value' => 0, 'team_ids' => []];
                    ++$links[$key]['value'];
                    $links[$key]['team_ids'][] = $id;
                }
                $paths[] = ['team_id' => $id, 'path' => $path, 'final_result_code' => $result];
            }
            return ['nodes' => array_values($nodes), 'links' => array_values($links), 'teams' => $paths];
        } catch (\yii\db\Exception $exception) {
            Yii::error(['operation' => __FUNCTION__, 'exception' => $exception], self::class);
            throw new ServerErrorHttpException('晋级流向暂时不可用，请稍后重试', 0, $exception);
        }
    }
}
