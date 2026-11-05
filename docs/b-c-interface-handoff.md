# WorldCup Journey：B / C 模块阶段性接口交接文档

| 项目 | 内容 |
|---|---|
| 整理日期 | 2026-11-05 |
| 提供方 / 核对方 | B（赛事与比赛后端）/ C（球队、统计及正式数据库） |
| 当前分支 / 核对代码版本 | `feature/b-match` / `3842640`（已推送的代码基线） |
| 文档状态 | B 阶段代码交付与 C 联调清单；代码已推送不等于正式数据库、跨模块接口已验收 |
| 本次范围 | 记录现状、明确依赖及联调验收；不新增业务代码、DDL 或数据 |

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

当前工作区仍未包含 `models/NationalTeam.php`、`models/TournamentTeam.php`、`services/TeamStatsService.php` 和正式数据库字典；`data/install.sql` 仍只有占位注释。JourneyService、JourneyController、ApiController 及 B 四组后台已经实现并推送，具体见第 2、6、7 节。此处仅描述 B 当前工作区，不推断 C 其他分支是否已完成开发。

已阅读 C 分支 `c112acf` 的 `docs/c-reply-to-b.md`；B 的回复为 [b-confirmation-to-c.md](b-confirmation-to-c.md)，已在 `e0d9e21` 推送。该回复中的“未开发/未提交”等文字是当时状态，当前代码交付以本文为准；其中拟议的事件枚举、分页上限、审计和 FK 方案仍须对齐正式字典及实施版本，不能因推送文档就视为已经落库或实现。

本文不替代 V1.1 或 C 的正式数据字典；若代码行为与双方拟定接口不同，应记录差异并走变更流程，不由任一方私自改名、补列或改变统计口径。

## 2. B 已完成功能与文件

### 2.1 当前实现清单

| 文件 | 已实现内容 | 仍未完成的联调部分 |
|---|---|---|
| [models/Tournament.php](../models/Tournament.php) | 表映射、字段规则、标签、year 唯一性验证配置 | 正式表及数据库唯一约束验证 |
| [models/Stadium.php](../models/Stadium.php) | 表映射、字段规则、标签；国家代码 CA/MX/US | 正式场馆表及真实数据 |
| [models/WcMatch.php](../models/WcMatch.php) | 阶段/状态、双方不同、比分成对、加时累计、获胜方规则；赛事/场馆关联及存在性验证、赛事内编号唯一验证 | C 的球队存在性、参赛资格、球队关联及实际数据库约束 |
| [models/MatchEvent.php](../models/MatchEvent.php) | 分钟/补时/排序字段验证、比赛关联；find() 默认 sort_order 升序 | C 的事件球队验证；建议枚举及完整排序契约冻结 |
| [models/MatchStory.php](../models/MatchStory.php) | 比赛唯一故事、发布标记、内容/路径/URL 规则；比赛、关联表、标签关系 | 审计字段正式策略、素材契约和真实唯一/FK 约束 |
| [models/MatchTag.php](../models/MatchTag.php) | code/name 规则、code 唯一验证、关联表关系 | 正式字典、唯一约束验证 |
| [models/MatchStoryTag.php](../models/MatchStoryTag.php) | 联合主键声明、必填 ID、重复组合验证、故事/标签关联 | 正式联合 PK、FK 及删除规则 |
| [services/MatchQueryService.php](../services/MatchQueryService.php) | 2026 届比赛列表、公开详情、阶段统计；精确过滤/分页、父表预加载、故事发布控制 | 正式数据库查询、执行计划、球队资料集成 |
| [controllers/WcMatchController.php](../controllers/WcMatchController.php) | 请求白名单、ID 校验、依赖注入、GET/HEAD、HTTP 异常传播、View 数据交接 | 真实 HTTP 与数据库联调 |
| [views/wc-match/index.php](../views/wc-match/index.php) | 比赛卡片、阶段/球队 ID 筛选、数组分页、空状态、北京时间 | 球队名称/国旗集成、真实数据及浏览器验收 |
| [views/wc-match/view.php](../views/wc-match/view.php) | 比赛信息、独立比分区域、已发布故事、标签、按原顺序展示事件 | 同上，另需封面素材根目录确认 |
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

