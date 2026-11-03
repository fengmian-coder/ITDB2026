# WorldCup Journey：B 模块数据库字段与接口契约确认单

| 项目 | 内容 |
|---|---|
| 文档状态 | 核对稿，待 B/C 确认；不是已执行的 DDL 或实现证明 |
| 整理人 / 核对人 | B / C |
| 整理日期 | 2026-11-03 |
| 唯一技术基线 | 《WorldCup_Journey_项目开发协作规范与接口约定_V1.1.md》 |
| 依据章节 | 第 3.1—3.5 节数据库规范与字典，第 4 节统计口径，第 5 节服务与路由，第 6 节职责 |
| 范围 | B 的七张表，以及与 C 的球队、参赛关系和统计服务的依赖 |

请 C 按本文的待确认编号反馈，并提供正式字段字典 / DDL 版本或提交号。已经由 V1.1 明确的内容应核对落库一致性；如需变更，应按 V1.1 第 9.2 节协调并更新基线，不能仅在一端修改。

本文件只整理设计和待确认事项，不创建数据库、不执行 SQL、不创建 Model，不替代或修改由 C 维护的 `data/install.sql`。当前工作区该 SQL 仍为注释占位，未发现 C 确认的独立字段字典或正式 DDL，不能将本文标记为“数据库已实现”。

## 1. 阅读标记与确认原则

| 标记 / 写法 | 含义 |
|---|---|
| 已明确 | V1.1 已给出字段、类型、约束或业务语义，本文仅整理 |
| 基线建议 | V1.1 使用“建议”“可以”等措辞，不等于必须采用的最终 DDL |
| 待确认 / 未明确 | V1.1 未写明，或尚需 C 说明具体落地方式；不得自行补全 |
| 否（PK） | 由已明确的主键含义得出的非空要求，不表示原字段行另写了 NN |
| 默认值“未明确” | 不等于 `DEFAULT NULL`、空字符串、0、当前时间，也不等于“明确没有默认值” |
| 自增 | 第 3.1 节规定的主键生成方式，不是 SQL `DEFAULT` 值 |

共同原则：

- 第 3.1 节规定普通主键为 `id BIGINT UNSIGNED AUTO_INCREMENT`，外键与被引用主键类型一致；多对多表可用联合主键。
- 第 3.3 节对精选内容部分使用 `BIGINT` 简写。下表按第 3.1 节统一写为 `BIGINT UNSIGNED`，并要求 C 核对实际有无 `UNSIGNED`；这不是对现有 DDL 已完成核验的结论。
- 可空字段的缺失值语义与数据库默认值是两项不同约定。表中允许 NULL，不代表数据库已指定 `DEFAULT NULL`。
- `created_at/updated_at` 只使用 V1.1 已提及的名称，逐表情况另见第 2.8 节；不得因为“通常都有”而自动为所有表增加字段。
- 数据库名 `worldcup_journey` 属于基线建议；字符集按 `utf8mb4`，排序规则统一选用 MySQL 8 的 `utf8mb4_0900_ai_ci`，调整时全组同步。C 需确认正式库名和 DDL 设置。
- 比赛及审计时间按 UTC 存储；前台转换为 `Asia/Shanghai` 并标注北京时间。`DATETIME` 本身不携带时区。

## 2. 七张表的逐字段确认

各表最后一列中的 Q 编号对应第 7 节。没有明确默认值的字段均由 Q01 统一确认；不得把业务示例值直接作为字段默认值。

### 2.1 tournament：赛事信息（B）

依据：V1.1 第 3.1、3.3 A 节。首期只维护 2026 届赛事，但 `year=2026` 不是文档指定的数据库默认值。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | 否（PK） | 未明确；采用自增机制 | PK | 赛事内部 ID | 按第 3.1 节落实自增及引用类型，见 Q02 |
| `year` | `SMALLINT UNSIGNED` | 否 | 未明确 | 非 PK/FK；UQ | 赛事年份，本期为 2026 | 唯一约束落地；默认值见 Q01 |
| `name` | `VARCHAR(120)` | 否 | 未明确 | 非 PK/FK | 赛事名称 | 默认值见 Q01；不把名称示例当默认值 |
| `start_date` | `DATE` | 否 | 未明确 | 非 PK/FK | 赛事开始日期 | 默认值见 Q01 |
| `end_date` | `DATE` | 否 | 未明确 | 非 PK/FK | 赛事结束日期 | 默认值见 Q01 |
| `description` | `TEXT` | 是 | 未明确 | 非 PK/FK | 赛事简介 | 缺失内容与空字符串的处理、默认值，见 Q01 |

`created_at/updated_at` 是否逐表纳入、是否非空及维护方式见第 2.8 节、Q03；本表不自行添加软删除字段。

### 2.2 stadium：比赛场馆（B）

