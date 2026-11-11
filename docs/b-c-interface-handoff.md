# WorldCup Journey：B / C 模块阶段性接口交接文档

| 项目 | 内容 |
|---|---|
| 整理日期 | 2026-11-11 |
| 提供方 / 核对方 | B（赛事与比赛后端）/ C（球队、统计及正式数据库） |
| 当前分支 / 核对代码版本 | `feature/b-match`，本轮 PK 与图表提交见 Pull Request |
| C 交付版本 | `C` 分支 `c45f159`，已合入当前 B 分支 |
| 文档状态 | B/C 基础代码已通过 PR #3 合并到 main 的 99fe78d，本轮增加球队 PK、图表和已发布故事数据包 |
| 本次范围 | 球队资料、参赛资格、比赛与历程页面、统计 API、后台校验及数据导入回归 |

## 1. 依据与状态标记

主要依据：

- [V1.1 技术基线](../WorldCup_Journey_项目开发协作规范与接口约定_V1.1.md)：第 3 节字段及数据导入、第 4 节统计、第 5 节接口、第 6 节分工。
- [B 字段确认单](b-field-confirmation.md)：仍为待 B/C 确认稿，Q01—Q11 未因本文件产生而自动关闭。
- 当前 B 模块 Model、Service、Controller、View，以及 `config/db.php`、`config/db.example.php`、`data/install.sql` 的实际内容。

本文件使用以下状态，避免把设计、代码和数据库实施混为一谈：

| 标记 | 含义 |
|---|---|
| **已实现** | 当前仓库存在代码；不等于真实数据库测试已通过 |
| **V1.1 基线** | 规范明确约定，尚需核对落库或实现情况 |
| **待确认建议** | 为联调提出的签名、类型或行为；未经 B/C 确认不能当作现有接口 |
| **待数据库验证** | 需要 C 的正式字典、DDL、数据及可用数据库才能验收 |

当前已接入 `models/NationalTeam.php`、`models/TournamentTeam.php`、`services/TeamStatsService.php` 和 C 的球队列表、DNA 详情页。表结构与约束见 `docs/data-dictionary.md`、`docs/er-diagram.md` 和 `data/install.sql`。C 的 15 表结构已在独立 MySQL 测试库还原。

2026-11-11 在 PHP 8.2.35、Yii 2.0.55、MySQL 8.0.19 上执行 `php tests/integration/b-module.php`，193 项真实 HTTP／数据库检查通过。`php tests/integration/match-data-import.php` 的 84 条导入断言通过。两项脚本均创建独立临时库并在结束时清理。本轮新增和修改的 PHP 文件均通过语法检查。

对开发库只读核对了 104 场比赛详情、48 队统计及 Journey。104 场均为 FINISHED，场上总进球 308，各队进球合计同为 308，各队完赛场次合计 208。九张赛事、球队、精选内容表的记录指纹在核对前后一致。该一致性检查覆盖当前导入数据的查询口径，外部事实复核继续按 V1.1 执行。

已阅读 C 分支 `c112acf` 的 `docs/c-reply-to-b.md`，B 的回复在 `e0d9e21` 推送。事件枚举、参赛校验和分页上限现已按 C 的表结构实施，字段确认单中的历史讨论可结合当前数据字典与实际代码追溯。

字段设计继续由 V1.1、C 的数据字典和 install.sql 维护。接口变更通过 PR 记录并评审。

## 2. B 已完成功能与文件

### 2.1 当前实现清单

| 文件 | 已实现内容 | 当前验收或后续事项 |
|---|---|---|
| [models/Tournament.php](../models/Tournament.php) | 表映射、字段规则、标签、year 唯一性验证配置 | 已按 C 的 15 表结构验证，最终部署仍需还原验收 |
| [models/Stadium.php](../models/Stadium.php) | 表映射、字段规则、标签；国家代码 CA/MX/US | 开发库 16 场馆已导入 |
| [models/WcMatch.php](../models/WcMatch.php) | 阶段/状态、双方不同、比分成对、加时累计、获胜方规则；赛事/场馆关联及存在性验证、赛事内编号唯一验证 | 球队关联、参赛资格与主要数据库约束已接入测试 |
| [models/MatchEvent.php](../models/MatchEvent.php) | 分钟/补时/排序字段验证、比赛关联；find() 默认 sort_order 升序 | 球队归属、正式枚举、分钟规则和排序已接入 |
| [models/MatchStory.php](../models/MatchStory.php) | 比赛唯一故事、发布标记、内容/路径/URL 规则；比赛、关联表、标签关系 | 审计字段正式策略、素材契约和真实唯一/FK 约束 |
| [models/MatchTag.php](../models/MatchTag.php) | code/name 规则、code 唯一验证、关联表关系 | 正式字典、唯一约束验证 |
| [models/MatchStoryTag.php](../models/MatchStoryTag.php) | 联合主键声明、必填 ID、重复组合验证、故事/标签关联 | 正式联合 PK、FK 及删除规则 |
| [services/MatchQueryService.php](../services/MatchQueryService.php) | 2026 届比赛列表、公开详情、阶段统计；精确过滤/分页、父表预加载、故事发布控制 | 球队资料与真实数据库查询已接入，执行计划专项验收待补 |
| [controllers/WcMatchController.php](../controllers/WcMatchController.php) | 请求白名单、ID 校验、依赖注入、GET/HEAD、HTTP 异常传播、View 数据交接 | 真实 HTTP 与数据库联调已执行 |
| [views/wc-match/index.php](../views/wc-match/index.php) | 比赛卡片、阶段/球队 ID 筛选、数组分页、空状态、北京时间 | 名称已接入，国旗路径可展示，A 尚需提供素材 |
| [views/wc-match/view.php](../views/wc-match/view.php) | 比赛信息、独立比分区域、已发布故事、标签、按原顺序展示事件 | 名称已接入，素材路径按 web/ 根解析，A 尚需提供封面 |
| [docs/b-field-confirmation.md](b-field-confirmation.md) | 七表逐字段及 Q01—Q11 确认事项 | C 反馈与正式字典版本 |

已有本地规则、结构、Controller 隔离测试及 View 渲染验证不替代正式数据库联调。本次只做文档核对，不重新执行 SQL、业务测试或宣称端到端验收通过。

### 2.2 新增的已推送交付