表中花括号表示分别存在的 PHP 文件。后台 ID 当前直接录入，不编造球队名称；写入列为已明确的业务字段，不从表单加载主键和审计时间。四组后台均默认拒绝访问，须 D 接入真实管理员授权；路由及权限细节见 [给 D 的交接文档](b-d-admin-handoff.md)。

### 2.3 源数据交付与未完成范围

源 CSV 已在 `c440dda` 推送，位置为 `data/import/`，解析规则及来源说明见 [CSV README](../data/import/README.md)。记录数为 tournament 1、teams 48、stadiums 16、matches 104、stories 8、events 104、tags 16、story_tags 27、sources 57。故事全部保留未发布；这些是文件记录数，不代表已导入数据库或完成独立外部复核。C 统一解析 fifa_code、场馆名和本届 match_no 为内部 ID；空值不得补成比分 0，120 分钟比分不得与 90 分钟相加。具体导入和复核问题由 C/B/D 在测试记录中反馈，本文不重新认定源资料的真实性。

尚未完成：C 的正式 DDL/Model/统计服务接入、真实数据导入与 ID 映射、球队参赛资格及名称展示、事件正式枚举/分钟规则、公开比赛查询 page_size 上限、审计时间维护策略、D 的正式授权，以及真实数据库/浏览器/部署验收。Controller 中早期阶段注释不代表当前文件缺失；本次只更新文档，不修改业务代码。

最近一轮本地检查：19 个 PHP 文件语法通过；故事/事件/标签 160 用例 462 断言，故事删除事务控制流 6/32，比赛后台 62/161，API 43/167，合计 271 用例、822 断言。CRUD、事务和服务依赖使用替代对象，未访问真实数据库；这些数字不代表唯一约束、外键、InnoDB 回滚、C 服务或 D 登录已通过。

## 3. B 七张表的字段与键

### 3.1 通用解释

下表描述 **V1.1 基线及当前 Model 使用的列**，不是从数据库反查出的正式 DDL。

- `NN` 表示非空，`NULL` 表示允许缺失，`PK/FK/UQ` 分别表示主键、外键、唯一约束。
- 普通 `id` 按 V1.1 第 3.1 节为 `BIGINT UNSIGNED AUTO_INCREMENT`；关联表使用联合主键，不另加 id。精选表的 BIGINT 简写按此统一解释，C 必须核实真实两端类型一致。
- B 七表明确的业务默认值仅为 `status='SCHEDULED'`、`sort_order=0`、`is_published=0`。其他默认值未冻结；不能自行改成空串、0、当前时间或 DEFAULT NULL。
- 当前仅 MatchStory 显式使用 `created_at/updated_at`；其 NULL、默认值、维护机制未冻结。其他六表的审计列是否采用通用建议仍待确认，不在本文件加列。
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
| team_id | BIGINT UNSIGNED | NULL，FK → national_team.id；具体事件归属校验待 C 联调 |
| player_name | VARCHAR(100) | NULL，球员显示名；不另建球员表 |
| minute | SMALLINT UNSIGNED | NULL，基础分钟 |
| added_minute | SMALLINT UNSIGNED | NULL，补时分钟；45+2 分开存储 |
| period_code | VARCHAR(12) | NN，时段代码；建议值未冻结 |
| event_type | VARCHAR(20) | NN，事件代码；建议值未冻结 |
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
| cover_path | VARCHAR(255) | NULL，本地素材相对路径；根目录待确认 |
| source_url | VARCHAR(600) | NULL，内容来源；当前仅链接有效 HTTP/HTTPS URL |
| is_published | TINYINT(1) | NN，默认 0；未发布内容不提供给前台 |
| created_at | DATETIME | UTC；NULL、默认值、维护方待确认 |
| updated_at | DATETIME | UTC；NULL、默认值、维护方待确认 |

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
| national_team 1:N wc_match（三个不同角色） | C → B | home/away/winner 关联尚未实现；不能用同一个属性混合角色 |
| tournament、national_team 经 tournament_team 建立参赛关系 | B/C → C | C 提供联合 PK/FK；B 当前没有相关 AR 关系 |
| wc_match 1:N match_event | B → B | MatchEvent::getMatch()；事件球队关联尚未实现 |
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
| page_size | 缺省为 20；校验同 page；当前没有另设业务上限，是否限流/限页大小待 B/C 确认 |