依据：V1.1 第 3.3 A 节。目标数据覆盖 16 座场馆；数量是数据验收要求，不是字段默认值或额外表字段。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | 否（PK） | 未明确；采用自增机制 | PK | 场馆内部 ID | 自增及外键类型一致性，见 Q02 |
| `name` | `VARCHAR(120)` | 否 | 未明确 | 非 PK/FK | 可核验的官方比赛场馆名称 | 文档未声明名称唯一；若需唯一约束须共同确认，见 Q04 |
| `city` | `VARCHAR(100)` | 否 | 未明确 | 非 PK/FK | 所在城市 | 默认值见 Q01 |
| `country_code` | `CHAR(2)` | 否 | 未明确 | 非 PK/FK | `CA` / `MX` / `US` | 代码校验如何落地，见 Q05；默认值见 Q01 |
| `timezone_name` | `VARCHAR(64)` | 是 | 未明确 | 非 PK/FK | 时区名称，例如 `America/Toronto` | 缺失值处理见 Q01；示例不等于统一默认时区 |

审计字段见第 2.8 节、Q03。不能因为允许受控软删除而擅自添加 `deleted_at` 或状态字段。

### 2.3 wc_match：比赛赛程与结果（B）

依据：V1.1 第 3.3 A 节及第 4 节。2026 届覆盖 104 场比赛；阶段、状态、比分等约束详见第 4 节。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | 否（PK） | 未明确；采用自增机制 | PK | 比赛内部 ID，供页面与跨模块引用 | 自增与引用类型见 Q02 |
| `tournament_id` | `BIGINT UNSIGNED` | 否 | 未明确 | FK → `tournament.id` | 所属赛事 | FK、删除规则见 Q02、Q06 |
| `match_no` | `SMALLINT UNSIGNED` | 否 | 未明确 | 非 PK/FK；联合 UQ 组成字段 | 本届比赛编号，1—104 | 与赛事组成唯一键，不是全表单列唯一，见 Q04 |
| `home_team_id` | `BIGINT UNSIGNED` | 否 | 未明确 | FK → C 的 `national_team.id` | 展示顺序中的主队 | 双方不同及参赛资格校验见 Q05 |
| `away_team_id` | `BIGINT UNSIGNED` | 否 | 未明确 | FK → C 的 `national_team.id` | 展示顺序中的客队 | 双方不同及参赛资格校验见 Q05 |
| `stadium_id` | `BIGINT UNSIGNED` | 否 | 未明确 | FK → `stadium.id` | 举办场馆 | FK 配套索引、删除规则见 Q04、Q06 |
| `kickoff_utc` | `DATETIME` | 否 | 未明确 | 非 PK/FK | UTC 开球时间 | 导入和时间格式转换交接见 Q11；不默认当前时间 |
| `stage_code` | `VARCHAR(10)` | 否 | 未明确 | 非 PK/FK | 比赛阶段代码 | 允许值已明确；数据库及模型校验分工见 Q05 |
| `group_code` | `CHAR(1)` | 是 | 未明确 | 非 PK/FK | 仅小组赛填写 A—L | 阶段与分组一致性校验落地，见 Q05 |
| `status` | `VARCHAR(16)` | 否 | `SCHEDULED`（已明确） | 非 PK/FK | 人工维护或导入的比赛状态 | 枚举与比分条件校验落地，见 Q05 |
| `home_score_90` | `TINYINT UNSIGNED` | 是；FINISHED 时必填 | 未明确 | 非 PK/FK | 主队 90 分钟含补时比分 | 成对填写及状态约束见 Q05；不能默认 0 冒充赛果 |
| `away_score_90` | `TINYINT UNSIGNED` | 是；FINISHED 时必填 | 未明确 | 非 PK/FK | 客队 90 分钟含补时比分 | 同上 |
| `home_score_120` | `TINYINT UNSIGNED` | 是；成对填写 | 未明确 | 非 PK/FK | 主队累计至加时结束的比分 | 仅踢加时时填写，不小于对应 90 分钟比分，见 Q05 |
| `away_score_120` | `TINYINT UNSIGNED` | 是；成对填写 | 未明确 | 非 PK/FK | 客队累计至加时结束的比分 | 同上 |
| `home_penalties` | `TINYINT UNSIGNED` | 是；成对填写 | 未明确 | 非 PK/FK | 主队点球大战命中数 | 仅点球决胜填写，不计总进球，见 Q05 |
| `away_penalties` | `TINYINT UNSIGNED` | 是；成对填写 | 未明确 | 非 PK/FK | 客队点球大战命中数 | 同上 |
| `winner_team_id` | `BIGINT UNSIGNED` | 是；特定完赛场景必填 | 未明确 | FK → C 的 `national_team.id` | 淘汰赛、季军赛或决赛最终获胜方 | 必须为本场双方之一；与阶段、比分的约束见 Q05 |
| `summary` | `VARCHAR(300)` | 是 | 未明确 | 非 PK/FK | 一句话摘要，不是故事正文 | 缺失值与默认值见 Q01 |

审计字段见第 2.8 节、Q03。不增加冗余的球队进球、胜率或独立 PK 统计字段。

### 2.4 match_event：精选比赛事件（B）