| 提交号 | 文件范围 | 已实现及待联调边界 |
|---|---|---|
| 21ce5f8 | services/JourneyService.php、controllers/JourneyController.php、views/journey/view.php | B 侧比赛历程；球队名称、参赛资格和最终成绩待 C |
| 90e5d75 | controllers/AdminTournamentController.php、models/TournamentSearch.php、views/admin-tournament/{index,view,create,update,_form}.php | 赛事 CRUD；数据库及 D 授权待联调 |
| ab91be7 | controllers/AdminStadiumController.php、models/StadiumSearch.php、views/admin-stadium/{index,view,create,update,_form}.php | 场馆 CRUD 与比赛引用保护 |
| 54bb116 | controllers/AdminStoryController.php、models/MatchStorySearch.php、views/admin-story/{index,view,create,update,_form}.php | 故事 CRUD、人工发布确认、删除事务 |
| 13fe837 | controllers/AdminStoryController.php、views/admin-story/{events,event-form,view}.php | 事件增改删、排序及比赛归属隔离 |
| 5ccaba4 | controllers/AdminStoryController.php、views/admin-story/{tags,tag-form,view}.php | 共享标签字典、故事标签关联、被引用标签拒删 |
| 91f5350 | controllers/AdminMatchController.php、models/WcMatchSearch.php、views/admin-match/{index,view,create,update,_form}.php | 比赛 CRUD、阶段/状态/球队/赛事筛选、复用 WcMatch 比分验证 |
| 3842640 | controllers/ApiController.php | 三个只读 JSON 路由；阶段统计调用 B，球队统计调用 C 的约定服务 |

表中花括号表示分别存在的 PHP 文件。后台 ID 当前直接录入，不编造球队名称；写入列为已明确的业务字段，不从表单加载主键和审计时间。四组后台已接入 D 的真实管理员授权，游客返回 403；路由及权限细节见 [给 D 的交接文档](b-d-admin-handoff.md)。

### 2.3 源数据交付与未完成范围

源 CSV 位于 `data/import/`，解析规则及来源说明见 [CSV README](../data/import/README.md)。记录数为 tournament 1、teams 48、stadiums 16、matches 104、stories 8、events 104、tags 16、story_tags 27、sources 57。八篇故事已按 B 负责人指令发布，本轮同步定稿标题、摘要、正文和 is_published=1。D 的独立外部复核待交付。MatchDataImportService 统一解析 fifa_code、场馆名和本届 match_no 为内部 ID，空比分保留 NULL，120 分钟为累计比分。

已完成 C Model 与统计服务接入、CSV 导入及 ID 映射、球队参赛资格与名称展示、事件枚举／分钟／球队校验、公开分页 1—100 上限及 D 的后台授权接入。浏览器实际操作验证了比赛球队筛选、比赛详情到 Journey 的跳转和球队搜索。

球队 PK 与三张图表已由 B 协助 C 完成，页面和新增排名接口见 [C PK 与图表交接](handoffs/c-team-insights.md)。待交付内容包括 48 队可靠的 final_result_code、A 的国旗与故事封面素材、D 的独立数据复核、D/C 的部署还原。开发库当前 48 个国旗路径、8 个封面路径及 48 个最终成绩均为空，8 篇故事已发布。

本轮 `php tests/integration/match-data-import.php` 通过 84 条真实数据库及 HTTP 断言。空库还原后八篇故事正文与数据包一致，前台展示 104 条事件及 27 条标签关联。新增记录读取 CSV 发布状态，重复导入保留后台已有发布／撤回状态和封面。现有正文若与 CSV 冲突，整批业务写入回滚。

最近一轮本地检查：19 个 PHP 文件语法通过；故事/事件/标签 160 用例 462 断言，故事删除事务控制流 6/32，比赛后台 62/161，API 43/167，合计 271 用例、822 断言。CRUD、事务和服务依赖使用替代对象，未访问真实数据库；这些数字不代表唯一约束、外键、InnoDB 回滚、C 服务或 D 登录已通过。

## 3. B 七张表的字段与键

### 3.1 通用解释

下表描述 **V1.1 基线及当前 Model 使用的列**，不是从数据库反查出的正式 DDL。

- `NN` 表示非空，`NULL` 表示允许缺失，`PK/FK/UQ` 分别表示主键、外键、唯一约束。
- 普通 `id` 按 V1.1 第 3.1 节为 `BIGINT UNSIGNED AUTO_INCREMENT`；关联表使用联合主键，不另加 id。精选表的 BIGINT 简写按此统一解释，C 必须核实真实两端类型一致。
- B 七表明确的业务默认值仅为 `status='SCHEDULED'`、`sort_order=0`、`is_published=0`。其他默认值未冻结；不能自行改成空串、0、当前时间或 DEFAULT NULL。
- C 当前为 tournament、stadium、wc_match、match_event、match_story 设置 created_at／updated_at，由数据库默认值及 ON UPDATE 维护。标签字典及故事标签关联表无审计列。逐字段定义见数据字典第 2 节。
- Model 的 unique/exist 规则及 primaryKey() 声明不能替代数据库的 UQ/FK/PK。

### 3.2 tournament（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增 |
| year | SMALLINT UNSIGNED | NN，UQ；以 year=2026 定位本届，不写死赛事 ID=1 |
| name | VARCHAR(120) | NN，赛事名 |
| start_date | DATE | NN，开始日期 |
| end_date | DATE | NN，结束日期 |
| description | TEXT | NULL，简介 |

### 3.3 stadium（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增 |
| name | VARCHAR(120) | NN，官方场馆名；未约定名称唯一 |
| city | VARCHAR(100) | NN，城市 |
| country_code | CHAR(2) | NN，CA / MX / US |
| timezone_name | VARCHAR(64) | NULL，时区名；示例不是默认值 |

### 3.4 wc_match（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增；详情链接使用该值 |
| tournament_id | BIGINT UNSIGNED | NN，FK → tournament.id |
| match_no | SMALLINT UNSIGNED | NN，1—104；与 tournament_id 联合 UQ，不是 id |
| home_team_id | BIGINT UNSIGNED | NN，FK → national_team.id，主队展示位 |
| away_team_id | BIGINT UNSIGNED | NN，FK → national_team.id，客队展示位 |
| stadium_id | BIGINT UNSIGNED | NN，FK → stadium.id |
| kickoff_utc | DATETIME | NN，UTC 开赛时间 |
| stage_code | VARCHAR(10) | NN，比赛阶段，见第 8.2 节 |
| group_code | CHAR(1) | NULL，仅 GROUP 可填写 A—L |
| status | VARCHAR(16) | NN，默认 SCHEDULED，见第 8.2 节 |
| home_score_90 | TINYINT UNSIGNED | NULL，FINISHED 必填，与 away_score_90 成对 |
| away_score_90 | TINYINT UNSIGNED | NULL，FINISHED 必填，与 home_score_90 成对 |
| home_score_120 | TINYINT UNSIGNED | NULL，与 away_score_120 成对；累计加时比分 |
| away_score_120 | TINYINT UNSIGNED | NULL，与 home_score_120 成对；累计加时比分 |
| home_penalties | TINYINT UNSIGNED | NULL，与 away_penalties 成对；点球大战命中数 |
| away_penalties | TINYINT UNSIGNED | NULL，与 home_penalties 成对；点球大战命中数 |
| winner_team_id | BIGINT UNSIGNED | NULL，FK → national_team.id；非空须为本场一方，小组赛不填写 |
| summary | VARCHAR(300) | NULL，比赛摘要，不是故事正文 |

