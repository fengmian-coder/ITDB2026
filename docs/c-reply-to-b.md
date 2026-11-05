# C 对 B 两份确认单的回复

| 项目 | 内容 |
|---|---|
| 对应文档 | `docs/b-field-confirmation.md`（Q01—Q11）；`docs/b-c-interface-handoff.md`（BC01—BC08） |
| 代码基线 | `2ea356b`（C 尚无提交） |
| 状态 | 设计确认，尚未联调 |

## 0. 摘要

- C 接受 B 当前 `getMatchList()`、`getMatchDetail()`、`getStageSummary()` 的契约，不要求 B 改现有行为。
- C 的交付顺序：数据字典/DDL → 两个球队 Model → `TeamStatsService` → 48 队与参赛关系 → 104 场导入 → 空库恢复。
- 统计口径：只统计 2026 届 `FINISHED`；最终场上比分优先 120 分钟；点球不计总进球；点球胜场独立；`W+D+L=matches_played`。
- 涉及 B 的表/代码、需 B 确认的事项见 §4；未确认项不视为已冻结。
- C 不修改 B 的 `ApiController`、`WcMatch`、`views/wc-match/*`；接口变化走 `docs/api-contract.md`。
- `docs/api-contract.md` 当前不存在；建议 B/D 建立，C 在首次 PR 中补充 C 的条目。

## 1. 状态标记

| 标记 | 含义 |
|---|---|
| C 已决定 | C 负责范围内可直接落地 |
| 接受 B 现状 | C 不要求 B 改代码 |
| C 建议，待 B 确认 | 涉及 B 的表/代码，需 B 同意 |
| 待联调验证 | 需真实数据或数据库后才能确认 |

## 2. BC01—BC08 逐项回复

### BC01 正式数据库版本与 DDL

- C 统一维护 `docs/data-dictionary.md` 与 `data/install.sql`；首次提交后补充分支/提交号。
- 全局约定：
  - 数据库 `worldcup_journey`；InnoDB；`utf8mb4` / `utf8mb4_0900_ai_ci`；
  - 主键/外键统一 `BIGINT UNSIGNED`；普通表自增，关联表使用联合主键；
  - 时间按 UTC 存 `DATETIME`，页面转 `Asia/Shanghai`；
  - 历史外键 `RESTRICT`；仅 `match_story_tag.story_id` 建议 `CASCADE`（见 Q06）。
- 15 张表由 C 合并进 `install.sql`；B/A/D 的表业务字段分别由各负责人确认，C 只统一类型、外键、索引与字符集。

### BC02 两个 C Model、球队与本届资格

- 类与命名空间：`app\models\NationalTeam`、`app\models\TournamentTeam`；后者联合主键 `(tournament_id, team_id)`。
- 两者均继承 `yii\db\ActiveRecord`，支持标准 `find()`/`findOne()`。
- 关系：
  - `NationalTeam::getTournamentTeams()`；
  - `TournamentTeam::getTeam()`、`getTournament()`。
- 资格判断：先按 `Tournament.year = 2026` 取赛事，再用 `tournament_team` 判断是否参赛；不能只看 `national_team` 是否存在。
- 异常与停用：
  - 非法 ID → 400；
  - 球队不存在 → 404；
  - 存在但未参加 2026 → 404，消息明确；
  - `is_active = 0` 保留历史关联与按 ID 读取；C 的公开列表/搜索默认只展示启用球队，后台可见全部。
- `final_result_code` 只表示最终成绩类别，不是官方逐名次排名；无可靠结论保持 `NULL`。

### BC03 事件球队、枚举与排序

- `match_event.team_id` 非空时，C 建议必须是本场双方之一；该表属 B，C 可在导入程序/后台校验中协助。
- 枚举冻结建议：`period_code` 为 `H1/H2/ET1/ET2/SHOOTOUT`；`event_type` 为 `GOAL/OWN_GOAL/YELLOW/RED/PENALTY_GOAL/PENALTY_MISS/OTHER`。
- `minute` 为基础分钟，非 `SHOOTOUT` 必填；无补时 `added_minute` 用 `NULL`；`SHOOTOUT` 时分钟可空。
- 排序接受 B 当前 `sort_order ASC, id ASC`；C 的统计只读 `wc_match` 比分，不依赖事件。