依据：V1.1 第 3.3 B 节；`BIGINT` 的统一展开规则见第 1 节。事件表服务于精选时间轴，不是比分统计的事实来源。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED`（3.1；3.3 简写 BIGINT） | 否（PK） | 未明确；采用自增机制 | PK | 事件 ID | 实际 UNSIGNED、自增见 Q02 |
| `match_id` | `BIGINT UNSIGNED`（依 3.1 对齐） | 否 | 未明确 | FK → `wc_match.id` | 所属比赛 | 实际类型、删除规则见 Q02、Q06 |
| `team_id` | `BIGINT UNSIGNED`（依 3.1 对齐） | 是 | 未明确 | FK → C 的 `national_team.id` | 可选关联球队 | NULL 的业务含义、球队与该场关系校验口径见 Q01、Q05 |
| `player_name` | `VARCHAR(100)` | 是 | 未明确 | 非 PK/FK | 事件相关球员姓名文本 | 缺失值见 Q01；不新增球员表或球员 ID |
| `minute` | `SMALLINT UNSIGNED` | 是 | 未明确 | 非 PK/FK | 基础显示分钟数，例如 45、67、105 | 非点球阶段的必填条件、排序见 Q07 |
| `added_minute` | `SMALLINT UNSIGNED` | 是 | 未明确 | 非 PK/FK | 补时分钟数；45+2 中的 2 | 无补时时用 NULL 还是 0，及排序见 Q07 |
| `period_code` | `VARCHAR(12)` | 否 | 未明确 | 非 PK/FK | 所属比赛时段 | 文档所列代码属于建议值，需冻结，见 Q07 |
| `event_type` | `VARCHAR(20)` | 否 | 未明确 | 非 PK/FK | 事件类型 | 文档所列代码属于建议值，需冻结，见 Q07 |
| `description` | `VARCHAR(500)` | 是 | 未明确 | 非 PK/FK | 事件描述 | 缺失值、默认值见 Q01 |
| `sort_order` | `INT` | 否 | `0`（已明确） | 非 PK/FK | 同一分钟内稳定排序 | 不擅自改为 UNSIGNED；排序方向、优先级及并列处理见 Q07 |

基线建议的 `period_code`：`H1`、`H2`、`ET1`、`ET2`、`SHOOTOUT`。

基线建议的 `event_type`：`GOAL`、`OWN_GOAL`、`YELLOW`、`RED`、`PENALTY_GOAL`、`PENALTY_MISS`、`OTHER`。

上述建议不能在尚未共同冻结时写成已实施的数据库 ENUM。`PENALTY_GOAL/PENALTY_MISS` 属于常规比赛点球还是点球大战，由 `period_code` 区分。审计字段见第 2.8 节。

### 2.5 match_story：精选比赛故事（B）

依据：V1.1 第 3.3 B 节；明确列有 `created_at/updated_at`，但没有逐字段规定其 NULL、默认值及自动维护方式。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED`（3.1；3.3 简写 BIGINT） | 否（PK） | 未明确；采用自增机制 | PK | 故事 ID | UNSIGNED、自增见 Q02 |
| `match_id` | `BIGINT UNSIGNED`（依 3.1 对齐） | 否 | 未明确 | FK → `wc_match.id`；UQ | 对应的一场比赛 | 必须是唯一外键，以落实一场最多一篇故事，见 Q04 |
| `title` | `VARCHAR(150)` | 否 | 未明确 | 非 PK/FK | 故事标题 | 默认值见 Q01 |
| `summary` | `VARCHAR(500)` | 否 | 未明确 | 非 PK/FK | 故事摘要 | 与 wc_match.summary 区分；默认值见 Q01 |
| `body` | `TEXT` | 否 | 未明确 | 非 PK/FK | 故事正文 | 默认值见 Q01；正文存储格式及展示净化约定见 Q08 |
| `cover_path` | `VARCHAR(255)` | 是 | 未明确 | 非 PK/FK | 封面相对路径 | 缺失封面处理与路径交接见 Q01、Q08 |
| `source_url` | `VARCHAR(600)` | 是 | 未明确 | 非 PK/FK | 内容来源 URL | 默认值见 Q01；可空不表示免除事实与素材许可复核 |
| `is_published` | `TINYINT(1)` | 否 | `0`（已明确） | 非 PK/FK | 精选内容发布开关 | 发布与前台返回边界核对，见 Q08 |
| `created_at` | `DATETIME`（3.1、3.3 通用约定） | 未明确 | 未明确 | 非 PK/FK | UTC 创建时间 | NULL、默认值、由谁填写，见 Q03 |
| `updated_at` | `DATETIME`（3.1、3.3 通用约定） | 未明确 | 未明确 | 非 PK/FK | UTC 修改时间 | NULL、默认值、更新机制，见 Q03 |

### 2.6 match_tag：比赛标签字典（B）

依据：V1.1 第 3.3 B 节。文档给出的标签代码是示例，不是全部必需初始数据，也不是默认值。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `id` | `BIGINT UNSIGNED`（3.1；3.3 简写 BIGINT） | 否（PK） | 未明确；采用自增机制 | PK | 标签 ID | UNSIGNED、自增见 Q02 |
| `code` | `VARCHAR(40)` | 否 | 未明确 | 非 PK/FK；UQ | 唯一英文标签代码 | 唯一约束见 Q04；默认值见 Q01 |
| `name` | `VARCHAR(40)` | 否 | 未明确 | 非 PK/FK | 标签显示名称 | 未声明 name 唯一，不自行增加唯一约束；默认值见 Q01 |