stage 与 team_id 可同时筛选。Service 拒绝未知键、负数、0、浮点数、科学计数、数组、布尔值等不合法输入；page/page_size 显式 null 或空串返回 400。球队数字 ID 格式合法但不存在或不参赛时，当前只会查得空列表，不做球队存在性/资格验证；是否补充区分 404，见 BC02。

返回结构（类型说明，不是演示赛果）：

```text
{
  items: MatchItem[],
  pagination: {page: int, page_size: int, total_count: int, page_count: int}
}
```

`MatchItem` 为第 3.4 节的 18 个比赛列，加以下键；目前没有 `home_team`、`away_team`、球队名称或国旗等嵌套字段：

| 附加键 | 当前结构 |
|---|---|
| tournament | `{id, year, name, start_date, end_date, description}` 或 null |
| stadium | `{id, name, city, country_code, timezone_name}` 或 null |
| final_score | `{home: int或null, away: int或null}`；仅 FINISHED 提供最终场上比分，优先 120 分钟，否则 90 分钟，不含点球 |

列表按 `kickoff_utc ASC, match_no ASC, id ASC` 稳定排序，预加载 tournament/stadium；不连接一对多事件/标签来放大比赛记录或总数。当前列表包含取消场次；仅阶段统计排除 CANCELLED。

空查询在 page=1 返回 `items=[]`、`total_count=0`、`page_count=0`，保留请求页大小。页码超出 `max(1,page_count)` 返回 400，不钳制到最后一页。使用方不能把默认首批 20 条当作球队的全部比赛。

### 4.3 getMatchDetail

```php
public function getMatchDetail(int $matchId): array
```

返回 **MatchItem 的平铺字段**，再增加下表三个键；不是 `{match: {...}}` 包装。`$matchId<1` 返回 400；不存在或不属于 2026 届的比赛返回 404。

| 键 | 当前结构与发布边界 |
|---|---|
| story | null，或第 3.6 节 10 个故事列；返回时 is_published 为布尔 true |
| events | 第 3.5 节 10 个事件列组成的数组；按 sort_order ASC、id ASC；不按 minute 重排 |
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
| GET/HEAD `index.php?r=wc-match/index` | 仅提取 stage、team_id、page、page_size；忽略路由参数及其他 GET 键；向 index.php 传 `matches=result.items`、`pagination=result.pagination`、`filters` |
| GET/HEAD `index.php?r=wc-match/view&id=...` | 严格正整数 ID，非法为 400、查无为 404；向 view.php 传 `match=Service完整详情数组` |

filters 固定含 stage、team_id、page、page_size；stage/team_id 保留有效原始输入，page/page_size 使用 Service 返回值。列表筛选以 GET 提交并重置 page=1，分页保留 stage/team_id/page_size。

Controller 采用构造器注入 MatchQueryService；写方法被 VerbFilter 拒绝为 405。两个入口返回 HTML，不是 JSON API。只读 JSON API 已在 `3842640` 实现，见第 7.3 节；不改变这两个 HTML 入口。

## 5. C 需要提供的球队 Model 与 B 的依赖

### 5.1 文件、字段访问及职责（V1.1 基线；实现待 C 交付）

| 交付文件 / 类 | 表与已约定字段 | B 使用方式 |
|---|---|---|
| `models/NationalTeam.php` / `app\models\NationalTeam` | national_team：id、fifa_code、name_zh、name_en、flag_path、description、is_active | AR 属性读取球队身份、名称、素材；球队 ID 存在性检查；不由 B 再建球队 Model |
| `models/TournamentTeam.php` / `app\models\TournamentTeam` | tournament_team：tournament_id、team_id、group_code、final_result_code | 联合 PK；查本届资格、分组、最终成绩，不由 B 维护另一份参赛关系 |

