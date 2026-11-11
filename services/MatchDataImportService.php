<?php
/**
 * WorldCup Journey - 2026 届 CSV 事务导入与业务键映射
 * 模块：B；负责人：B；依赖：C 的 install.sql、B Model、Yii DB
 * 最近修改：2026-11-11
 */
declare(strict_types=1);

namespace app\services;

use app\models\MatchEvent;
use app\models\MatchStory;
use app\models\MatchStoryTag;
use app\models\MatchTag;
use app\models\Stadium;
use app\models\Tournament;
use app\models\WcMatch;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Query;

class MatchDataImportService
{
    private const HEADERS = [
        'tournament' => ['year', 'name', 'start_date', 'end_date', 'description'],
        'teams' => ['fifa_code', 'name_zh', 'name_en', 'group_code', 'final_result_code'],
        'stadiums' => ['name', 'city', 'country_code', 'timezone_name'],
        'matches' => ['match_no', 'home_fifa_code', 'away_fifa_code', 'stadium_name', 'kickoff_utc',
            'stage_code', 'group_code', 'status', 'home_score_90', 'away_score_90', 'home_score_120',
            'away_score_120', 'home_penalties', 'away_penalties', 'winner_fifa_code', 'summary'],
        'stories' => ['match_no', 'title', 'summary', 'body', 'cover_path', 'source_url', 'is_published'],
        'events' => ['match_no', 'team_fifa_code', 'player_name', 'minute', 'added_minute', 'period_code',
            'event_type', 'description', 'sort_order'],
        'tags' => ['code', 'name'],
        'story_tags' => ['match_no', 'tag_code'],
    ];
    private const NUMBERS = [
        'year' => [2026, 2026], 'match_no' => [1, 104], 'minute' => [0, 65535],
        'added_minute' => [0, 65535], 'sort_order' => [-2147483648, 2147483647],
        'home_score_90' => [0, 255], 'away_score_90' => [0, 255],
        'home_score_120' => [0, 255], 'away_score_120' => [0, 255],
        'home_penalties' => [0, 255], 'away_penalties' => [0, 255], 'is_published' => [0, 0],
    ];
    private array $counts = [];
    private string $context = '';