基线示例：`PENALTY_SHOOTOUT`、`COMEBACK`、`HIGH_SCORING`、`KNOCKOUT`。只读字典表允许适当省略审计字段，但是否将本表按此处理仍需 C 确认，见 Q03。

### 2.7 match_story_tag：故事与标签关联（B）

依据：V1.1 第 3.1、3.3 B 节。本表没有单独 `id` 字段，不添加自增 ID。

| 字段名称 | 数据类型 | 是否允许 NULL | 默认值 | 主键或外键 | 字段用途（已明确） | 需要 C 确认的事项 |
|---|---|---|---|---|---|---|
| `story_id` | `BIGINT UNSIGNED`（依 3.1；3.3 简写 BIGINT） | 否（PK） | 未明确 | 联合 PK 组成字段；FK → `match_story.id` | 关联故事 | 联合 PK、实际 UNSIGNED、删除规则见 Q02、Q04、Q06 |
| `tag_id` | `BIGINT UNSIGNED`（依 3.1；3.3 简写 BIGINT） | 否（PK） | 未明确 | 联合 PK 组成字段；FK → `match_tag.id` | 关联标签 | 联合 PK、反向索引、标签删除规则见 Q02、Q04、Q06 |

联合主键为 `(story_id, tag_id)`。V1.1 未逐表明确本关联表的审计字段，不自动添加，见 Q03。

### 2.8 统一审计字段的逐表核对

第 3.1 节使用“主表建议有”的措辞，第 3.3 节说明未列入的统一审计字段为 `created_at/updated_at DATETIME`；只有 `match_story` 在具体定义中再次明确列出。不能据此断言七表都已经具有同样的审计列和默认规则。

| 表 | V1.1 已明确或建议的程度 | 待 C 确认 |
|---|---|---|
| `tournament` | 通用审计约定 / 主表建议，未在本表字段行重列 | 是否落实两列，以及 NULL、默认值、维护机制 |
| `stadium` | 同上 | 同上 |
| `wc_match` | 同上 | 同上 |
| `match_event` | 通用约定，未在本表简写中重列 | 是否包含两列，及具体属性 |
| `match_story` | 明确列出两列；类型为通用 DATETIME | NULL、默认值、创建 / 更新机制 |
| `match_tag` | 通用约定；只读字典可适当省略 | 是否省略，若保留则明确属性 |
| `match_story_tag` | 未逐表明确；关联表不能直接等同主表 | 是否采用通用审计约定；未经确认不添加 |

两个审计字段均不属于主键或外键。V1.1 没有指定 `DEFAULT CURRENT_TIMESTAMP`、`ON UPDATE CURRENT_TIMESTAMP` 或某一种 Yii Behavior，本文不作选择。

## 3. 关联关系与表归属

| 父表 / 所属成员 | 子表 / 所属成员 | 外键与方向 | 基数及可选性（基线语义） | 核对重点 |
|---|---|---|---|---|
| `tournament` / B | `wc_match` / B | `wc_match.tournament_id → tournament.id` | 一赛事对应多场比赛；每场必属一赛事 | 类型一致、真实 FK、编号按赛事唯一 |
| `stadium` / B | `wc_match` / B | `wc_match.stadium_id → stadium.id` | 一场馆对应多场比赛；每场必有场馆 | 禁止删除场馆时清空比赛历史 |
| `national_team` / C | `wc_match` / B | `home_team_id`、`away_team_id → national_team.id` | 每场有两个必填球队引用，位置不同 | 两队不同，且都属于该赛事 |
| `national_team` / C | `wc_match` / B | `winner_team_id → national_team.id` | 可空；非空时只能为该场双方之一 | 普通 FK 不能单独保证“是本场一方” |
| `national_team` / C | `tournament_team` / C | `tournament_team.team_id → national_team.id` | 球队通过参赛关系进入赛事 | 联合 PK、分组、最终成绩由 C 维护 |
| `tournament` / B | `tournament_team` / C | `tournament_team.tournament_id → tournament.id` | 一赛事有多条参赛关系 | 与 team_id 共同确认参赛资格；C 实现 FK |
| `wc_match` / B | `match_event` / B | `match_event.match_id → wc_match.id` | 一场比赛可有零到多条事件；每条事件必属一场 | 时间轴排序、比赛删除限制 |
| `national_team` / C | `match_event` / B | `match_event.team_id → national_team.id` | 事件可不绑定球队 | NULL 语义及事件球队范围待核对 |
| `wc_match` / B | `match_story` / B | `match_story.match_id → wc_match.id` | 一场比赛零或一篇故事；每篇故事恰属一场 | FK 非空 + match_id 唯一；不是每场都必须有故事 |
| `match_story` / B | `match_story_tag` / B | `story_id → match_story.id` | 一篇故事可有多条标签关联 | 故事删除时允许清理关联，不反向删除比赛 |
| `match_tag` / B | `match_story_tag` / B | `tag_id → match_tag.id` | 一个标签可被多篇故事使用 | 联合 PK 防止同一故事重复关联同一标签 |