以上为交付目标，不是当前可调用类。B 建议两类继承 Yii2 ActiveRecord，支持框架的 find()/findOne() 等标准查询，不要求 C 为每一种 B 查询新增专用方法。

| C 字段 | V1.1 类型 / 约束 | 读取约定与待确认点 |
|---|---|---|
| national_team.id | BIGINT UNSIGNED，PK | B 所有球队 FK 的唯一目标；不以名称或 fifa_code 代替内部 ID |
| fifa_code | CHAR(3)，NN UQ | 源数据映射键、三字母显示标识；不是另一个数字主键 |
| name_zh / name_en | VARCHAR(80) / VARCHAR(100)，NN | 中文/英文展示名；建议名称缺失的显示回退由展示层处理，具体优先级待确认 |
| flag_path | VARCHAR(255)，NULL | 本地合法素材相对路径；根目录、文件存在性及缺图回退由 B/C/A 对齐，不伪造队徽 |
| description | TEXT，NULL | 球队简介；前台编码输出 |
| is_active | TINYINT(1)，NN 默认 1 | 停用不能破坏历史关联；需确认 C 的默认查询是否隐藏停用球队 |
| tournament_team.tournament_id / team_id | BIGINT UNSIGNED，联合 PK，各自 FK | 分别引用 tournament.id、national_team.id |
| group_code | CHAR(1)，NN，A—L | 赛事内分组，不从球队基础表猜测 |
| final_result_code | VARCHAR(20)，NULL | 最终成绩类别；无可靠结论保持 null，见第 8.2 节 |

V1.1 没有独立的 `short_name`、`abbreviation`、`logo_url` 字段。本轮不创造这些列或接口承诺；“简称”若仅指三字母码可用 fifa_code，如需其他简称，应先与 C 确认并更新正式字典。

### 5.2 ID 映射与本届参赛资格

1. B 提交源 CSV/JSON 时，球队使用经核验的 fifa_code、场馆使用官方名称，C 统一映射为内部主键。不能各自生成互不一致的球队 ID，也不能把 FIFA 外部数字编号直接当 national_team.id。
2. home_team_id、away_team_id、winner_team_id 均引用同一 national_team.id；三列不是 tournament_team 的不同 ID，也不是 fifa_code。
3. 先通过 tournament.year=2026 取得真实赛事 ID，再用 `(tournament_id, team_id)` 查询 tournament_team。球队存在不等于参加 2026；双方必须各有该赛事参赛记录。
4. home_team_id 与 away_team_id 不同。winner_team_id 可空；有值只能为本场双方之一，且遵循阶段、状态、场上及点球规则。
5. match_event.team_id 可空，非空引用同一球队 ID 体系；是否必须属于本场双方、空值的业务含义由 BC03 确认。
6. 数据导入顺序沿用 V1.1：赛事 → 球队 → 场馆 → 参赛关系 → 比赛 → 故事/事件/标签/关联。C 维护最终 install.sql 和映射程序，B 提供赛事源数据与来源复核记录。

### 5.3 关系方法建议（全部待确认，当前没有这些新增方法）

| 拟归属文件 | 建议签名 | 关联映射与用途 |
|---|---|---|
| C：NationalTeam.php | `public function getTournamentTeams(): ActiveQuery` | hasMany TournamentTeam，`['team_id' => 'id']`；返回各届参赛记录 |
| C：TournamentTeam.php | `public function getTeam(): ActiveQuery` | hasOne NationalTeam，`['id' => 'team_id']` |
| C：TournamentTeam.php | `public function getTournament(): ActiveQuery` | hasOne Tournament，`['id' => 'tournament_id']` |
| B：WcMatch.php，后续协商后实现 | `getHomeTeam(): ActiveQuery`、`getAwayTeam(): ActiveQuery`、`getWinnerTeam(): ActiveQuery` | 分别 hasOne NationalTeam，目标 id 对应 home_team_id、away_team_id、winner_team_id；可空获胜方返回 null |
| B：MatchEvent.php，后续协商后实现 | `getTeam(): ActiveQuery` | hasOne NationalTeam，`['id' => 'team_id']`；team_id 为空时无关联 |