### 3.5 match_event（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增 |
| match_id | BIGINT UNSIGNED | NN，FK → wc_match.id |
| team_id | BIGINT UNSIGNED | NULL，FK → national_team.id，非空由 Model 校验属于本场双方 |
| player_name | VARCHAR(100) | NULL，球员显示名；不另建球员表 |
| minute | SMALLINT UNSIGNED | NULL，基础分钟 |
| added_minute | SMALLINT UNSIGNED | NULL，补时分钟；45+2 分开存储 |
| period_code | VARCHAR(12) | NN，时段代码，按第 8.2 节枚举校验 |
| event_type | VARCHAR(20) | NN，事件代码，按第 8.2 节枚举校验 |
| description | VARCHAR(500) | NULL，事件描述 |
| sort_order | INT | NN，默认 0；当前规则允许有符号整数 |

### 3.6 match_story（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增 |
| match_id | BIGINT UNSIGNED | NN，FK → wc_match.id，UQ；一场最多一篇故事 |
| title | VARCHAR(150) | NN，标题 |
| summary | VARCHAR(500) | NN，故事摘要 |
| body | TEXT | NN，正文；当前 View 纯文本编码输出 |
| cover_path | VARCHAR(255) | NULL，相对 web/ 的本地素材路径 |
| source_url | VARCHAR(600) | NULL，内容来源；当前仅链接有效 HTTP/HTTPS URL |
| is_published | TINYINT(1) | NN，默认 0；未发布内容不提供给前台 |
| created_at | DATETIME | NN，UTC，默认 CURRENT_TIMESTAMP |
| updated_at | DATETIME | NN，UTC，默认 CURRENT_TIMESTAMP，ON UPDATE CURRENT_TIMESTAMP |

### 3.7 match_tag（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| id | BIGINT UNSIGNED | NN，PK，自增 |
| code | VARCHAR(40) | NN，UQ；代码示例不是默认值或冻结枚举 |
| name | VARCHAR(40) | NN，显示名；未规定唯一 |

### 3.8 match_story_tag（B）

| 字段 | 类型 | 空值 / 键 / 用途 |
|---|---|---|
| story_id | BIGINT UNSIGNED | NN，联合 PK 第一列，FK → match_story.id |
| tag_id | BIGINT UNSIGNED | NN，联合 PK 第二列，FK → match_tag.id |

联合主键为 `(story_id, tag_id)`，不添加自增 id、默认值或其他列。

### 3.9 关系与现有 ActiveRecord 方法

| 关系 / 基数 | 表归属 | 当前代码情况 |
|---|---|---|
| tournament 1:N wc_match | B → B | WcMatch::getTournament()，hasOne，`['id' => 'tournament_id']` |
| stadium 1:N wc_match | B → B | WcMatch::getStadium()，hasOne，`['id' => 'stadium_id']` |
| national_team 1:N wc_match（三个不同角色） | C → B | 三个独立关联已在 WcMatch 实现 |
| tournament、national_team 经 tournament_team 建立参赛关系 | B/C → C | C 已实现联合 PK/FK 和 TournamentTeam 关联 |
| wc_match 1:N match_event | B → B | MatchEvent::getMatch()；事件 getTeam() 已实现 |
| wc_match 1:0..1 match_story | B → B | MatchStory::getMatch()；数据库 match_id UQ 待验证 |
| match_story 1:N match_story_tag | B → B | MatchStory::getMatchStoryTags()、MatchStoryTag::getStory() |
| match_tag 1:N match_story_tag | B → B | MatchTag::getMatchStoryTags()、MatchStoryTag::getTag() |
| match_story N:M match_tag | B → B | MatchStory::getTags() 经 matchStoryTags；MatchTag 没有直接 getStories()，可沿关联记录的 story 读取 |

Tournament、Stadium 目前没有反向 getMatches()；WcMatch 也没有故事/事件的 AR 关联。MatchQueryService 对故事和事件使用已有 Model 单独查询，不应假设这些反向方法已存在。

## 4. B 当前可交接的查询与页面接口（已实现）

### 4.1 公共边界与类型

`app\services\MatchQueryService` 为实例服务，当前无自定义构造参数。三个方法均以 `tournament.year=2026` 限定赛事，数据库异常写服务端日志并转为友好的 ServerErrorHttpException（500）；400/404 及其他未预期异常不伪装成成功数据。

本节 `Id` 仅为说明用类型名，不是新增 PHP 类：表示 `int|string`。Service 在 PHP 整数范围内把 ID 转为 int，超出则保留十进制字符串；比分、比赛编号、分钟及计数返回 int 或相应 null。字符串时间仍为 UTC `Y-m-d H:i:s`，现有两个 View 转换为 Asia/Shanghai 并标注北京时间。

注意：`getMatchDetail(int $matchId)` 与 V1.1 的后续球队方法使用 int 入参，Controller 拒绝大于 PHP_INT_MAX 的比赛 ID。这与 MySQL BIGINT UNSIGNED 的完整上界不同。C 需确认实际 ID 是否都在 PHP 整数范围内；若需支持更大 ID，双方先协商接口类型及 JSON/JavaScript 精度策略，不能强转截断。

### 4.2 getMatchList

```php
public function getMatchList(array $filters = []): array
```

| 输入键 | 当前接受值与行为 |
|---|---|
| stage | 缺省、null、空字符串表示不筛选；否则必须为第 8.2 节的阶段代码，大小写严格匹配 |
| team_id | 缺省、null、空字符串表示不筛选；否则为正整数或纯数字字符串，上限 BIGINT UNSIGNED；匹配主队或客队 |
| page | 缺省为 1；显式传入时必须是 PHP_INT_MAX 范围内的正整数或纯数字字符串 |
| page_size | 缺省为 20，允许 1—100 的正整数或纯数字字符串，越界返回 400 |

stage 与 team_id 可同时筛选。Service 拒绝未知键、负数、0、浮点数、科学计数、数组、布尔值等不合法输入，page/page_size 显式 null 或空串返回 400。球队不存在或未参加 2026 时返回 404，参赛但无匹配赛程时返回空列表。

返回结构（类型说明，不是演示赛果）：

```text
{
  items: MatchItem[],
  teams: TeamInfo[],
  pagination: {page: int, page_size: int, total_count: int, page_count: int}
}
```

`MatchItem` 为第 3.4 节的 18 个比赛列，加以下键。`TeamInfo` 为 `{id, name_zh, name_en, fifa_code, flag_path}`，ID 类型沿用本节 Id 约定。

| 附加键 | 当前结构 |
|---|---|
| tournament | `{id, year, name, start_date, end_date, description}` 或 null |
| stadium | `{id, name, city, country_code, timezone_name}` 或 null |
| home_team / away_team / winner_team | TeamInfo 或 null，按三个独立的球队外键关联，允许停用球队历史查询 |
| final_score | `{home: int或null, away: int或null}`；仅 FINISHED 提供最终场上比分，优先 120 分钟，否则 90 分钟，不含点球 |