`national_team` 与 `tournament` 的参赛关系经 C 的 `tournament_team` 表表达，不能拿球队名称、国家名或直接存在 national_team 记录来代替“已参加该届赛事”。B 不另建球队表或参赛关系副本。

## 4. 必须核对的业务约束

| 编号 | V1.1 已明确的业务规则 | 实现及确认边界 |
|---|---|---|
| B01 | `home_team_id != away_team_id` | Model / Service 校验；可落地的 CHECK 由 C 确认 |
| B02 | 双方球队必须属于比赛对应赛事 | 对照 C 的 tournament_team 校验；普通球队 FK 不足以证明资格，具体实现由 B/C 确认 |
| B03 | 同赛事比赛编号唯一，范围为 1—104 | 必须有 `UNIQUE(tournament_id, match_no)`；编号范围检查的落地由 C 确认 |
| B04 | `FINISHED` 必须有成对的 90 分钟含补时比分 | 列本身允许 NULL，但完赛时有条件必填；未完赛可空，不能用默认 0 伪造结果 |
| B05 | 120 分钟比分成对为空或成对有值，只在踢了加时时填写 | 保存累计至加时结束的总比分，每侧不得小于对应 90 分钟比分；不是加时新增进球 |
| B06 | 点球比分成对填写，仅用于点球决胜展示 | 不加入场上比分或球队总进球；非空时 winner_team_id 与点球较高方一致 |
| B07 | `winner_team_id` 只记录淘汰赛、季军赛、决赛的最终晋级 / 获胜方 | 小组赛不填；已结束的上述比赛必须有值，且是双方之一；不能直接用其统计场上胜场 |
| B08 | 最终场上比分优先 120 分钟，否则 90 分钟 | 统计、详情与旅程复用同一口径；点球获胜但场上打平仍计平局 |
| B09 | `SCHEDULED`、`POSTPONED`、`CANCELLED` 不得伪造赛果 | `IN_PROGRESS` 为人工状态，不代表实时比分服务；更细的状态转换规则文档未完整定义，见 Q05 |
| B10 | `match_story.match_id` 非空且唯一 | 对比赛是零或一篇故事，对故事是恰好一场比赛 |
| B11 | `match_story_tag` 使用 `(story_id, tag_id)` 联合主键 | 不增加单独自增 ID；FK、反向索引及删除行为见第 5 节 |
| B12 | minute 为基础分钟，added_minute 为补时；SHOOTOUT 时分钟可空 | `sort_order` 用于同一分钟内稳定排序；完整跨时段排序及并列处理未明确，见 Q07 |
| B13 | 比分统计只读取 wc_match，不以事件条数计算进球 | 精选事件可能不完整，不能用于补算全量比赛统计 |
| B14 | `is_published=0` 时不展示对应故事正文、时间轴与标签 | 仍显示比赛基础详情；发布前核验事实及素材使用许可 |

已明确的阶段代码：`GROUP / R32 / R16 / QF / SF / THIRD / FINAL`。只有 `GROUP` 阶段填写 A—L 分组。

已明确的状态代码：`SCHEDULED / IN_PROGRESS / FINISHED / POSTPONED / CANCELLED`，默认 `SCHEDULED`。

事件排序还需特别核对：不能只按 minute 数字或 minute + added_minute 推定完整时间线，因为不同半场和加时时段需要区分。V1.1 未指定最终排序表达式，本文不擅自选择按 sort_order 全局排序、时段优先或用 id 补序。

## 5. 主外键、索引和删除规则核对

### 5.1 基线明确要求与基线建议

| 表 | V1.1 已明确的键 / 索引要求 | V1.1 的建议或允许项 | 待 C 具体确认 |
|---|---|---|---|
| `tournament` | PK(id)、UQ(year) | 主表审计字段建议 | 实际键名、自增、被引用时的删除限制 |
| `stadium` | PK(id) | 主表审计字段建议 | name 未明确唯一；其他索引不自行新增 |
| `wc_match` | PK(id)、UQ(tournament_id,match_no)、INDEX(tournament_id,kickoff_utc)、INDEX(home_team_id)、INDEX(away_team_id)、INDEX(stage_code) | 尽可能增加业务 CHECK | stadium_id、winner_team_id 等 FK 配套索引；约束名和覆盖范围 |
| `match_event` | PK(id)、比赛 FK、可空球队 FK | 建议 INDEX(match_id,sort_order) | 建议索引是否采纳，team_id 配套索引及排序口径 |
| `match_story` | PK(id)、UQ(match_id)、比赛 FK | 无逐表额外索引建议 | 发布筛选等是否需要额外索引；不能写成已确定设计 |
| `match_tag` | PK(id)、UQ(code) | 只读字典可省审计字段 | name 不擅自唯一；标签被引用时的删除行为 |
| `match_story_tag` | 联合 PK(story_id,tag_id)、两个 FK | 关联清理允许 ON DELETE CASCADE | tag_id 方向查询和 FK 的配套索引，以及每个 FK 的删除规则 |
| `tournament_team`（C） | 联合 PK(tournament_id,team_id)、两个 FK | 本文件不扩展 C 的设计 | 确认用于查参赛资格、分组和最终成绩的正式结构 |