    /** 同值记录跳过，内容冲突整批回滚。dryRun 同样实际验证全部写入，再回滚。 */
    public function import(string $directory, bool $dryRun = false): array
    {
        $this->counts = [];
        $data = [];
        foreach (self::HEADERS as $file => $header) {
            $data[$file] = $this->readCsv($directory . '/' . $file . '.csv', $header);
        }
        foreach (['tournament' => 1, 'teams' => 48, 'stadiums' => 16, 'matches' => 104] as $file => $size) {
            if (count($data[$file]) !== $size) {
                throw new RuntimeException($file . '.csv 记录数应为 ' . $size);
            }
        }
        if (count($data['stories']) < 5 || count($data['stories']) > 8) {
            throw new RuntimeException('精选故事应为 5—8 场');
        }
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $this->context = 'tournament.csv 记录 1';
            $tournament = $this->store('tournament', $data['tournament'][0], ['year' => 2026], Tournament::class);
            $teamIds = [];
            $groups = [];
            foreach ($data['teams'] as $index => $row) {
                $this->context = 'teams.csv 记录 ' . ($index + 1);
                $code = $row['fifa_code'];
                if (!is_string($code) || !preg_match('/^[A-Z]{3}$/D', $code) || isset($teamIds[$code])) {
                    throw new RuntimeException('球队代码缺失、无效或重复');
                }
                $team = $this->store('national_team', array_intersect_key($row,
                    array_flip(['fifa_code', 'name_zh', 'name_en'])), ['fifa_code' => $code]);
                $teamIds[$code] = $team['id'];
                $group = $row['group_code'];
                if (!in_array($group, range('A', 'L'), true)) {
                    throw new RuntimeException('参赛分组必须为 A—L');
                }
                $groups[$group] = ($groups[$group] ?? 0) + 1;
                $participation = ['tournament_id' => $tournament['id'], 'team_id' => $team['id'], 'group_code' => $group];
                if ($row['final_result_code'] !== null) {
                    $participation['final_result_code'] = $row['final_result_code'];
                }
                $this->store('tournament_team', $participation, array_intersect_key($participation,
                    array_flip(['tournament_id', 'team_id'])));
            }
            if (count($groups) !== 12 || array_values(array_unique($groups)) !== [4]) {
                throw new RuntimeException('必须为 12 组，每组 4 队');
            }
            $stadiumIds = [];
            foreach ($data['stadiums'] as $index => $row) {
                $this->context = 'stadiums.csv 记录 ' . ($index + 1);
                $this->uniqueKey($stadiumIds, $row['name']);
                $stadium = $this->store('stadium', $row, ['name' => $row['name']], Stadium::class);
                $stadiumIds[$row['name']] = $stadium['id'];
            }
            $matches = [];
            foreach ($data['matches'] as $index => $row) {
                $this->context = 'matches.csv 记录 ' . ($index + 1);
                $number = $row['match_no'];
                $this->uniqueKey($matches, $number);
                $attributes = array_diff_key($row, array_flip(['home_fifa_code', 'away_fifa_code', 'winner_fifa_code', 'stadium_name']));
                $attributes['tournament_id'] = $tournament['id'];
                $attributes['home_team_id'] = $this->resolve($teamIds, $row['home_fifa_code'], '主队');
                $attributes['away_team_id'] = $this->resolve($teamIds, $row['away_fifa_code'], '客队');
                $attributes['winner_team_id'] = $row['winner_fifa_code'] === null ? null
                    : $this->resolve($teamIds, $row['winner_fifa_code'], '获胜方');
                $attributes['stadium_id'] = $this->resolve($stadiumIds, $row['stadium_name'], '场馆');
                $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', (string) $row['kickoff_utc'], new DateTimeZone('UTC'));
                if ($time === false || $time->format('Y-m-d\TH:i:s\Z') !== $row['kickoff_utc']) {
                    throw new RuntimeException('kickoff_utc 必须为 ISO 8601 UTC 时间');
                }
                $attributes['kickoff_utc'] = $time->format('Y-m-d H:i:s');
                $matches[$number] = $this->store('wc_match', $attributes,
                    ['tournament_id' => $tournament['id'], 'match_no' => $number], WcMatch::class);
            }
            $stories = [];
            foreach ($data['stories'] as $index => $row) {
                $this->context = 'stories.csv 记录 ' . ($index + 1);
                $number = $row['match_no'];
                $this->uniqueKey($stories, $number);
                $attributes = array_diff_key($row, ['match_no' => true]);
                $attributes['match_id'] = $this->resolve($matches, $number, '故事所属比赛')['id'];
                $stories[$number] = $this->store('match_story', $attributes,
                    ['match_id' => $attributes['match_id']], MatchStory::class, ['is_published', 'cover_path']);
            }
            $tags = [];
            foreach ($data['tags'] as $index => $row) {
                $this->context = 'tags.csv 记录 ' . ($index + 1);
                $this->uniqueKey($tags, $row['code']);
                $tags[$row['code']] = $this->store('match_tag', $row, ['code' => $row['code']], MatchTag::class)['id'];
            }
            $links = [];
            foreach ($data['story_tags'] as $index => $row) {
                $this->context = 'story_tags.csv 记录 ' . ($index + 1);
                $key = $row['match_no'] . ':' . $row['tag_code'];
                $this->uniqueKey($links, $key);
                $links[$key] = true;
                $attributes = ['story_id' => $this->resolve($stories, $row['match_no'], '故事')['id'],
                    'tag_id' => $this->resolve($tags, $row['tag_code'], '标签')];
                $this->store('match_story_tag', $attributes, $attributes, MatchStoryTag::class);
            }
            $events = [];
            $eventRecordNumbers = [];
            foreach ($data['events'] as $index => $row) {
                $this->context = 'events.csv 记录 ' . ($index + 1);
                $match = $this->resolve($matches, $row['match_no'], '事件所属比赛');
                $this->resolve($stories, $row['match_no'], '事件所属精选故事');
                $attributes = array_diff_key($row, ['match_no' => true, 'team_fifa_code' => true]);
                $attributes['match_id'] = $match['id'];
                $attributes['team_id'] = $row['team_fifa_code'] === null ? null
                    : $this->resolve($teamIds, $row['team_fifa_code'], '事件球队');
                if ($attributes['team_id'] !== null && !in_array((string) $attributes['team_id'],
                    [(string) $match['home_team_id'], (string) $match['away_team_id']], true)) {
                    throw new RuntimeException('事件球队必须属于本场双方');
                }
                $events[$match['id']][] = $attributes;
                $eventRecordNumbers[$match['id']][] = $index + 1;
            }
            foreach ($events as $matchId => $rows) {
                $this->context = 'events.csv 比赛 ID ' . $matchId;
                $existing = (new Query())->from('match_event')->select(array_keys($rows[0]))
                    ->where(['match_id' => $matchId])->all($db);
                // sort_order 允许并列。按全部业务列的多重集合核对，保留重复事件的数量。
                if ($existing !== [] && $this->eventSet($existing) !== $this->eventSet($rows)) {
                    throw new RuntimeException('已有事件与 CSV 内容不同，请先核对后台编辑记录');
                }
                foreach ($rows as $position => $row) {
                    $this->context = 'events.csv 记录 ' . $eventRecordNumbers[$matchId][$position];
                    if ($existing !== []) {
                        $this->count('match_event', 'skipped');
                    } else {
                        $model = new MatchEvent();
                        $model->setAttributes($row);
                        if (!$model->save()) {
                            throw new RuntimeException(json_encode($model->getFirstErrors(), JSON_UNESCAPED_UNICODE));
                        }
                        $this->count('match_event', 'inserted');
                    }
                }
            }
            if ($dryRun) {
                $transaction->rollBack();
            } else {
                $transaction->commit();
            }
            return ['dry_run' => $dryRun, 'tables' => $this->counts,
                'source_rows' => array_map('count', $data)];
        } catch (Throwable $exception) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            Yii::error(['context' => $this->context, 'exception' => $exception], self::class);
            $reason = $exception instanceof RuntimeException ? $exception->getMessage() : '数据库约束或写入失败，请查看服务端日志';
            throw new RuntimeException($this->context . '：' . $reason . '，本批业务写入已回滚', 0, $exception);
        }
    }

    private function store(string $table, array $attributes, array $key, ?string $modelClass = null, array $preserve = []): array
    {
        $rows = (new Query())->from($table)->where($key)->limit(2)->all(Yii::$app->db);
        if (count($rows) > 1) {
            throw new RuntimeException($table . ' 业务键对应多条记录');
        }
        if ($rows !== []) {
            $existing = $rows[0];
            foreach ($attributes as $column => $value) {
                if (in_array($column, $preserve, true)) {
                    continue;
                }
                if (($value === null) !== ($existing[$column] === null)
                    || ($value !== null && (string) $value !== (string) $existing[$column])) {
                    throw new RuntimeException($table . '.' . $column . ' 已存在且与 CSV 不同');
                }
            }
            $this->count($table, 'skipped');
            return $existing;
        }
        if ($modelClass !== null) {
            $model = new $modelClass();
            $model->setAttributes($attributes);
            if (!$model->save()) {
                throw new RuntimeException(json_encode($model->getFirstErrors(), JSON_UNESCAPED_UNICODE));
            }
            $result = $model->getAttributes();
        } else {
            Yii::$app->db->createCommand()->insert($table, $attributes)->execute();
            $result = $attributes;
            if ($table === 'national_team') {
                $result['id'] = Yii::$app->db->getLastInsertID();
            }
        }
        $this->count($table, 'inserted');
        return $result;
    }

    private function readCsv(string $path, array $header): array
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('无法读取 ' . basename($path));
        }
        try {
            if (fgetcsv($handle, 0, ',', '"', '') !== $header) {
                throw new RuntimeException(basename($path) . ' 表头与导入契约不符');
            }
            $rows = [];
            while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($values === [null]) {
                    continue;
                }
                $context = basename($path) . ' 记录 ' . (count($rows) + 1);
                if (count($values) !== count($header) || !mb_check_encoding(implode('', $values), 'UTF-8')) {
                    throw new RuntimeException($context . ' 列数或 UTF-8 编码错误');
                }
                $row = array_combine($header, array_map(static fn ($value) => $value === '' ? null : $value, $values));
                foreach (self::NUMBERS as $field => [$min, $max]) {
                    if (!isset($row[$field])) {
                        continue;
                    }
                    $value = $row[$field];
                    if (!preg_match('/^-?[0-9]+$/D', $value) || strlen(ltrim($value, '-0')) > 10
                        || (int) $value < $min || (int) $value > $max) {
                        throw new RuntimeException($context . ' 的 ' . $field . ' 数值无效');
                    }
                    $row[$field] = (int) $value;
                }
                $rows[] = $row;
            }
            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function resolve(array $map, mixed $key, string $name): mixed
    {
        if ($key === null || !array_key_exists($key, $map)) {
            throw new RuntimeException($name . ' 未找到映射');
        }
        return $map[$key];
    }

    private function uniqueKey(array $map, mixed $key): void
    {
        if ($key === null || array_key_exists($key, $map)) {
            throw new RuntimeException('业务键缺失或重复');
        }
    }

    private function count(string $table, string $operation): void
    {
        $this->counts[$table] ??= ['inserted' => 0, 'skipped' => 0];
        ++$this->counts[$table][$operation];
    }

    private function eventSet(array $rows): array
    {
        $set = [];
        foreach ($rows as $row) {
            ksort($row);
            $set[] = json_encode(array_map(static fn ($value) => $value === null ? null : (string) $value, $row));
        }
        sort($set);
        return $set;
    }
}