列表按 `kickoff_utc ASC, match_no ASC, id ASC` 稳定排序，批量预加载 tournament、stadium 及三个球队关联。teams 是本届全部参赛队的筛选目录，含停用球队。独立测试中每页 1 条和 100 条的比赛列表查询均不超过 8 次。当前列表包含取消场次，仅阶段统计排除 CANCELLED。

空查询在 page=1 返回 `items=[]`、`total_count=0`、`page_count=0`，保留请求页大小。页码超出 `max(1,page_count)` 返回 400，不钳制到最后一页。使用方不能把默认首批 20 条当作球队的全部比赛。

### 4.3 getMatchDetail

```php
public function getMatchDetail(int $matchId): array
```

返回 **MatchItem 的平铺字段**，再增加下表三个键；不是 `{match: {...}}` 包装。`$matchId<1` 返回 400；不存在或不属于 2026 届的比赛返回 404。

| 键 | 当前结构与发布边界 |
|---|---|
| story | null，或第 3.6 节 10 个故事列；返回时 is_published 为布尔 true |
| events | 第 3.5 节 10 个事件列加 `team: TeamInfo或null`，按 sort_order ASC、id ASC 排列 |
| tags | `{id: Id, code: string, name: string}` 数组；当前按 tag.id 升序 |

仅查询 `is_published=1` 的故事。没有已发布故事时，统一返回 `story=null, events=[], tags=[]`，不会读取对应事件；草稿标题、正文、标签、时间轴都不对外提供。故事是可选扩展，所有基础比赛均应有详情入口。

### 4.4 getStageSummary

```php
public function getStageSummary(): array
```

```text
[{stage_code: string, match_count: int, finished_match_count: int, total_goals: int或null}, ...]
```

- match_count 统计非 CANCELLED 的有效赛程，finished_match_count 只统计 FINISHED。
- total_goals 仅累加 FINISHED 的最终场上比分；各侧优先 120 分钟累计值，否则 90 分钟；不加点球，也不把 90 与 120 相加。
- 有有效赛程但尚无完赛时 total_goals=null；真实完赛 0:0 的汇总可以为 0。
- 当前只返回有有效赛程的阶段；无数据或只有取消比赛时返回 `[]`，不补造七个阶段行。顺序为 GROUP、R32、R16、QF、SF、THIRD、FINAL。C 图表需确认接受这些当前行为。

### 4.5 页面与 Controller / View 数据交接

| 当前入口 | 行为与 View 参数 |
|---|---|
| GET/HEAD `index.php?r=wc-match/index` | 提取 stage、team_id、page、page_size，向 index.php 传 matches、pagination、filters、teams |
| GET/HEAD `index.php?r=wc-match/view&id=...` | 严格正整数 ID，非法为 400、查无为 404；向 view.php 传 `match=Service完整详情数组` |

filters 固定含 stage、team_id、page、page_size；stage/team_id 保留有效原始输入，page/page_size 使用 Service 返回值。列表筛选以 GET 提交并重置 page=1，分页保留 stage/team_id/page_size。

Controller 采用构造器注入 MatchQueryService；写方法被 VerbFilter 拒绝为 405。两个入口返回 HTML，不是 JSON API。只读 JSON API 已在 `3842640` 实现，见第 7.3 节；不改变这两个 HTML 入口。

## 5. C 需要提供的球队 Model 与 B 的依赖

### 5.1 文件、字段访问及职责

| 交付文件 / 类 | 表与已约定字段 | B 使用方式 |
|---|---|---|
| `models/NationalTeam.php` / `app\models\NationalTeam` | national_team：id、fifa_code、name_zh、name_en、flag_path、description、is_active | AR 属性读取球队身份、名称、素材；球队 ID 存在性检查；不由 B 再建球队 Model |
| `models/TournamentTeam.php` / `app\models\TournamentTeam` | tournament_team：tournament_id、team_id、group_code、final_result_code | 联合 PK；查本届资格、分组、最终成绩，不由 B 维护另一份参赛关系 |

以上两个类已在 `c45f159` 交付，继承 Yii2 ActiveRecord，支持 find()/findOne()。NationalTeam::findActive() 用于启用球队目录，按 ID 的普通查询保留停用球队历史资料。

| C 字段 | V1.1 类型 / 约束 | 读取约定与待确认点 |
|---|---|---|
| national_team.id | BIGINT UNSIGNED，PK | B 所有球队 FK 的唯一目标；不以名称或 fifa_code 代替内部 ID |
| fifa_code | CHAR(3)，NN UQ | 源数据映射键、三字母显示标识；不是另一个数字主键 |
| name_zh / name_en | VARCHAR(80) / VARCHAR(100)，NN | 中文/英文展示名；当前优先使用中文名，缺失时回退英文名或球队 ID |
| flag_path | VARCHAR(255)，NULL | 相对 web/ 的本地素材路径，缺失时展示球队名称与 FIFA 代码，素材由 A 提供 |
| description | TEXT，NULL | 球队简介；前台编码输出 |
| is_active | TINYINT(1)，NN 默认 1 | findActive() 过滤停用队，按 ID 的普通查询保留历史球队 |
| tournament_team.tournament_id / team_id | BIGINT UNSIGNED，联合 PK，各自 FK | 分别引用 tournament.id、national_team.id |
| group_code | CHAR(1)，NN，A—L | 赛事内分组，不从球队基础表猜测 |
| final_result_code | VARCHAR(20)，NULL | 最终成绩类别；无可靠结论保持 null，见第 8.2 节 |

V1.1 没有独立的 `short_name`、`abbreviation`、`logo_url` 字段。本轮不创造这些列或接口承诺；“简称”若仅指三字母码可用 fifa_code，如需其他简称，应先与 C 确认并更新正式字典。

### 5.2 ID 映射与本届参赛资格

1. B 提交源 CSV/JSON 时，球队使用经核验的 fifa_code、场馆使用官方名称，C 统一映射为内部主键。不能各自生成互不一致的球队 ID，也不能把 FIFA 外部数字编号直接当 national_team.id。
2. home_team_id、away_team_id、winner_team_id 均引用同一 national_team.id；三列不是 tournament_team 的不同 ID，也不是 fifa_code。
3. 先通过 tournament.year=2026 取得真实赛事 ID，再用 `(tournament_id, team_id)` 查询 tournament_team。球队存在不等于参加 2026；双方必须各有该赛事参赛记录。
4. home_team_id 与 away_team_id 不同。winner_team_id 可空；有值只能为本场双方之一，且遵循阶段、状态、场上及点球规则。
5. match_event.team_id 可空，非空引用同一球队 ID 体系，并由 MatchEvent 校验属于本场双方。
6. 数据导入顺序沿用 V1.1：赛事 → 球队 → 场馆 → 参赛关系 → 比赛 → 故事/事件/标签/关联。C 维护最终 install.sql 和映射程序，B 提供赛事源数据与来源复核记录。