### BC04 名称、简称与素材

- 展示名以 `name_zh` 为主，为空时回退 `name_en`。
- 不新增 `short_name`/`abbreviation`/`logo_url`；三字母简称使用 `fifa_code`。
- `flag_path` 存相对 `web/` 的本地路径，建议 `images/flags/xxx.svg`；C 只存路径，不存外链。
- 缺图显示名称/占位，不伪造队徽；素材来源与版权由 A 协调。

### BC05 是否接受 B 当前查询契约

- 接受 B 当前列表/详情：键名、`items` + `pagination`、`int|string` ID、UTC `Y-m-d H:i:s`、空集返回、超页 400、缺失阶段不补行、阶段顺序 `GROUP → FINAL`。
- 建议 `page_size` 增加上限（如 100）；是否修改由 B 决定，C 不擅自加限制。
- C 的 `insight/index` 图③服务端直接调用 `getStageSummary()`；如需异步刷新再使用 `api/insights`。

### BC06 TeamStatsService

- 类：`app\services\TeamStatsService`；实例方法：
  - `getTeamStats(int $teamId): array`
  - `getCompareStats(int $leftId, int $rightId): array`
  - `getScoringRanking(): array`
- 核心规则：
  - 只统计 2026 届 `FINISHED`；
  - 最终场上比分 `COALESCE(score_120, score_90)`；点球不计总进球；
  - 场上打平后点球获胜计 `draws`，另计 `penalty_wins`，`wins` 不增加；
  - `W+D+L = matches_played`；分母为 0 的场均/胜率返回 `null`。
- 异常：不存在球队 404；未参加 2026 404；同队比较 400；无完赛记录返回场次/进失球 0、比率 `null`。
- `getScoringRanking()` 返回 2026 全部 48 支参赛队的完整统计，按 `goals_for DESC, fifa_code ASC, team_id ASC` 排序。
- Service 返回业务数组，不包 `success/data/error`；由 B 的 `ApiController` 统一包装。C 不设跨请求缓存。
- 字段与类型见 §5.4。

### BC07 JourneyService 联调契约

- C 不实现 `JourneyService`，由 B 负责。
- C 提供球队 `id`、`fifa_code`、`name_zh`、`name_en`、`flag_path` 与 `final_result_code`。
- C 接受 B 的 `JourneyItem[]` 结构及 B 决定的时间格式。
- `is_featured` 建议仅在存在已发布故事时为 `true`；最终由 B 定义。

### BC08 导入与联调交付

- B 将源数据放在 `data/import/`，UTF-8 CSV，球队用 `fifa_code`、场馆用官方名称作映射键，不写内部自增 ID；格式见 §5.5。
- B 负责 104 场、16 场馆、5—8 场故事/事件/标签及来源复核。
- C 负责 48 队、48 条参赛关系、导入程序、ID 映射、最终 `install.sql`、计数校验。
- 顺序：B 交付源数据 → C 导入并校验 → D 独立复核与空库恢复。
- 校验至少包括：48 队、12 组 × 4 队、104 场（72 小组赛 + 32 淘汰赛）、每队至少 3 场小组赛、16 场馆、5—8 场已发布故事、所有引用可解析、比分/点球/winner 合规。
- 来源、映射、复核人写入 `docs/data-sources.md`；测试数据与真实数据分开。

## 3. Q01—Q11 逐项回复