没有在 V1.1 中出现的额外索引只能作为待评估事项，不视为最终 DDL。联合主键中的列顺序按基线列示顺序核对；是否另有必要索引由 C 统一整理。

### 5.2 外键类型与逐关系删除规则

第 3.1 节规定重要关联使用真实 FK，历史关联采用 `RESTRICT` 或必要的受控软删除，禁止级联清空真实比赛历史；`match_story_tag` 允许级联清理。这是边界，不是每个 FK 已有确定的 ON DELETE / ON UPDATE 子句。

| 外键 | 类型核对（按基线） | 已明确的删除边界 | 待 C 确认 |
|---|---|---|---|
| wc_match.tournament_id → tournament.id | 两端 BIGINT UNSIGNED | 不得随赛事删除级联清空历史比赛 | 具体 ON DELETE、ON UPDATE、约束名 |
| wc_match.stadium_id → stadium.id | 同上 | 不得随场馆删除清空比赛历史 | 同上 |
| wc_match.home_team_id / away_team_id → national_team.id | 同上 | 不能破坏历史球队关联 | 两个 FK 分别定义；与 C 的球队停用策略对齐 |
| wc_match.winner_team_id → national_team.id | 同上，子列可空 | 不擅自选 SET NULL 或级联删除以丢失历史结论 | 具体规则；可空不等于已经确定 SET NULL |
| tournament_team.tournament_id → tournament.id（C 实现） | 同上 | 不得破坏正式参赛历史 | C 提供正式子句及配套索引 |
| tournament_team.team_id → national_team.id（C 实现） | 同上 | 同上 | 同上 |
| match_event.match_id → wc_match.id | 同上 | 采用历史关联保护边界，不自动决定事件级联删除 | 具体删除策略 |
| match_event.team_id → national_team.id | 同上，子列可空 | 不自动由可空推导 SET NULL | 具体删除策略与 NULL 语义 |
| match_story.match_id → wc_match.id | 同上，子列非空唯一 | 删除故事不得反向级联删除比赛 | 比赛被删除时故事如何处理，及实际 FK 子句 |
| match_story_tag.story_id → match_story.id | 同上，联合 PK 列 | 故事删除时允许清理对应标签关联 | 是否实际使用 ON DELETE CASCADE |
| match_story_tag.tag_id → match_tag.id | 同上，联合 PK 列 | 关联表允许清理，但标签删除规则未逐项冻结 | CASCADE 还是限制删除，由 B/C 明确 |

V1.1 没有指定统一的 ON UPDATE 行为，也没有为七张表逐一冻结软删除方式。本文不新增软删除字段、不默认所有 FK 都级联、不省略真实 FK。

## 6. B / C 接口依赖确认

### 6.1 C 向 B 提供

| 交付项 / 归属 | V1.1 已明确的内容 | B 的用途 | 尚需确认 |
|---|---|---|---|
| `models/NationalTeam.php` / C | 国家队 Model；字段包括 id、fifa_code、name_zh、name_en、flag_path、description、is_active | 比赛双方 / 获胜方关联、球队筛选与展示、事件球队关联 | 实际类和表映射、关联方法及交付版本；B 不代写第二套 Model |
| `models/TournamentTeam.php` / C | 联合 PK(tournament_id,team_id)，group_code、final_result_code | 验证本届参赛资格、读取分组和最终成绩 | 关联方法、正式约束和交付版本 |
| 球队与赛事参赛关系 / C | tournament_team 分别关联赛事与球队，2026 共 48 条参赛关系 | 不能仅凭 national_team 中存在一队就允许录入本届比赛 | 资格查询方式与导入时 ID 映射流程 |
| `services/TeamStatsService.php` / C | 以下三个实例方法及标准统计口径 | B 维护的 ApiController 调用球队统计，避免重复 SQL | 实现版本、异常方式、返回类型和缺失值与基线一致 |

TeamStatsService 的既定实例方法：

```php
getTeamStats(int $teamId): array
getCompareStats(int $leftId, int $rightId): array
getScoringRanking(): array
```

基线规定单队结果包含 `team_id`、`team_name`、`fifa_code`、`matches_played`、`wins`、`draws`、`losses`、`goals_for`、`goals_against`、`goals_per_match`、`goals_against_per_match`、`win_rate`、`penalty_wins`、`final_result_code`；比较结果包含 `left/right`。排名方法返回 48 队排序统计简表，但具体简表每项的完整键名尚需共同对齐。

与 B 直接相关的 C 表字段依赖如下，仅摘录基线，不代替 C 的完整数据字典：