### 5.3 已实现的关系方法

| 拟归属文件 | 建议签名 | 关联映射与用途 |
|---|---|---|
| C：NationalTeam.php | `public function getTournamentTeams(): ActiveQuery` | hasMany TournamentTeam，`['team_id' => 'id']`；返回各届参赛记录 |
| C：TournamentTeam.php | `public function getTeam(): ActiveQuery` | hasOne NationalTeam，`['id' => 'team_id']` |
| C：TournamentTeam.php | `public function getTournament(): ActiveQuery` | hasOne Tournament，`['id' => 'tournament_id']` |
| B：WcMatch.php | `getHomeTeam(): ActiveQuery`、`getAwayTeam(): ActiveQuery`、`getWinnerTeam(): ActiveQuery` | hasOne NationalTeam，分别对应三个球队外键 |
| B：MatchEvent.php | `getTeam(): ActiveQuery` | hasOne NationalTeam，`['id' => 'team_id']`，team_id 为空时无关联 |

ActiveQuery 类型为 `yii\db\ActiveQuery`。WcMatch 通过 tournament_team 校验双方与获胜方参赛资格，MatchEvent 通过比赛双方校验 team_id。C 两个 Model 的类名、联合主键和关联方法均已按表中映射接入。

## 6. JourneyService 已实现接口及 C 接入需求

### 6.1 当前代码与签名

文件：`services/JourneyService.php`，类 `app\services\JourneyService`；公开方法：

```php
public function getTeamJourney(int $teamId): array
```

入口为 GET/HEAD `index.php?r=journey/view&team_id=...`。服务查询本届全部匹配比赛，按 kickoff_utc、match_no、id 升序。非法 ID 返回 400，未知或未参赛球队返回 404，已参赛但无比赛返回 []。

新增 `public function getTeamProfile(int $teamId): array`，返回 `{id, name_zh, name_en, fifa_code, flag_path, group_code, final_result_code}`。资料读取 C 的两个 Model。JourneyController 向 View 传入 teamId、journey、team 和 teams 目录，teams 复用现有 TeamDirectoryService 的含停用球队目录。

### 6.2 实际返回结构

```text
JourneyItem = {
  match_id: int|string, match_no: int,
  kickoff_utc: string, stage_code: string, status: string,
  opponent: {team_id: int|string, name_zh: string|null, name_en: string|null, fifa_code: string|null, flag_path: string|null},
  is_home: bool,
  goals_for: int|null, goals_against: int|null,
  penalties_for: int|null, penalties_against: int|null,
  advancement_result: "ADVANCED"|"ELIMINATED"|null,
  is_featured: bool
}
返回：JourneyItem[]
```

- kickoff_utc 为 UTC ISO 8601；MatchQueryService 仍保留数据库 UTC 格式，两者没有互相改写。
- 已完赛比分按本球队视角转换，优先 120 分钟累计比分，否则 90 分钟；未完赛为 null，点球独立。
- 保留 CANCELLED、POSTPONED 等赛程并提供 status；只有 FINISHED 的 R32/R16/QF/SF 提供晋级/淘汰结果。
- GROUP、未完赛、FINAL 和 THIRD 的 advancement_result 为 null；不能将决赛或季军赛胜利表达为进入下一轮。
- is_featured 仅依据已发布故事；草稿不会产生精选入口。
- opponent 通过主客队关联批量读取真实资料。最终成绩由 getTeamProfile() 读取 tournament_team，NULL 显示“暂无记录”。

### 6.3 C 依赖的接入情况

| 所需能力 | C 交付内容 | B 接入位置 |
|---|---|---|
| 球队基本信息 | NationalTeam 的 id、name_zh、name_en、fifa_code、flag_path；实际命名空间和查询行为 | Journey 与比赛列表/详情读取真实名称及素材 |
| 本届参赛资格 | TournamentTeam 联合 PK、getTeam()/getTournament() 等实际关系；以 tournament.year=2026 定位赛事 | B 增加球队存在性、参赛资格及事件球队验证 |
| 最终成绩 | tournament_team.final_result_code 的可靠值或 null | Journey 成绩展示；不从最大阶段猜测名次 |
| 异常语义 | 未知／非参赛球队 404，停用球队仍可读历史，已参赛无比赛为 [] | 当前实现与测试已覆盖 |

以上是当前 B 侧阶段实现及待接入任务，不意味着 C 已提供自定义 getTeamInfo()/isParticipant() 等方法。

## 7. TeamStatsService 接口与调用

### 7.1 V1.1 既定实例方法

文件为 `services/TeamStatsService.php`，类为 `app\services\TeamStatsService`，当前通过 Yii::createObject 创建实例。

```php
public function getTeamStats(int $teamId): array
public function getCompareStats(int $leftId, int $rightId): array
public function getScoringRanking(): array
```

| 方法 | 所需返回 | B 的用途 |
|---|---|---|
| getTeamStats | 下节单队统一统计数组 | 已有 ApiController::actionTeamStats() 调用入口；B 不重写球队聚合 SQL |
| getCompareStats | `{left: TeamStats, right: TeamStats}`，对应输入顺序 | 已有 ApiController::actionTeamCompare() 调用入口；两边复用相同统计规则 |
| getScoringRanking | 2026 届完整参赛队统计，goals_for DESC、fifa_code ASC、team_id ASC，含无完赛球队 | A/C 排名图表复用 |

### 7.2 getTeamStats 返回字段

字段名称与业务含义来自 V1.1，C 的实现已按下表接入并执行真实数据库检查。

| 返回字段 | 含义与必须遵守的规则 | 待确认类型 / 边界 |
|---|---|---|
| team_id | national_team.id | Id；与 int 入参及前端精度边界统一 |
| team_name | 球队展示名 | string；建议对应 name_zh，回退策略待确认 |
| fifa_code | 已核验三字母球队码 | string |
| matches_played | 2026 FINISHED 场数 | C 回复为 int；无完赛为 0，数据库/关键资料错误应报错 |
| wins | 最终场上比分领先场数 | 同上；不能按 winner_team_id 直接计数 |
| draws | 最终场上比分相等场数，含点球决胜前的场上平局 | 同上 |
| losses | 最终场上比分落后场数 | 同上；W+D+L=matches_played |
| goals_for | 本队最终场上进球合计 | C 回复为 int；无完赛为 0，缺正式比分报错；绝不含点球大战 |
| goals_against | 对手最终场上进球合计 | 同上 |
| goals_per_match | goals_for / matches_played | 数值或 null；分母为 0 时 null |
| goals_against_per_match | goals_against / matches_played | 数值或 null；展示层保留两位小数 |
| win_rate | wins / matches_played | 0—1 数值或 null；分母为 0 时 null，不返回百分号字符串 |
| penalty_wins | 点球大战获胜场数 | 已核实计数为 int；独立于 wins |
| final_result_code | 本届 tournament_team 的最终成绩 | string 或 null；不能编造排名 |