ActiveQuery 类型指 `yii\db\ActiveQuery`。关系定义不等于参赛资格验证；B 后续还需补 C 表存在性及 tournament_team 检查。C 应提供类命名空间、字段属性、联合主键声明和实际关系方法名；若已有不同命名，先反馈再集成，不要求 C 擅自覆盖已有实现。

## 6. JourneyService 已实现接口及 C 接入需求

### 6.1 当前代码与签名

文件：`services/JourneyService.php`，类 `app\services\JourneyService`；公开方法：

```php
public function getTeamJourney(int $teamId): array
```

入口：GET/HEAD `index.php?r=journey/view&team_id=...`。服务查询 2026 届全部匹配比赛，不分页；按 kickoff_utc、match_no、id 升序。非法 ID 返回 400；当前尚不验证球队是否存在或参赛，空数组只表示无匹配比赛，不能据此断定球队不存在。

### 6.2 实际返回结构

```text
JourneyItem = {
  match_id: int|string, match_no: int,
  kickoff_utc: string, stage_code: string, status: string,
  opponent: {team_id: int|string, name_zh: null},
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
- opponent.name_zh 当前为 null，不伪造队名；不输出或推算 final_result_code，也不增加顶层球队/排名包装。

### 6.3 请 C 提供后由 B 接入

| 所需能力 | C 交付内容 | B 接入位置 |
|---|---|---|
| 球队基本信息 | NationalTeam 的 id、name_zh、name_en、fifa_code、flag_path；实际命名空间和查询行为 | Journey 与比赛列表/详情读取真实名称及素材 |
| 本届参赛资格 | TournamentTeam 联合 PK、getTeam()/getTournament() 等实际关系；以 tournament.year=2026 定位赛事 | B 增加球队存在性、参赛资格及事件球队验证 |
| 最终成绩 | tournament_team.final_result_code 的可靠值或 null | Journey 成绩展示；不从最大阶段猜测名次 |
| 异常语义 | C 已回复建议未知/非参赛球队 404；正式实现及停用历史策略仍需交付 | 当前 Journey 的空数组行为须由 B 在正式接入时调整并测试 |

以上是当前 B 侧阶段实现及待接入任务，不意味着 C 已提供自定义 getTeamInfo()/isParticipant() 等方法。

## 7. TeamStatsService 接口需求（C 负责，尚未在当前仓库交付）

### 7.1 V1.1 既定实例方法

目标文件：`services/TeamStatsService.php`，目标类：`app\services\TeamStatsService`；C 确认最终命名空间与构造依赖。

```php
public function getTeamStats(int $teamId): array
public function getCompareStats(int $leftId, int $rightId): array
public function getScoringRanking(): array
```

| 方法 | 所需返回 | B 的用途 |
|---|---|---|
| getTeamStats | 下节单队统一统计数组 | 已有 ApiController::actionTeamStats() 调用入口；B 不重写球队聚合 SQL |
| getCompareStats | `{left: TeamStats, right: TeamStats}`，对应输入顺序 | 已有 ApiController::actionTeamCompare() 调用入口；两边复用相同统计规则 |
| getScoringRanking | 2026 届 48 队完整统计数组；C 已回复排序建议，实施待交付 | V1.1 主要供 A/C 排名图表；B 不擅自新增 API 路由 |

### 7.2 getTeamStats 返回字段

字段名称与业务含义来自 V1.1；下列 PHP 类型是联调核对建议，不代表 C 已实现的具体类型。

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

C 在 `c112acf` 回复建议 getScoringRanking 每项返回完整 TeamStats，按 goals_for DESC、fifa_code ASC、team_id ASC 排序，含无完赛球队；B 回复拟接受。请 C 交付实际实现与字段类型，排名不新增 B 的 API 路由。

C 已回复未知球队及未参加 2026 返回 404，同队比较返回 400；无完赛时场次/进失球为 0、比率为 null；赛事未初始化或数据库失败返回 500。B API 已拒绝同队比较并映射 HTTP 异常；请 C 交付实现及真实测试证据，不能各方法暗自使用不同包装。

### 7.3 已实现的 API 调用与响应

文件：`controllers/ApiController.php`，命名空间 `app\controllers`，提交 `3842640`。

| 路由（均仅 GET） | Action 签名 | 调用 |
|---|---|---|
| api/team-stats?id=... | actionTeamStats(mixed $id = null): array | C：getTeamStats(int $teamId): array |
| api/team-compare?left_id=...&right_id=... | actionTeamCompare(mixed $left_id = null, mixed $right_id = null): array | C：getCompareStats(int $leftId, int $rightId): array |
| api/insights?chart=stage-goals | actionInsights(mixed $chart = null): array | B：getStageSummary(): array |

默认地址示例：`index.php?r=api/insights&chart=stage-goals`。MatchQueryService 使用构造器注入；C 服务由服务器端配置 `ApiController::$teamStatsService` 实例，或在首次球队请求时按 `app\services\TeamStatsService` 经 Yii::createObject 创建。构造依赖如有特殊要求请 C 明确，不将依赖从请求参数加载。

C 服务尚未在 B 工作区交付时，球队接口返回友好 500（SERVER_ERROR），不会伪造统计；阶段接口仍调用真实 B 服务，其数据库未就绪时同样不能验收为成功。

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

全部 FK 的目标类型须一致，尤其七表精选部分不能因 BIGINT 简写而错误使用有符号列。所有 ON UPDATE 子句、外键名及未逐项冻结的删除策略均待 C 明确。禁止 B 另写一套“最终 SQL”。

### 8.2 枚举及比分口径

| 对象 | 当前依据与代码 | 确认边界 |
|---|---|---|
| stage_code | GROUP / R32 / R16 / QF / SF / THIRD / FINAL；WcMatch::STAGE_CODES | V1.1 已明确；DB 采用 VARCHAR，CHECK 实施由 C 确认，不改造为未协商 ENUM |
| status | SCHEDULED / IN_PROGRESS / FINISHED / POSTPONED / CANCELLED；默认 SCHEDULED | V1.1 已明确；IN_PROGRESS 是人工状态，不承诺实时比分 |
| group_code | A—L；wc_match 仅 GROUP 填写；tournament_team 为参赛分组 | 当前 WcMatch 允许 GROUP 的 group_code 为空；是否需更严格必填不得未经协商增加 |
| period_code | 建议 H1 / H2 / ET1 / ET2 / SHOOTOUT | 尚未冻结，当前 MatchEvent 只校验字符串长度，不固定白名单 |
| event_type | 建议 GOAL / OWN_GOAL / YELLOW / RED / PENALTY_GOAL / PENALTY_MISS / OTHER | 同上；点球事件是否大战由 period_code 区分 |
| final_result_code | 建议 GROUP / R32 / R16 / QF / FOURTH / THIRD / RUNNER_UP / CHAMPION | C 确认集合与含义；这是最终成绩类别，不是 stage_code 或 1—48 的官方排名 |
| 标签代码 | 仅有示例 | 不把示例当成固定数据库枚举或默认值 |
| 事件排序 | 当前 Service 为 sort_order ASC、id ASC，View 不重排 | 完整跨时段口径待确认；不能仅按 minute+added_minute 推断时间线 |

业务核对必须同时覆盖：

1. 双方不同、均属于该赛事；winner 非空只能为双方之一，事件球队范围待确认。
2. FINISHED 必填 90 分钟成对比分；120 分钟可空但成对，有值表示累计值且不小于对应 90 分钟值。
3. 点球成对、单列展示，不计场上进球；点球胜方与 winner_team_id 一致。
4. 已结束的淘汰赛、季军赛和决赛必须有 winner_team_id；小组赛不填。场上平局经点球获胜仍计 draws，penalty_wins 独立增加。
5. SCHEDULED / POSTPONED / CANCELLED 不能填写虚构赛果。状态迁移与 IN_PROGRESS 的编辑限制还需补充，不由本文件猜测。
6. 一场比赛最多一篇故事；关联表组合不能重复；未发布故事、事件及标签不可泄漏。
7. 统计以 wc_match 的正式比分为事实源，不从不完整的精选事件计数进球；null 与真实 0 不能混用。

### 8.3 配置、数据与素材交付

- `config/db.php` 加载 db.example.php 并叠加被 Git 忽略的 db.local.php；C 提供正式库名、端口等非敏感约定，账号密码仅存各自本地，不写进交接文档。
- 示例连接使用 utf8mb4、MySQL 原生预处理、连接时区 +00:00；实际服务器版本与正式环境由联调记录确认，不以配置文件存在代替连接成功。
- V1.1 目标：2026 赛事 1 条、48 球队/48 条本届参赛记录、16 场馆、104 比赛（72 小组赛+32 淘汰赛）、5—8 场精选故事。数量是验收目标，不是当前已导入事实。
- B 提供真实赛程、场馆、精选内容及来源；C 合并导入程序、内部 ID 映射和最终 install.sql；A 协调合法国旗/图片素材，D 独立复核。
- 当前详情封面暂按 web/ 下相对路径解析，拒绝外链和路径穿越；球队国旗路径根目录尚无接入实现。故事正文当前为纯文本编码并保留换行，富文本格式如需采用须另行确认净化规则。

## 9. 双方联调测试清单（全部待执行，不能按勾选示例视为已通过）

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
| T20 | Journey 全量与主客互换 | 不丢分页后的比赛；按时间、编号排序，对手和进失球视角正确 | B+C，代码已实现、待真实联调 |
| T21 | 晋级、决赛/季军赛、最终成绩未知 | 按 BC07 决议展示；不把最大阶段或未知成绩冒充官方排名 | B+C，代码已实现、待真实联调 |
| T22 | 统计无完赛、同队 PK、排名并列 | 比率不除零、统一空集语义和并列顺序，left/right 保持输入顺序 | C+B |
| T23 | UTC 跨日和大 ID | 北京时间跨日正确；ID 不截断、不经 JavaScript 浮点误改；ISO/数据库格式按接口区别处理 | B+C |
| T24 | 名称/故事/标签 XSS、素材路径 | 文本编码、危险 URL 拒绝；桌面和移动端真实浏览器检查 | B，A 协助 |
| T25 | 数据库失败与 API 封装 | 服务端日志有记录，用户信息友好；API HTTP 状态及统一包装正确，不暴露敏感信息 | B+C，代码已实现、待真实联调 |

每项建议留存：测试日期、操作者、提交号、数据库版本、输入/预期/实际、结果和截图或日志位置。Journey/API 已有代码，其真实数据用例仍待执行；本地隔离测试不能替代这些验收项。

## 10. 请 C 反馈的确认事项

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
| C 开发分支 / 交付提交号 | 已收到 C 分支 c112acf 的确认回复；Model/Service/DDL 实现提交号仍请 C 提供 |
| 正式数据字典、DDL、install.sql 路径与版本 | 待填写 |
| NationalTeam / TournamentTeam / TeamStatsService 文件、命名空间及可用版本 | 待填写 |
| BC01—BC08 逐项结论与差异 | 待填写 |
| 可联调的测试库及非敏感配置约定 | 待填写；不填写账号密码 |
| 联调日期、测试记录位置、遗留问题负责人 | 待填写 |

下一步顺序：C 返回正式字典/DDL及可用 Model/Service 提交号 → 双方对齐未决规则与 ID 映射 → C 准备独立测试库并导入已交付 CSV → B 补球队关联、资格与展示、事件规则等确认项 → B/C 验证比赛查询、Journey、后台及 API → D 接入授权并参与空库恢复与完整流程验收。接口变更先核对契约，各自维护本模块；本次只更新交接文档，不修改业务代码、SQL 或其他成员文件。