| 表 / 字段 | V1.1 类型与约束 | 对 B 的意义 |
|---|---|---|
| national_team.id | BIGINT UNSIGNED，PK | 三个比赛球队 FK 及事件球队 FK 的目标 |
| national_team.fifa_code | CHAR(3)，NN UQ | 源 CSV/JSON 的球队映射键，不使用自编自增 ID |
| national_team.name_zh / name_en | VARCHAR(80) / VARCHAR(100)，NN | 展示球队名称 |
| national_team.flag_path | VARCHAR(255)，可空 | 展示用素材路径；缺失时的显示方式不在本文擅定 |
| national_team.is_active | TINYINT(1)，NN，默认 1 | 停用不应破坏历史比赛关联 |
| tournament_team.tournament_id / team_id | BIGINT UNSIGNED，联合 PK 且各自 FK | 核对该赛事参赛资格 |
| tournament_team.group_code | CHAR(1)，NN，A—L | 参赛分组 |
| tournament_team.final_result_code | VARCHAR(20)，可空 | 最终成绩类别，不是逐名次的官方排名 |

`final_result_code` 的基线建议值为 `GROUP/R32/R16/QF/FOURTH/THIRD/RUNNER_UP/CHAMPION`；不能把建议值当作已确认的数据库 ENUM。未知结论保持缺失，不伪造名次。两张 C 表其余字段默认值和实际 DDL 由 C 的字典解释。

### 6.2 B 向 C 提供

| 实例方法 / 归属 | 输入与已明确返回语义 | 使用方 / 入口 | 待确认的接口细节 |
|---|---|---|---|
| `MatchQueryService->getMatchList(array $filters = []): array` / B | 筛选键 stage、team_id、page，可选 page_size；默认每页 20；返回 items 和 pagination | C 可复用赛事查询；HTML 入口 wc-match/index | items 全部键名、pagination 内部键名、默认排序、page_size 上限及超页处理未完整规定，见 Q09 |
| `MatchQueryService->getMatchDetail(int $matchId): array` / B | 基础比赛信息，加可选故事、事件、标签 | C 的比赛链接，也供 A/D 调用；入口 wc-match/view | 完整嵌套键名、无故事结构、图片路径格式，见 Q09；不改变未发布内容的隐藏规则 |
| `JourneyService->getTeamJourney(int $teamId): array` / B | 本届全部比赛，按 kickoff_utc 再 match_no 排序 | C 的球队页面、B 的 journey/view | 基线样例字段与派生语义按下文核对；is_featured 等未完整定义处见 Q09 |
| `MatchQueryService->getStageSummary(): array` / B | 按阶段返回 stage_code、match_count、finished_match_count、total_goals | C 的 insight 页面；JSON 入口 api/insights?chart=stage-goals | 没有记录的阶段是否补行、行排序等未明确，见 Q10 |

以上只是契约确认，不表示服务已经实现。统一为实例方法，不私自改为静态调用，不新增第二套球队统计 SQL。

旅程单项的基线示例键为：`match_id`、`match_no`、`kickoff_utc`、`stage_code`、`opponent`（team_id、name_zh）、`is_home`、`goals_for`、`goals_against`、`penalties_for`、`penalties_against`、`advancement_result`、`is_featured`。比分按最终场上比分；未完赛时 goals_for/goals_against 为 NULL，点球独立表示。小组赛 advancement_result 为 NULL；淘汰赛可用 ADVANCED/ELIMINATED。示例时间为 UTC 的 ISO 8601 字符串。样例未完整规定未完赛、取消场次的晋级结果及季军赛/决赛的表达，不自行补齐。

阶段统计口径已经明确：

- `match_count` 不计 CANCELLED，包含 SCHEDULED、IN_PROGRESS、FINISHED、POSTPONED。
- `finished_match_count` 只计 FINISHED。
- `total_goals` 只汇总 FINISHED 的最终场上进球；每侧优先 120 分钟，否则 90 分钟；不计点球大战。
- 某阶段没有已结束比赛时 `total_goals=null`，不能用 0 冒充已核实总进球。

TeamStatsService 计算的场上 W/D/L 同样只读取 FINISHED 比赛；点球大战获胜而场上打平算平局，点球胜场独立计数。场次应等于 W+D+L；分母为 0 的场均 / 比率指标返回 NULL，不做除零。比分规则由 B/C 统一核对，但球队统计实现仍由 C 维护。

### 6.3 路由、JSON 和错误语义

| 入口 | 方法及参数 | 负责人 / 服务 |
|---|---|---|
| `wc-match/index` | GET；stage、team_id、page 可选 | B / getMatchList |
| `wc-match/view` | GET；id | B / getMatchDetail |
| `journey/view` | GET；team_id | B / getTeamJourney |
| `api/team-stats` | GET；id 为球队 ID | B 的 ApiController 调用 C 的 getTeamStats |
| `api/team-compare` | GET；left_id、right_id | B 的 ApiController 调用 C 的 getCompareStats |
| `api/insights` | GET；chart=stage-goals | B 的 ApiController 调用 B 的 getStageSummary，C 页面使用 |

采用 Basic 默认路由 `index.php?r=controller/action&...`。JSON 成功格式固定为 `success=true, data=业务结果, error=null`；失败为 `success=false, data=null, error={code,message}`，并设置相应 HTTP 状态码。键名 snake_case，文本 UTF-8。

参数格式错误返回 400，实体不存在返回 404，权限错误 403，内部错误 500；不返回 SQL、密码或内部路径。V1.1 允许不存在实体时抛 NotFoundHttpException 或返回明确查无结果，实际 Service 异常方式仍需 B/C 统一，防止调用方猜测。