“确认没有完赛记录”和“数据库失败/资料缺失”必须区分。C 已回复前者场次、进失球为 0，比率为 null；FINISHED 缺 90 分钟比分或数据库失败应记录日志并报 500，不能返回全 0 的成功统计。请 C 用实际代码和数据库测试证明该语义；B 调用端不替其补算或补零。

C 已交付 getScoringRanking 的完整 TeamStats 数组和上述排序，B 已对本机 48 队逐个核对排名项与单队查询，结果一致。

C 的实现对未知球队及未参加 2026 返回 404，同队比较返回 400，无完赛时场次／进失球为 0、比率为 null。B 的 API 统一封装这些服务结果。赛事初始化失败和数据库异常按 500 处理。

### 7.3 已实现的 API 调用与响应

文件：`controllers/ApiController.php`，命名空间 `app\controllers`，提交 `3842640`。

| 路由（均仅 GET） | Action 签名 | 调用 |
|---|---|---|
| api/team-stats?id=... | actionTeamStats(mixed $id = null): array | C：getTeamStats(int $teamId): array |
| api/team-compare?left_id=...&right_id=... | actionTeamCompare(mixed $left_id = null, mixed $right_id = null): array | C：getCompareStats(int $leftId, int $rightId): array |
| api/insights?chart=stage-goals | actionInsights(mixed $chart = null): array | B：getStageSummary(): array |

默认地址示例：`index.php?r=api/insights&chart=stage-goals`。MatchQueryService 使用构造器注入；C 服务由服务器端配置 `ApiController::$teamStatsService` 实例，或在首次球队请求时按 `app\services\TeamStatsService` 经 Yii::createObject 创建。构造依赖如有特殊要求请 C 明确，不将依赖从请求参数加载。

C 的服务已在当前分支交付，两个球队 API 已在真实 HTTP／临时数据库上验证成功。未参赛／未知球队 404、非法 ID／同队比较 400、无完赛时平均值为 null 的行为均已覆盖。

```text
成功（200）：{success: true, data: 服务业务数组, error: null}
失败：{success: false, data: null, error: {code: string, message: string}}
```

- ID 严格为 PHP 整数范围内正整数；同队对比为 400 INVALID_PARAMETER。
- 非法 chart 为 400 INVALID_CHART；C 抛出的未知/非参赛球队 404 映射 TEAM_NOT_FOUND。
- 仅允许 GET，HEAD 也不放行；错误方法被 VerbFilter 拒绝为 405 METHOD_NOT_ALLOWED。写请求还受现有 CSRF 检查保护，无有效 token 时可能先返回 400，不执行服务。
- 未交付依赖、数据库错误、未预期异常及无效返回结构均为友好 500；不将异常包装为成功 200，不泄露 SQL、路径或敏感配置。
- C 返回空数组时按查无处理；比较结果必须包含非空的 left/right 数组。正式服务应按约定抛 404，并保证完整 TeamStats 字段；C 不再包 success/data/error。

## 8. 正式数据库与业务规则确认清单

### 8.1 PK、FK、索引与删除策略

| 确认对象 | V1.1 已明确 | C 需给出的正式实施信息 |
|---|---|---|
| 全部七表及 C 两表 | 表名、核心字段及本文件第 3/5 节关系 | 完整字典、DDL 路径/提交号；类型、UNSIGNED、NULL、默认值、自增、审计列及字符集 |
| tournament | PK(id)、UQ(year) | 约束名及实际索引 |
| wc_match | PK(id)、UQ(tournament_id,match_no)；INDEX(tournament_id,kickoff_utc)、INDEX(home_team_id)、INDEX(away_team_id)、INDEX(stage_code) | 所有 FK 配套索引、实际 CHECK 范围与业务层分工 |
| match_story / match_tag | UQ(match_id) / UQ(code) | 不擅自增加 stadium.name、match_tag.name 等未确认唯一约束 |
| match_story_tag / tournament_team | 联合 PK(story_id,tag_id) / (tournament_id,team_id) | 实际列顺序、两个 FK 及必要的反向索引 |
| match_event | PK、比赛 FK、可空球队 FK；INDEX(match_id,sort_order) 属建议 | 是否采纳建议索引，team_id 的 FK 索引 |
| 历史引用 | 不允许因删赛事/球队/场馆而级联清空比赛历史 | 对 wc_match 的赛事/场馆/三种球队 FK，逐条给出 ON DELETE / ON UPDATE；可空不自动等于 SET NULL |
| 事件/故事引用 | 保护真实比赛历史；删故事不能反向删比赛 | match_event.match_id、team_id，match_story.match_id 各自删除/更新策略 |
| 标签关联清理 | match_story_tag 允许必要的 CASCADE | story_id、tag_id 是否分别采用 CASCADE；允许不等于已确定 |
| 参赛历史与停用 | national_team.is_active 停用不破坏历史 | tournament_team 两个 FK、停用查询策略；不自行新增软删除列 |

当前 FK、索引与删除规则见 `docs/data-dictionary.md` 第 5 节和 `data/install.sql`。本轮已用 C 的 15 表结构执行唯一约束与引用保护测试，后续 DDL 变更由 C 统一维护。

### 8.2 枚举及比分口径

| 对象 | 当前依据与代码 | 确认边界 |
|---|---|---|
| stage_code | GROUP / R32 / R16 / QF / SF / THIRD / FINAL；WcMatch::STAGE_CODES | V1.1 已明确；DB 采用 VARCHAR，CHECK 实施由 C 确认，不改造为未协商 ENUM |
| status | SCHEDULED / IN_PROGRESS / FINISHED / POSTPONED / CANCELLED；默认 SCHEDULED | V1.1 已明确；IN_PROGRESS 是人工状态，不承诺实时比分 |
| group_code | A—L；wc_match 仅 GROUP 填写；tournament_team 为参赛分组 | 当前 WcMatch 允许 GROUP 的 group_code 为空；是否需更严格必填不得未经协商增加 |
| period_code | H1 / H2 / ET1 / ET2 / SHOOTOUT | 与 C 的 CHECK 对齐，Model 严格验证，非 SHOOTOUT 必填 minute，有 added_minute 时须有 minute |
| event_type | GOAL / OWN_GOAL / YELLOW / RED / PENALTY_GOAL / PENALTY_MISS / OTHER | 与 C 的 CHECK 对齐，点球是否属于大战由 period_code 区分 |
| final_result_code | 建议 GROUP / R32 / R16 / QF / FOURTH / THIRD / RUNNER_UP / CHAMPION | C 确认集合与含义；这是最终成绩类别，不是 stage_code 或 1—48 的官方排名 |
| 标签代码 | 仅有示例 | 不把示例当成固定数据库枚举或默认值 |
| 事件排序 | 当前 Service 为 sort_order ASC、id ASC，View 不重排 | 完整跨时段口径待确认；不能仅按 minute+added_minute 推断时间线 |