| 编号 | C 的结论 | 说明/待确认 |
|---|---|---|
| Q01 | `status='SCHEDULED'`、`sort_order=0`、`is_published=0`、`is_active=1`；可空字段用 `NULL` 表示缺失，不用空串；比分/点球/`winner_team_id` 不设默认值 | 审计字段默认值见 Q03 |
| Q02 | 所有 PK/FK `BIGINT UNSIGNED`；普通表自增；`tournament_team`、`match_story_tag` 联合主键；以 C 的 `install.sql` 为准 | 需 B 确认 B 七表外键列类型与 C 的目标列一致 |
| Q03 | 建议 15 表统一 `created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP`、`updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | B 当前仅 `match_story` 明确列审计字段；**需 B 确认** |
| Q04 | UQ：`tournament.year`、`national_team.fifa_code`、`wc_match(tournament_id,match_no)`、`match_story.match_id`、`match_tag.code`；PK：`tournament_team(tournament_id,team_id)`、`match_story_tag(story_id,tag_id)`；建议 `match_event(match_id,sort_order)`、`match_story_tag(tag_id,story_id)`、`national_team(name_zh)`；不给 `stadium.name`、`match_tag.name` 加 UQ | B 七表索引需 B 确认 |
| Q05 | DB：FK、UQ、CHECK（阶段/状态/比分成对/120≥90/点球成对且分胜负/小组赛 `group_code`/`winner_team_id` 属于双方）；应用层：双方不同、双方属于该赛事、事件球队属于双方、状态流转、故事发布 | 跨行规则由 Model/Service/导入共同保证 |
| Q06 | 历史 FK 一律 `RESTRICT`；`match_story_tag.story_id` 建议 `CASCADE`，`tag_id` 建议 `RESTRICT`；`match_event`/`match_story` 的 `match_id` 用 `RESTRICT`；不级联删比赛；可空不自动等于 `SET NULL` | 逐 FK 清单需 B 确认 |
| Q07 | `period_code`/`event_type` 按 BC03 集合冻结为应用层白名单；`minute` 非 `SHOOTOUT` 必填；无补时 `added_minute=NULL`；排序沿用 B 当前 `sort_order,id` | 是否加 DB CHECK 由 B 决定 |
| Q08 | 正文按纯文本存储/输出；若用 HTML 必须白名单净化；`cover_path` 相对 `web/`；`is_featured` 建议=已发布故事 | 素材根目录/版权由 A/B 确认 |
| Q09 | 接受 B 当前列表/详情结构；C 不新增字段、不改包装 | B 后续新增字段需通知 C |
| Q10 | 接受 B 当前阶段统计：只返回有有效赛程的阶段、固定顺序、空返回 `[]`、无完赛 `total_goals=null` | C 的图表显示空状态，不补造行 |
| Q11 | 首次提交后补充分支/提交号、字典/DDL 路径、Model/Service 版本；时间统一 UTC 存储、页面转 `Asia/Shanghai`；实际自增 ID 用 `int`，超大 ID 按 B 的 `int|string` 约定处理 | 不承诺支持超出 PHP 整数范围的自增 ID |

## 4. 需要 B 确认或共同冻结的事项

| 编号 | 事项 | C 的建议 | 若 B 不同意 |
|---|---|---|---|
| 1 | 审计字段是否 15 表统一（Q03） | 统一加 `created_at/updated_at`，DB 默认值维护 | C 按 B 的逐表清单执行 |
| 2 | B 七表 FK 删除/更新规则（Q06） | 历史 `RESTRICT`；仅 `match_story_tag.story_id` `CASCADE` | B 给出逐 FK 规则，C 照改 |
| 3 | B 七表 CHECK 约束（Q05/Q07） | DB 加可表达的 CHECK；跨行规则放 Model/Service | B 指定哪些不加，C 保留应用层校验 |
| 4 | 事件枚举是否 DB CHECK（Q07） | DB CHECK + 应用层白名单 | 改为仅应用层白名单 |
| 5 | `getMatchList` 的 `page_size` 上限（BC05） | 建议上限 100 | 保持现状；C 页面固定 20 |
| 6 | `match_event.team_id` 必须属于本场双方（BC03） | 建议是 | B 说明允许范围，C 按 B 的规则导入 |
| 7 | 非参赛球队 404、同队比较 400（BC02/BC06） | 采用 404/400，不返回全 0 假统计 | B 给出替代语义，C 统一实现 |
| 8 | `getScoringRanking()` 返回完整 TeamStats + 并列排序（BC06） | 返回完整统计；`goals_for DESC, fifa_code ASC, team_id ASC` | B 指定简表键与排序，C 调整 |
| 9 | 源数据格式与交付顺序（BC08） | 按 §5.5 格式，B 先交付，C 再导入 | B 提出可行格式，C 调整导入程序 |
| 10 | `docs/api-contract.md` 的建立与维护 | B/D 建立；C 首次 PR 补 C 条目 | 由 D 指定其他共享位置 |

## 5. 支撑细节

### 5.1 全局 DDL 约定

| 项目 | 约定 |
|---|---|
| 数据库 | `worldcup_journey` |
| 引擎 | InnoDB |
| 字符集/排序 | `utf8mb4` / `utf8mb4_0900_ai_ci` |
| 主键 | 单列 `BIGINT UNSIGNED AUTO_INCREMENT`；关联表联合主键 |
| 外键 | 同类型 `BIGINT UNSIGNED`；显式命名；历史 `RESTRICT` |
| 时间 | `DATETIME`，UTC；页面转 `Asia/Shanghai` |
| 审计 | `created_at`/`updated_at`（待 Q03 确认） |
| 删除 | 禁止级联清空比赛历史；仅关联表允许必要 `CASCADE` |

### 5.2 C 的两张表 DDL（拟稿）

```sql
CREATE TABLE national_team (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    fifa_code     CHAR(3)         NOT NULL,
    name_zh       VARCHAR(80)     NOT NULL,
    name_en       VARCHAR(100)    NOT NULL,
    flag_path     VARCHAR(255)    NULL,
    description   TEXT            NULL,
    is_active     TINYINT(1)      NOT NULL DEFAULT 1,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_national_team_fifa_code (fifa_code),
    KEY idx_national_team_name_zh (name_zh),
    CONSTRAINT chk_national_team_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

```sql
CREATE TABLE tournament_team (
    tournament_id      BIGINT UNSIGNED NOT NULL,
    team_id            BIGINT UNSIGNED NOT NULL,
    group_code         CHAR(1)         NOT NULL,
    final_result_code  VARCHAR(20)     NULL,
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (tournament_id, team_id),
    KEY idx_tournament_team_team (team_id, tournament_id),
    CONSTRAINT fk_tournament_team_tournament FOREIGN KEY (tournament_id)
        REFERENCES tournament (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_tournament_team_team FOREIGN KEY (team_id)
        REFERENCES national_team (id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_tournament_team_group CHECK (group_code BETWEEN 'A' AND 'L'),
    CONSTRAINT chk_tournament_team_result CHECK (
        final_result_code IS NULL OR final_result_code IN
        ('GROUP','R32','R16','QF','FOURTH','THIRD','RUNNER_UP','CHAMPION')
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
```

`created_at/updated_at` 按 Q03 建议加入；若 B 不同意全表统一，C 从本表删除或在字典中注明例外。

### 5.3 Model 契约

```php
namespace app\models;

class NationalTeam extends ActiveRecord
{
    public const ACTIVE = 1;
    public const INACTIVE = 0;

    public static function tableName(): string; // 'national_team'
    public function rules(): array;             // fifa_code UQ/长度；name_zh/name_en 必填；is_active 0/1
    public function getTournamentTeams(): ActiveQuery;
    public static function findActive(): ActiveQuery; // is_active = 1
}
```

```php
namespace app\models;

class TournamentTeam extends ActiveRecord
{
    public const FINAL_RESULT_CODES = [
        'GROUP', 'R32', 'R16', 'QF', 'FOURTH', 'THIRD', 'RUNNER_UP', 'CHAMPION',
    ];

    public static function tableName(): string; // 'tournament_team'
    public static function primaryKey(): array;  // ['tournament_id', 'team_id']
    public function rules(): array;              // group_code A—L；final_result_code 枚举/可空
    public function getTeam(): ActiveQuery;
    public function getTournament(): ActiveQuery;
}
```

B 的使用方式：

```php
$team = NationalTeam::findOne($teamId);
$isParticipant = TournamentTeam::find()
    ->where(['tournament_id' => $tournamentId, 'team_id' => $teamId])
    ->exists();
```

### 5.4 TeamStatsService 契约

**签名：**

```php
namespace app\services;

class TeamStatsService
{
    public function getTeamStats(int $teamId): array;
    public function getCompareStats(int $leftId, int $rightId): array;
    public function getScoringRanking(): array;
}
```

**单队返回：**

| 键 | 类型 | 规则 |
|---|---|---|
| `team_id` | int | `national_team.id` |
| `team_name` | string | `name_zh` |
| `fifa_code` | string | 三字母代码 |
| `matches_played` | int | 2026 届 `FINISHED` 场数 |
| `wins` | int | 最终场上比分领先场数 |
| `draws` | int | 最终场上比分相等场数（含点球大战前的平局） |
| `losses` | int | 最终场上比分落后场数 |
| `goals_for` | int | 本队最终场上进球合计；不含点球 |
| `goals_against` | int | 对手最终场上进球合计；不含点球 |
| `goals_per_match` | float\|null | 分母 0 时 `null` |
| `goals_against_per_match` | float\|null | 分母 0 时 `null` |
| `win_rate` | float\|null | 0—1；分母 0 时 `null` |
| `penalty_wins` | int | 点球大战获胜场数，独立于 `wins` |
| `final_result_code` | string\|null | 来自 `tournament_team` |

**比较：** `['left' => TeamStats, 'right' => TeamStats]`，顺序与输入一致；同队 → 400。

**排名：** 48 支参赛队，每项与 `getTeamStats()` 同结构；按 `goals_for DESC, fifa_code ASC, team_id ASC`；0 场球队也返回；一次聚合查询，避免 N+1；不设跨请求缓存。

**异常：**

| 场景 | 行为 |
|---|---|
| 非法 ID | 400 |
| 球队不存在 | 404 |
| 存在但未参加 2026 | 404，消息明确 |
| 2026 赛事未初始化 | 500，记录日志，不返回全 0 |
| 无完赛记录 | 场次/进失球 0；场均/胜率 `null`；`final_result_code` 可 `null` |
| `FINISHED` 缺 90 分钟比分 | 记录日志并抛 500；导入前校验应阻止 |
| 数据库异常 | 记录日志并抛 500，不返回 SQL/路径/密码 |

### 5.5 数据导入接口（B → C）

目录 `data/import/`；UTF-8 CSV；首行表头；不写内部自增 ID。

| 文件 | 建议列 |
|---|---|
| `teams.csv` | `fifa_code,name_zh,name_en,group_code,final_result_code`（C 整理，B 核对） |
| `stadiums.csv` | `name,city,country_code,timezone_name`（官方名称作映射键） |
| `matches.csv` | `match_no,home_fifa_code,away_fifa_code,stadium_name,kickoff_utc,stage_code,group_code,status,home_score_90,away_score_90,home_score_120,away_score_120,home_penalties,away_penalties,winner_fifa_code,summary` |
| `stories.csv` | `match_no,title,summary,body,cover_path,source_url,is_published` |
| `events.csv` | `match_no,team_fifa_code,player_name,minute,added_minute,period_code,event_type,description,sort_order` |
| `tags.csv` | `code,name` |
| `story_tags.csv` | `match_no,tag_code` |

`kickoff_utc` 建议 ISO 8601 UTC（`YYYY-MM-DDTHH:MM:SSZ`），C 转为 DB 的 `Y-m-d H:i:s`；空值留空。任何校验失败整批导入失败并输出行号/原因，不静默跳过。

### 5.6 错误、空值、时区与 ID 约定

| 项目 | 约定 |
|---|---|
| 错误类型 | 400 `BadRequestHttpException`；404 `NotFoundHttpException`；500 `ServerErrorHttpException` |
| 空值 | “无数据”用 `null`；“真实 0”用 `0`；页面用 `matches_played` 判断显示“暂无” |
| 时区 | 数据库存 UTC；页面转 `Asia/Shanghai` 并标注北京时间 |
| ID | 实际自增 ID 用 `int`；B 的 `int|string` 超大 ID 约定用于 B 的查询层 |
| 安全 | 用户文本 `Html::encode()`；不拼 SQL 参数；错误不返回敏感信息 |

## 6. C 的实施顺序

1. 数据字典、ER 图、`data/install.sql` 结构。
2. `NationalTeam`、`TournamentTeam`。
3. `TeamStatsService` 与单元测试。
4. 48 队 + 48 条参赛关系。
5. 104 场、16 场馆、5—8 场故事导入。
6. 与 D 做空库恢复 T01。

## 7. 变更流程

1. 字段、路由、方法签名或统计口径变化：先更新 `docs/api-contract.md` 或 `docs/data-dictionary.md`。
2. 在 PR/任务卡说明影响 B/A/D 的范围。
3. 四方确认后，代码与测试同批提交。
4. 不允许只改一端后要求其他人猜新格式；不允许 C 直接改 B 的 `ApiController`/`WcMatch`/视图，反之亦然。