基线建议错误码为 `INVALID_PARAMETER`、`TEAM_NOT_FOUND`、`MATCH_NOT_FOUND`、`INVALID_CHART`、`FORBIDDEN`；具体实现时统一冻结。API 只读，不能为了复用接口而加入写操作。后台写操作须 POST、CSRF 和 D 的正式管理员访问控制，模板演示账号不能作为交接完成的依据。

### 6.4 数据交接边界

B 整理比赛、场馆和精选内容源数据；C 统一维护导入程序、ID 映射及最终 install.sql。球队用 fifa_code、场馆用可核验官方名称作为源文件映射键，不在源 CSV 中自编数据库自增 ID。字段映射、来源和复核记录后续应进入项目数据来源文档。

验收范围为 2026 届 48 队 / 48 条参赛关系、104 场基础比赛、16 场馆，以及 5—8 场精选内容。示例或隔离测试记录不得当作真实赛果进入最终演示库。本文件没有提供或核实真实赛事数据。

## 7. 请 C 反馈的待确认事项

以下均尚未获得 C 的确认；填写“与 V1.1 一致”也应附上对应的正式字典或 DDL 位置，不能只勾选后继续各自维护不同结构。

| 编号 | 需确认事项 | 当前已有依据 | 期望 C / B-C 的反馈 | 状态 |
|---|---|---|---|---|
| Q01 | 字段默认值及缺失值 | 仅 status、sort_order、is_published 的默认值在 B 表中明确；自增不是默认值 | 对所有“未明确”字段明确最终策略，尤其 NULL 与空字符串、0 的区别；若新增约定需同步字典 | 待确认 |
| Q02 | 主外键实际类型 | 3.1 要求 BIGINT UNSIGNED，3.3 精选部分简写 BIGINT | 七表普通主键自增、所有 FK 与 C 的目标列类型完全一致；给出实际 DDL | 待确认 |
| Q03 | 审计字段逐表范围 | 通用建议与 story 明确列出并存 | 确认哪些表包含两列、NULL、默认值、维护机制；关联表不擅自加列 | 待确认 |
| Q04 | 唯一键、普通索引及 FK 配套索引 | 第 5.1 节区分明确要求和建议 | 提供实际索引清单；确认是否采纳事件建议索引、反向标签索引等，未规定的唯一性不默认为已定 | 待确认 |
| Q05 | 业务约束如何落地 | 第 4 节的明确规则 | 列出 C 实现的 FK/CHECK 与 B 的 Model/Service/导入校验分工；明确事件球队、状态转换等未完整规定项 | 待确认 |
| Q06 | 每条 FK 删除 / 更新规则 | 历史保护边界、关联清理允许级联 | 按第 5.2 节逐条确认，不把允许级联当成所有 FK 的默认选择；软删除需已有约定支撑 | 待确认 |
| Q07 | 事件枚举、分钟与排序 | 基线建议代码、minute/added_minute 含义、同分钟 sort_order | 冻结代码集合、非点球时的分钟必填条件、无补时时的值、跨时段顺序、排序优先级及并列规则 | 待确认 |
| Q08 | 精选发布、正文及素材 | 未发布隐藏内容，来源/封面字段可空 | 确认正文存储与输出方式、素材路径交接；明确旅程 is_featured 与发布状态的关系 | 待确认 |
| Q09 | 列表、详情及旅程完整返回结构 | 签名、部分结构及样例已给出 | 补齐尚未明确的嵌套键名、分页键、空结构、边界和异常；不增加未协商字段 | 待确认 |
| Q10 | 阶段统计缺失阶段与顺序 | 已明确四个字段及统计口径 | 确认无记录阶段是否返回、顺序与数据类型；不得改变 null / 0 语义 | 待确认 |
| Q11 | C 的交付版本和 ID / 时间映射 | NationalTeam、TournamentTeam、TeamStatsService 归 C，UTC 存储 | 给出实际模型/服务版本、关系方法、正式库名、字典/DDL 和数据映射方式 | 待确认 |

### C 可直接填写的反馈

| 确认项 | C 的反馈 |
|---|---|
| 核对人 / 日期 | 待填写 |
| 正式数据字典路径 / 版本 | 待填写 |
| DDL / install.sql 提交号 | 待填写 |
| NationalTeam / TournamentTeam / TeamStatsService 版本 | 待填写 |
| Q01—Q11 逐项结论或对应文档位置 | 待填写 |
| 与 V1.1 存在差异的条目及协调结果 | 待填写 |
| 可开始实现的表 / 接口及仍阻塞的部分 | 待填写 |

## 8. 确认后的执行边界

字段和关系确认后，B 再按顺序实现 Tournament、Stadium、WcMatch、查询服务与比赛列表/详情；后续实现旅程、精选内容、后台及阶段统计。只使用 C 统一提供的结构，不另建最终初始化脚本。

本文件的存在不代表已经签字冻结、建表、执行 SQL、实现 Model、完成服务或通过数据库测试。本次只生成本文档，不修改其他成员代码、不修改 data/install.sql、不提交或推送 GitHub。