业务核对必须同时覆盖：

1. 双方不同、均属于该赛事，winner 非空只能为双方之一，事件球队非空时也须为双方之一。
2. FINISHED 必填 90 分钟成对比分；120 分钟可空但成对，有值表示累计值且不小于对应 90 分钟值。
3. 点球成对、单列展示，不计场上进球；点球胜方与 winner_team_id 一致。
4. 已结束的淘汰赛、季军赛和决赛必须有 winner_team_id；小组赛不填。场上平局经点球获胜仍计 draws，penalty_wins 独立增加。
5. SCHEDULED / POSTPONED / CANCELLED 不能填写虚构赛果。状态迁移与 IN_PROGRESS 的编辑限制还需补充，不由本文件猜测。
6. 一场比赛最多一篇故事；关联表组合不能重复；未发布故事、事件及标签不可泄漏。
7. 统计以 wc_match 的正式比分为事实源，不从不完整的精选事件计数进球；null 与真实 0 不能混用。

### 8.3 配置、数据与素材交付

- `config/db.php` 加载 db.example.php 并叠加被 Git 忽略的 db.local.php；C 提供正式库名、端口等非敏感约定，账号密码仅存各自本地，不写进交接文档。
- 示例连接使用 utf8mb4、MySQL 原生预处理、连接时区 +00:00；实际服务器版本与正式环境由联调记录确认，不以配置文件存在代替连接成功。
- V1.1 目标为 2026 赛事 1 条、48 球队／参赛关系、16 场馆、104 比赛（72 小组赛+32 淘汰赛）、5—8 场精选故事。本机开发库已达到上述数量，8 篇故事已发布，数据包支持还原此状态。
- B 提供真实赛程、场馆、精选内容及来源；C 合并导入程序、内部 ID 映射和最终 install.sql；A 协调合法国旗/图片素材，D 独立复核。
- 详情封面和球队国旗均按 web/ 下相对路径解析，拒绝协议、路径跳转及危险字符。故事正文当前为纯文本编码并保留换行，富文本格式如需采用须另行确认净化规则。

## 9. 双方联调测试清单

本轮执行记录见本文开头，覆盖数据库恢复、核心 CRUD、权限／CSRF、比赛及统计接口、球队展示、历史停用、发布控制和导入。下表中的并发竞争、完整故障注入、全部大 ID 边界及素材验收仍需对应专项证据。

在独立测试库或可恢复备份中执行；隔离测试数据必须显式标识，不混入最终真实赛事库。本次整理不执行以下 SQL/数据库测试。

| 编号 | 用例 | 预期与验收证据 | 负责人 |
|---|---|---|---|
| T01 | 全新库执行 C 的正式 install.sql | 可还原结构及演示数据；保存 DDL/数据版本和执行记录 | C，B 复核 |
| T02 | PK/FK/UNSIGNED/NULL/默认值/索引核对 | 与第 3、8 节一致；保存表定义和索引清单 | C+B |
| T03 | 数据规模、分组及来源 | 满足目标数量、12 组各 4 队；数据来源及复核记录可查 | C+B，D 抽检 |
| T04 | 源 fifa_code 到内部 ID 映射 | 主客/胜方/事件引用均指向同一球队，不以名称或外部 ID 误关联 | C+B |
| T05 | 非法/不存在/非本届球队 | 区分参数错误、查无、资格不符；按 BC02 确认的统一行为响应 | C+B |
| T06 | 同队对赛、跨届参赛、winner 非本场一方 | Model/导入/DB 按已确认分工拒绝；记录绕过 Model 的 DB 结果 | B+C |
| T07 | 重复 year、赛事内 match_no、故事 match_id、标签 code、关联组合 | Model 与真实 UQ/PK 均按设计阻止；不同赛事可复用 match_no | C+B |
| T08 | 删除被引用赛事/球队/场馆 | 不级联丢失历史；故事标签清理符合逐 FK 决议 | C+B |
| T09 | 阶段+球队列表筛选 | AND 阶段、OR 主客队，无重复，准确总数和稳定排序 | B，C 提供数据 |
| T10 | 分页边界 | 默认 20、空集 page_count=0、超页 400；GET 翻页保留筛选及 page_size | B |
| T11 | 详情 ID / 赛事边界 | 非法 ID 400，不存在或非 2026 比赛 404；异常不伪装为 200 | B |
| T12 | 双边球队名称、国旗及停用 | 主客及胜方不串位；无素材有合适回退；停用不抹去历史 | C+B，A 核对素材 |
| T13 | 90 分钟普通胜负/平局及真实 0:0 | 详情、球队统计、阶段统计一致，0 与 null 区分 | B+C |
| T14 | 90 分钟 1:1、120 分钟 2:1 的隔离样本 | 最终场上比分 2:1，不算 3:2；进失球按双方视角正确 | B+C |
| T15 | 场上 1:1、点球 4:3 的隔离样本 | 场上平局、进失球各 1；胜方 penalty_wins+1，wins 不加 1 | B+C |
| T16 | 未开始/延期/取消/进行中 | 不伪造最终赛果；非 FINISHED 不参与球队完赛统计；取消不计有效阶段场次 | B+C |
| T17 | 阶段无完赛、无有效赛程、只有取消 | 有效赛程无完赛 total_goals=null；无有效记录返回 []，图表不报错 | B+C |
| T18 | 未发布故事、无故事、有故事无事件 | 草稿正文/标题/标签/事件不泄漏；普通详情及空状态正常 | B |
| T19 | 事件同 sort_order、补时、点球分钟为空 | 按当前 sort_order/id 或后续确认契约排序；View 忠实显示，不计事件条数作比分 | B+C |
| T20 | Journey 全量与主客互换 | 不丢分页后的比赛；按时间、编号排序，对手和进失球视角正确 | B+C，已在本轮真实 HTTP／数据库检查中覆盖核心流程 |
| T21 | 晋级、决赛/季军赛、最终成绩未知 | 按 BC07 决议展示；不把最大阶段或未知成绩冒充官方排名 | B+C，已在本轮真实 HTTP／数据库检查中覆盖核心流程 |
| T22 | 统计无完赛、同队 PK、排名并列 | 比率不除零、统一空集语义和并列顺序，left/right 保持输入顺序 | C+B |
| T23 | UTC 跨日和大 ID | 北京时间跨日正确；ID 不截断、不经 JavaScript 浮点误改；ISO/数据库格式按接口区别处理 | B+C |
| T24 | 名称/故事/标签 XSS、素材路径 | 文本编码、危险 URL 拒绝；桌面和移动端真实浏览器检查 | B，A 协助 |
| T25 | 数据库失败与 API 封装 | 服务端日志有记录，用户信息友好；API HTTP 状态及统一包装正确，不暴露敏感信息 | B+C，已在本轮真实 HTTP／数据库检查中覆盖核心流程 |

本轮自动化证据为 `tests/integration/b-module.php`、`tests/integration/match-data-import.php`，结果与环境见本文开头。浏览器实际验证球队名称筛选、决赛详情与 Journey 跳转、球队搜索保留路由。完整素材和部署验收随对应交付继续执行。

## 10. C 后续交付与反馈

| 编号 | 待确认事项 | 对应字段确认单 | 需反馈的具体内容 |
|---|---|---|---|
| BC01 | 正式数据库版本 | Q01—Q06、Q11 | 字典/DDL/最终 SQL 路径和提交号；类型、审计字段、约束、逐 FK 删除/更新策略 |
| BC02 | 两个 C Model、球队与本届资格 | Q02、Q05、Q11 | 类命名空间、联合 PK、可用查询/关系方法；未知球队与非参赛球队异常；历史停用策略 |
| BC03 | 事件球队与枚举/排序 | Q05、Q07 | team_id 非空时的允许范围、空分钟含义、枚举集合、跨时段与并列排序 |
| BC04 | 名称、简称与素材 | Q08、Q11 | name_zh/name_en 优先级；是否仅用 fifa_code 作简称；国旗/封面相对根目录与合法素材 |
| BC05 | 接受当前 B 查询契约 | Q09、Q10 | 第 4 节键名、ID 类型、UTC 格式、空集/超页、page_size 上限、缺失阶段与阶段顺序 |
| BC06 | TeamStatsService | Q09、Q11 | 三个签名、构造依赖、全部键与类型、null/0、比较边界、排名简表及并列规则、异常形式 |
| BC07 | JourneyService 联调契约 | Q08、Q09、Q11 | 时间格式、资格查无、取消/未完赛展示、决赛/季军赛结果、is_featured、最终成绩及返回包装 |
| BC08 | 导入与联调交付 | Q11 | fifa_code/场馆映射、真实数据来源、测试库准备方式、联调时间及第 9 节记录位置 |

C 可直接填写下表回复；“一致”应附代码或 DDL 依据，不表示通过本文件自动修改数据库：

| 确认项 | C 的反馈 |
|---|---|
| 核对人 / 日期 | 待填写 |
| C 开发分支 / 交付提交号 | 已收到 c112acf 回复及 c45f159 Model／Service 交付，a287e22 完成接入 |
| 数据字典、DDL 路径与版本 | docs/data-dictionary.md、docs/er-diagram.md、data/install.sql，已在当前分支还原 |
| NationalTeam / TournamentTeam / TeamStatsService 文件、命名空间及可用版本 | app\\models\\NationalTeam、app\\models\\TournamentTeam、app\\services\\TeamStatsService，c45f159 |
| BC01—BC08 逐项结论与差异 | 待填写 |
| 可联调的测试库及非敏感配置约定 | 待填写；不填写账号密码 |
| 联调日期、测试记录位置、遗留问题负责人 | 待填写 |

球队 PK 与三张 DB 驱动图表页面已由 B 协助完成。C 接下来补充经可靠来源核对的 final_result_code。A 提供合法国旗／封面素材。D 继续独立数据复核，D/C 完成部署还原。B/C 基础集成代码已通过 PR #3 合并到 main，本轮页面更新通过 feature/b-match 的 Pull Request 交付。

## 11. 精选故事的来源核对与发布交接

2026-11-11 重新打开下列来源，核对八场的赛果及表中关键节点。`data/import/sources.csv` 已记录选源说明，复核时须同时核对行级事件与对应来源。八篇故事已按 B 负责人指令发布，本轮同步 is_published=1 到数据包。

| 比赛编号 | 本轮核对的内容 | 可访问的核对来源 |
|---|---|---|
| 56 | 厄瓜多尔 2:1 德国，落后后逆转，德国下半场点球判罚经 VAR 撤销 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M56-ECU-V-GER.pdf)、[DFB 报道](https://www.dfb.de/news/germany-beaten-2-1-by-ecuador-in-final-world-cup-group-game) |
| 69 | 阿尔及利亚与奥地利 3:3，双方补时阶段相继进球 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M69-ALG-V-AUT.pdf)、[AFC 报道](https://www.the-afc.com/en/national/fifa_world_cup.html/news/group-j-algeria-3-3-austria) |
| 75 | 场上 1:1，摩洛哥点球 3:2 晋级，Gakpo 72 分钟和 Diop 90+1 分钟进球 | [J.LEAGUE 比赛记录](https://www.jleague.jp/en/match/japan/2026/062902/)、[CAF 报道](https://www.cafonline.com/fifa-world-cup/news/morocco-stun-netherlands-on-penalties-to-reach-world-cup-last-16/) |
| 82 | 90 分钟 2:2，加时累计 3:2，120+5 的进球为比赛内点球 | [Bundesliga 逐分钟记录](https://www.bundesliga.com/en/world-cup/matchday/2026-2027/round-of-32/belgium-vs-senegal/liveticker) |
| 91 | 巴西 1:2 挪威，Haaland 两球，Neymar 补时点球 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M91-BRA-V-NOR.pdf) |
| 92 | 墨西哥 2:3 英格兰，Bellingham 第 36、38 分钟进球 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M92-MEX-V-ENG.pdf) |
| 103 | 法国 4:6 英格兰，Saka 三球、Bellingham 补时进球 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M103-FRA-V-ENG.pdf) |
| 104 | 西班牙加时 1:0 阿根廷，Torres 106 分钟进球 | [FIFA 赛后报告](https://www.fifatrainingcentre.com/media/native/tournaments/fifa-world-cup/2026/PMSR-M104-ESP-V-ARG.pdf)、[RFEF 报道](https://rfef.es/es/noticias/espana-vuelve-a-dominar-el-mundo) |

M56 的 FIFA 球员摘要显示 Plata 78 分钟，射门记录页显示 77 分钟，SRC-032 已说明这类呈现差异。M69、M91、M92、M104 的来源表也记录了选源依据，D 复核时需保留这类备注。两篇 FIFA 网页版回顾在本次读取中没有返回可读正文，M103、M104 可使用表中可读的官方 PDF。

V1.1 第 3.5 节要求“D执行一次独立复核”，同节要求赛事时间、比分、点球结果经过第二人抽样核验。该独立复核仍待 D 完成。B 负责人已明确要求发布故事，B 已执行发布及前台检查，后续根据复核反馈维护文案与数据包。

八篇故事已整理为 [B 精选故事发布文案](b-story-publication-draft.md)，清理了字段、数据库录入及网站展示策划说明，并已保存、发布和同步到 CSV。A 提供图片时同时提供可用路径及使用依据，封面字段允许为空。
