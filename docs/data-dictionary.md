# WorldCup Journey 数据字典（15 表冻结草案）

2026-11-12 增量：A 按用户批准的球队档案初版方案，为 `tournament_team` 增加可空 `profile_json JSON`，维护该届球队简介、教练、三位代表球员及来源快照，仍保留原联合主键和 15 张表。结构、字段校验、旧库升级命令及提交给 C 的复核说明见 `docs/team-profiles-2026.md`。该增量不修改原统计口径。

| 项目 | 内容 |
|---|---|
| 文档状态 | 冻结草案：B 七表按 B 拟回复（`docs/b-confirmation-to-c.md`，`e0d9e21`，待 B 负责人审核）；A/D 六表按 V1.1 基线，待各自确认；C 两表由 C 决定 |
| 负责人 | C |
| 依据 | V1.1 第 3 节；B 新交接文档；B 拟回复；C 回复；B 的 `data/import/README.md` 与 CSV |
| 对应 DDL | `data/install.sql`（结构脚本） |
| 说明 | 本文是字段与约束的单一说明；B 的拟回复未最终审核前，涉及审计/FK/CHECK 的条目均为“拟同意/待审核”，不能对外称为最终冻结 |

---

## 1. 全局约定

| 项目 | 约定 |
|---|---|
| 数据库 | `worldcup_journey` |
| 引擎 | InnoDB |
| 字符集/排序 | `utf8mb4` / `utf8mb4_0900_ai_ci` |
| 主键 | 单列 `BIGINT UNSIGNED AUTO_INCREMENT`；关联表使用联合主键 |
| 外键 | 同类型 `BIGINT UNSIGNED`；显式命名；历史引用 `RESTRICT`，仅 `match_story_tag.story_id` 使用 `CASCADE` |
| 时间 | `DATETIME`，按 UTC 存储；页面转换为 `Asia/Shanghai` 并标注北京时间 |
| 审计字段 | **逐表方案，不强制 15 表统一**；见 §2 |
| 空值 | 可空列用 `NULL` 表示缺失；不用空串冒充缺失；真实 `0` 保留 |
| 命名 | 表/字段小写下划线；类名 PascalCase；JSON 键 snake_case |
| 删除 | 禁止级联清空比赛历史；不使用 `SET NULL` 静默丢失历史结论；不新增软删除列 |

**状态标记：**

| 标记 | 含义 |
|---|---|
| 已定 | V1.1 或 B 已明确 |
| C 决定 | C 负责范围内可直接落地 |
| B 拟同意 | B 拟回复已提出，待 B 负责人审核 |
| 待 A/D 确认 | 表归属 A/D，C 按 V1.1 起草，未获确认 |
| 待联调 | 需真实数据库/数据验证 |

---

## 2. 审计字段逐表方案

| 表 | 归属 | created_at | updated_at | 依据/状态 |
|---|---|---|---|---|
| `tournament` | B | 有 | 有 | B 拟同意 |
| `stadium` | B | 有 | 有 | B 拟同意 |
| `wc_match` | B | 有 | 有 | B 拟同意 |
| `match_event` | B | 有 | 有 | B 拟同意 |
| `match_story` | B | 有 | 有 | V1.1 明确，B 保留 |
| `match_tag` | B | 无 | 无 | B 拟同意（只读字典） |
| `match_story_tag` | B | 无 | 无 | B 拟同意（仅联合主键两列） |
| `national_team` | C | 有 | 有 | C 决定 |
| `tournament_team` | C | 有 | 有 | C 决定 |
| `project_team` | A | 未列 | 有 | V1.1 仅列 `updated_at`；待 A 确认是否补 `created_at` |
| `project_member` | A | 未列 | 有 | 同上 |
| `project_document` | A | 未列 | 未列（有 `uploaded_at`） | 待 A 确认 |
| `admin_user` | D | 有 | 有 | V1.1 明确 |
| `guestbook_message` | D | 有 | 有 | V1.1 明确 |
| `guestbook_reply` | D | 有 | 有 | V1.1 明确 |

**有审计列时的默认值：**

```sql
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
```

导入/写操作不得向 `NOT NULL` 审计列显式写入 `NULL`；省略该列由数据库维护。

---

## 3. 表逐字段说明

### 3.1 `tournament`（B）

赛事届次。首期只有 2026 一条，但以 `year` 定位，不写死 ID=1。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 赛事内部 ID |
| `year` | SMALLINT UNSIGNED | 否 | 无 | UQ | 赛事年份，本期 2026 |
| `name` | VARCHAR(120) | 否 | 无 |  | 赛事名称 |
| `start_date` | DATE | 否 | 无 |  | 开始日期 |
| `end_date` | DATE | 否 | 无 |  | 结束日期 |
| `description` | TEXT | 是 | NULL |  | 简介 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`id`)、UQ(`year`)；建议 `CHECK (year > 0)`、`CHECK (start_date <= end_date)`（B 复核）。

---

### 3.2 `national_team`（C）

48 支参赛球队的基础信息。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 球队内部 ID |
| `fifa_code` | CHAR(3) | 否 | 无 | UQ | FIFA 三字母码，源数据映射键 |
| `name_zh` | VARCHAR(80) | 否 | 无 |  | 中文展示名 |
| `name_en` | VARCHAR(100) | 否 | 无 |  | 英文名 |
| `flag_path` | VARCHAR(255) | 是 | NULL |  | 本地素材相对路径（相对 `web/`） |
| `description` | TEXT | 是 | NULL |  | 简介 |
| `is_active` | TINYINT(1) | 否 | 1 |  | 1 启用，0 停用；停用不破坏历史关联 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`id`)、UQ(`fifa_code`)、INDEX(`name_zh`)；`CHECK (is_active IN (0,1))`。

**停用策略：** 公开列表/搜索默认 `is_active=1`；按 ID 读取与历史比赛关联仍可解析停用球队；后台可见全部。

---

### 3.3 `tournament_team`（C）

球队与赛事的参赛关系、分组、最终成绩。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `tournament_id` | BIGINT UNSIGNED | 否 | 无 | PK1，FK → `tournament.id` | 赛事 ID |
| `team_id` | BIGINT UNSIGNED | 否 | 无 | PK2，FK → `national_team.id` | 球队 ID |
| `group_code` | CHAR(1) | 否 | 无 |  | 分组 A—L |
| `final_result_code` | VARCHAR(20) | 是 | NULL |  | 最终成绩类别 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`tournament_id`,`team_id`)、INDEX(`team_id`,`tournament_id`)；两个 FK 均 `ON DELETE RESTRICT ON UPDATE RESTRICT`；`CHECK (group_code BETWEEN 'A' AND 'L')`；`CHECK (final_result_code IS NULL OR final_result_code IN ('GROUP','R32','R16','QF','FOURTH','THIRD','RUNNER_UP','CHAMPION'))`。

**`final_result_code` 语义：** 采用既有八种最终成绩类别。2026-11-11 已按 FIFA 官方赛事总结补齐 `teams.csv` 的 48 队值，来源及类别映射见 `docs/data-sources.md`。该列允许 NULL，导入时源空值保留已有成绩。

---

### 3.4 `stadium`（B）

16 座比赛场馆。`name` 未约定唯一，不用名称做 PK。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 场馆内部 ID |
| `name` | VARCHAR(120) | 否 | 无 |  | 官方场馆名；源数据映射键 |
| `city` | VARCHAR(100) | 否 | 无 |  | 城市 |
| `country_code` | CHAR(2) | 否 | 无 |  | CA / MX / US |
| `timezone_name` | VARCHAR(64) | 是 | NULL |  | IANA 时区名 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`id`)；`CHECK (country_code IN ('CA','MX','US'))`；**不添加** `UNIQUE(name)`。

---

### 3.5 `wc_match`（B）

104 场比赛的赛程与结果，跨模块最重要的表。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 比赛内部 ID |
| `tournament_id` | BIGINT UNSIGNED | 否 | 无 | FK → `tournament.id` | 所属赛事 |
| `match_no` | SMALLINT UNSIGNED | 否 | 无 | UQ(与赛事) | 本届比赛编号 1—104 |
| `home_team_id` | BIGINT UNSIGNED | 否 | 无 | FK → `national_team.id` | 主队展示位 |
| `away_team_id` | BIGINT UNSIGNED | 否 | 无 | FK → `national_team.id` | 客队展示位 |
| `stadium_id` | BIGINT UNSIGNED | 否 | 无 | FK → `stadium.id` | 场馆 |
| `kickoff_utc` | DATETIME | 否 | 无 |  | UTC 开赛时间 |
| `stage_code` | VARCHAR(10) | 否 | 无 |  | `GROUP/R32/R16/QF/SF/THIRD/FINAL` |
| `group_code` | CHAR(1) | 是 | NULL |  | 仅 GROUP 可填 A—L；GROUP 允许 NULL |
| `status` | VARCHAR(16) | 否 | `SCHEDULED` |  | `SCHEDULED/IN_PROGRESS/FINISHED/POSTPONED/CANCELLED` |
| `home_score_90` | TINYINT UNSIGNED | 是 | NULL |  | 90 分钟含补时比分 |
| `away_score_90` | TINYINT UNSIGNED | 是 | NULL |  | 90 分钟含补时比分 |
| `home_score_120` | TINYINT UNSIGNED | 是 | NULL |  | 含加时累计比分 |
| `away_score_120` | TINYINT UNSIGNED | 是 | NULL |  | 含加时累计比分 |
| `home_penalties` | TINYINT UNSIGNED | 是 | NULL |  | 点球大战命中数 |
| `away_penalties` | TINYINT UNSIGNED | 是 | NULL |  | 点球大战命中数 |
| `winner_team_id` | BIGINT UNSIGNED | 是 | NULL | FK → `national_team.id` | 淘汰赛/季军赛/决赛胜方；小组赛不填 |
| `summary` | VARCHAR(300) | 是 | NULL |  | 一句话摘要，不是故事正文 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引：** PK(`id`)；UQ(`tournament_id`,`match_no`)；INDEX(`tournament_id`,`kickoff_utc`)；INDEX(`home_team_id`)；INDEX(`away_team_id`)；INDEX(`stage_code`)；INDEX(`stadium_id`)；INDEX(`winner_team_id`)。

**外键：** `tournament_id`、`stadium_id`、`home_team_id`、`away_team_id`、`winner_team_id` 均 `ON DELETE RESTRICT ON UPDATE RESTRICT`。

**CHECK（C 拟稿，B 复核）：**

1. `match_no BETWEEN 1 AND 104`
2. `home_team_id <> away_team_id`
3. `stage_code IN ('GROUP','R32','R16','QF','SF','THIRD','FINAL')`
4. `status IN ('SCHEDULED','IN_PROGRESS','FINISHED','POSTPONED','CANCELLED')`
5. `group_code`：`(stage_code = 'GROUP' AND (group_code IS NULL OR group_code BETWEEN 'A' AND 'L')) OR (stage_code <> 'GROUP' AND group_code IS NULL)`
6. 90 分钟比分：`(home_score_90 IS NULL AND away_score_90 IS NULL) OR (home_score_90 IS NOT NULL AND away_score_90 IS NOT NULL)`
7. 120 分钟比分：`(home_score_120 IS NULL AND away_score_120 IS NULL) OR (home_score_120 IS NOT NULL AND away_score_120 IS NOT NULL)`；且 `home_score_120 IS NULL OR home_score_90 IS NOT NULL`；`away_score_120 IS NULL OR away_score_90 IS NOT NULL`
8. 120 ≥ 90：`home_score_120 IS NULL OR home_score_90 IS NULL OR home_score_120 >= home_score_90`；`away_score_120 IS NULL OR away_score_90 IS NULL OR away_score_120 >= away_score_90`
9. 点球成对：`(home_penalties IS NULL AND away_penalties IS NULL) OR (home_penalties IS NOT NULL AND away_penalties IS NOT NULL)`
10. 点球分胜负：`home_penalties IS NULL OR home_penalties <> away_penalties`
11. 点球胜方一致：`home_penalties IS NULL OR (home_penalties > away_penalties AND winner_team_id = home_team_id) OR (away_penalties > home_penalties AND winner_team_id = away_team_id)`
12. `winner_team_id IS NULL OR winner_team_id IN (home_team_id, away_team_id)`
13. `FINISHED` 必须有 90 分钟比分：`status <> 'FINISHED' OR (home_score_90 IS NOT NULL AND away_score_90 IS NOT NULL)`
14. 未开始/延期/取消不得有赛果：`status NOT IN ('SCHEDULED','POSTPONED','CANCELLED') OR (home_score_90 IS NULL AND away_score_90 IS NULL AND home_score_120 IS NULL AND away_score_120 IS NULL AND home_penalties IS NULL AND away_penalties IS NULL AND winner_team_id IS NULL)`
15. 已结束的淘汰赛/季军赛/决赛必须有 winner：`status <> 'FINISHED' OR stage_code = 'GROUP' OR winner_team_id IS NOT NULL`
16. 小组赛不得有 winner/点球：`stage_code <> 'GROUP' OR (winner_team_id IS NULL AND home_penalties IS NULL AND away_penalties IS NULL)`
17. 无点球时，FINISHED 非小组赛的 winner 与最终场上比分一致：
    `status <> 'FINISHED' OR stage_code = 'GROUP' OR home_penalties IS NOT NULL OR (COALESCE(home_score_120, home_score_90) > COALESCE(away_score_120, away_score_90) AND winner_team_id = home_team_id) OR (COALESCE(away_score_120, away_score_90) > COALESCE(home_score_120, home_score_90) AND winner_team_id = away_team_id)`

> 说明：CHECK 只覆盖单行可表达规则；双方属于该赛事、事件球队属于本场双方等跨表规则由导入程序/Model/Service 保证。CHECK 表达式必须显式处理 NULL，不能依赖 UNKNOWN 冒充通过。

---

### 3.6 `match_event`（B）

精选比赛事件时间轴；只用于展示，不作为球队进球统计来源。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 事件 ID |
| `match_id` | BIGINT UNSIGNED | 否 | 无 | FK → `wc_match.id` | 所属比赛 |
| `team_id` | BIGINT UNSIGNED | 是 | NULL | FK → `national_team.id` | 可选关联球队；非空须为本场双方之一 |
| `player_name` | VARCHAR(100) | 是 | NULL |  | 球员显示名 |
| `minute` | SMALLINT UNSIGNED | 是 | NULL |  | 基础分钟；非 SHOOTOUT 必填 |
| `added_minute` | SMALLINT UNSIGNED | 是 | NULL |  | 补时分钟；无补时为 NULL |
| `period_code` | VARCHAR(12) | 否 | 无 |  | `H1/H2/ET1/ET2/SHOOTOUT` |
| `event_type` | VARCHAR(20) | 否 | 无 |  | `GOAL/OWN_GOAL/YELLOW/RED/PENALTY_GOAL/PENALTY_MISS/OTHER` |
| `description` | VARCHAR(500) | 是 | NULL |  | 事件描述 |
| `sort_order` | INT | 否 | 0 |  | 同一分钟稳定排序；允许有符号整数 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引：** PK(`id`)；INDEX(`match_id`,`sort_order`)；INDEX(`team_id`)。
**外键：** `match_id`、`team_id` 均 `ON DELETE RESTRICT ON UPDATE RESTRICT`。
**CHECK：** `period_code IN (...)`；`event_type IN (...)`；`period_code = 'SHOOTOUT' OR minute IS NOT NULL`；`added_minute IS NULL OR minute IS NOT NULL`。

---

### 3.7 `match_story`（B）

一场比赛最多一篇精选故事；未发布内容不提供给前台。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 故事 ID |
| `match_id` | BIGINT UNSIGNED | 否 | 无 | UQ，FK → `wc_match.id` | 对应比赛 |
| `title` | VARCHAR(150) | 否 | 无 |  | 标题 |
| `summary` | VARCHAR(500) | 否 | 无 |  | 摘要 |
| `body` | TEXT | 否 | 无 |  | 正文；当前纯文本编码输出 |
| `cover_path` | VARCHAR(255) | 是 | NULL |  | 封面相对路径（相对 `web/`） |
| `source_url` | VARCHAR(600) | 是 | NULL |  | 来源 URL |
| `is_published` | TINYINT(1) | 否 | 0 |  | 发布开关 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`id`)、UQ(`match_id`)；FK `match_id` `ON DELETE RESTRICT ON UPDATE RESTRICT`；`CHECK (is_published IN (0,1))`。

---

### 3.8 `match_tag`（B）

标签字典；只读，不添加审计字段。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 标签 ID |
| `code` | VARCHAR(40) | 否 | 无 | UQ | 唯一英文标签代码 |
| `name` | VARCHAR(40) | 否 | 无 |  | 显示名；不设 UQ |

**索引/约束：** PK(`id`)、UQ(`code`)；不添加 `UNIQUE(name)`。

---

### 3.9 `match_story_tag`（B）

故事与标签多对多关联；不添加自增 ID、审计列或默认值。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `story_id` | BIGINT UNSIGNED | 否 | 无 | PK1，FK → `match_story.id` | 故事 ID |
| `tag_id` | BIGINT UNSIGNED | 否 | 无 | PK2，FK → `match_tag.id` | 标签 ID |

**索引/约束：** PK(`story_id`,`tag_id`)；INDEX(`tag_id`,`story_id`)；`story_id` FK `ON DELETE CASCADE ON UPDATE RESTRICT`；`tag_id` FK `ON DELETE RESTRICT ON UPDATE RESTRICT`。

---

### 3.10 `project_team`（A）

课程团队展示资料；当前只有一个团队，但仍用表维护。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 团队 ID |
| `name` | VARCHAR(100) | 否 | 无 |  | 团队名称 |
| `description` | TEXT | 是 | NULL |  | 团队简介 |
| `logo_path` | VARCHAR(255) | 是 | NULL |  | Logo 相对路径 |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**待 A 确认：** 是否补 `created_at`；`updated_at` 的默认/维护方式。

---

### 3.11 `project_member`（A）

四名组员资料与分工。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 成员 ID |
| `project_team_id` | BIGINT UNSIGNED | 否 | 无 | FK → `project_team.id` | 所属团队 |
| `display_name` | VARCHAR(80) | 否 | 无 |  | 显示名 |
| `role_title` | VARCHAR(100) | 否 | 无 |  | 角色/分工 |
| `bio` | TEXT | 是 | NULL |  | 简介 |
| `sort_order` | INT | 否 | 0 |  | 展示顺序 |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/外键：** INDEX(`project_team_id`)；FK `ON DELETE RESTRICT ON UPDATE RESTRICT`。
**待 A 确认：** 是否补 `created_at`；`sort_order` 是否限制非负。

---

### 3.12 `project_document`（A）

团队/个人作业下载信息；文件只能来自受控的 `data/team` 或 `data/personal`。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 文档 ID |
| `project_team_id` | BIGINT UNSIGNED | 否 | 无 | FK → `project_team.id` | 所属团队 |
| `member_id` | BIGINT UNSIGNED | 是 | NULL | FK → `project_member.id` | PERSONAL 必填；TEAM 为空 |
| `document_type` | VARCHAR(10) | 否 | 无 |  | `TEAM` / `PERSONAL` |
| `title` | VARCHAR(160) | 否 | 无 |  | 标题 |
| `relative_path` | VARCHAR(255) | 否 | 无 |  | 受控相对路径 |
| `original_name` | VARCHAR(255) | 否 | 无 |  | 原始文件名 |
| `file_size` | BIGINT UNSIGNED | 否 | 无 |  | 文件大小（字节） |
| `uploaded_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 上传时间（UTC） |

**索引/外键：** INDEX(`project_team_id`)、INDEX(`member_id`)；两个 FK `ON DELETE RESTRICT ON UPDATE RESTRICT`。
**CHECK：** `document_type IN ('TEAM','PERSONAL')`；`(document_type = 'PERSONAL' AND member_id IS NOT NULL) OR (document_type = 'TEAM' AND member_id IS NULL)`。
**待 A 确认：** `file_size` 是否允许为空；`uploaded_at` 的默认/维护方式。

---

### 3.13 `admin_user`（D）

管理员账号；不存明文密码。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 管理员 ID |
| `username` | VARCHAR(60) | 否 | 无 | UQ | 登录名 |
| `password_hash` | VARCHAR(255) | 否 | 无 |  | 哈希密码 |
| `auth_key` | VARCHAR(100) | 否 | 无 |  | 认证 key |
| `status` | TINYINT(1) | 否 | 1 |  | 1 启用，0 停用 |
| `last_login_at` | DATETIME | 是 | NULL |  | 最后登录（UTC） |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/约束：** PK(`id`)、UQ(`username`)；`CHECK (status IN (0,1))`。

---

### 3.14 `guestbook_message`（D）

游客留言与审核状态。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 留言 ID |
| `nickname` | VARCHAR(50) | 否 | 无 |  | 昵称 |
| `content` | TEXT | 否 | 无 |  | 内容；输出必须编码 |
| `related_match_id` | BIGINT UNSIGNED | 是 | NULL | FK → `wc_match.id` | 可选关联比赛 |
| `status` | VARCHAR(12) | 否 | `PENDING` |  | `PENDING/APPROVED/REJECTED` |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/外键：** INDEX(`related_match_id`)、INDEX(`status`,`created_at`)；FK `ON DELETE RESTRICT ON UPDATE RESTRICT`；`CHECK (status IN ('PENDING','APPROVED','REJECTED'))`。

---

### 3.15 `guestbook_reply`（D）

管理员对留言的回复；首版一条留言最多一条回复。

| 字段 | 类型 | NULL | 默认 | 键/约束 | 含义 |
|---|---|---|---|---|---|
| `id` | BIGINT UNSIGNED | 否 | 自增 | PK | 回复 ID |
| `message_id` | BIGINT UNSIGNED | 否 | 无 | UQ，FK → `guestbook_message.id` | 留言 ID |
| `admin_user_id` | BIGINT UNSIGNED | 否 | 无 | FK → `admin_user.id` | 回复管理员 |
| `content` | TEXT | 否 | 无 |  | 回复内容 |
| `created_at` | DATETIME | 否 | CURRENT_TIMESTAMP |  | 创建时间（UTC） |
| `updated_at` | DATETIME | 否 | CURRENT_TIMESTAMP ON UPDATE |  | 修改时间（UTC） |

**索引/外键：** PK(`id`)、UQ(`message_id`)、INDEX(`admin_user_id`)；两个 FK `ON DELETE RESTRICT ON UPDATE RESTRICT`。

---

## 4. 关系与基数

```text
tournament 1 ── N tournament_team N ── 1 national_team
      │                                         │
      └────── 1 ── N wc_match ─────────────────┤ (home_team_id / away_team_id / winner_team_id)
                         │
                         ├── N ── 1 stadium
                         ├── 1 ── N match_event
                         └── 1 ── 0..1 match_story ── N match_story_tag N ── 1 match_tag

project_team 1 ── N project_member
project_team 1 ── N project_document (PERSONAL 时可指向 project_member)
admin_user 1 ── N guestbook_reply N ── 1 guestbook_message
wc_match 1 ── 0..N guestbook_message（关联可选）
```

- `national_team` 与 `tournament` 的参赛关系必须经 `tournament_team`；不能仅凭 `national_team` 存在就认定参加本届。
- `wc_match` 的主队/客队/胜方是同一 `national_team.id` 体系的三个不同角色，不能混用。
- `match_story_tag` 删除故事时清理关联；删除标签前必须处理关联，不能反向删除比赛。
- 事件与故事都不能反向级联删除 `wc_match`。

---

## 5. 索引、外键与删除规则汇总

### 5.1 唯一键

| 表 | 唯一键 |
|---|---|
| `tournament` | `year` |
| `national_team` | `fifa_code` |
| `wc_match` | `(tournament_id, match_no)` |
| `match_story` | `match_id` |
| `match_tag` | `code` |
| `admin_user` | `username` |
| `guestbook_reply` | `message_id` |

**联合主键：** `tournament_team(tournament_id, team_id)`；`match_story_tag(story_id, tag_id)`。

### 5.2 外键删除/更新规则

| 外键 | ON DELETE | ON UPDATE |
|---|---|---|
| `tournament_team.tournament_id` → `tournament.id` | RESTRICT | RESTRICT |
| `tournament_team.team_id` → `national_team.id` | RESTRICT | RESTRICT |
| `wc_match.tournament_id` → `tournament.id` | RESTRICT | RESTRICT |
| `wc_match.stadium_id` → `stadium.id` | RESTRICT | RESTRICT |
| `wc_match.home_team_id` / `away_team_id` → `national_team.id` | RESTRICT | RESTRICT |
| `wc_match.winner_team_id` → `national_team.id` | RESTRICT | RESTRICT |
| `match_event.match_id` → `wc_match.id` | RESTRICT | RESTRICT |
| `match_event.team_id` → `national_team.id` | RESTRICT | RESTRICT |
| `match_story.match_id` → `wc_match.id` | RESTRICT | RESTRICT |
| `match_story_tag.story_id` → `match_story.id` | CASCADE | RESTRICT |
| `match_story_tag.tag_id` → `match_tag.id` | RESTRICT | RESTRICT |
| `project_member.project_team_id` → `project_team.id` | RESTRICT | RESTRICT |
| `project_document.project_team_id` → `project_team.id` | RESTRICT | RESTRICT |
| `project_document.member_id` → `project_member.id` | RESTRICT | RESTRICT |
| `guestbook_message.related_match_id` → `wc_match.id` | RESTRICT | RESTRICT |
| `guestbook_reply.message_id` → `guestbook_message.id` | RESTRICT | RESTRICT |
| `guestbook_reply.admin_user_id` → `admin_user.id` | RESTRICT | RESTRICT |

**原则：** 不因删除赛事/球队/场馆而级联清空比赛历史；可空外键不代表 `SET NULL`；不新增软删除列。

---

## 6. 枚举字典

| 对象 | 取值 | 说明 |
|---|---|---|
| `wc_match.stage_code` | `GROUP/R32/R16/QF/SF/THIRD/FINAL` | V1.1 已定 |
| `wc_match.status` | `SCHEDULED/IN_PROGRESS/FINISHED/POSTPONED/CANCELLED` | 默认 `SCHEDULED` |
| `wc_match.group_code` | `A—L` | 仅 GROUP；GROUP 允许 NULL |
| `match_event.period_code` | `H1/H2/ET1/ET2/SHOOTOUT` | B 拟同意 |
| `match_event.event_type` | `GOAL/OWN_GOAL/YELLOW/RED/PENALTY_GOAL/PENALTY_MISS/OTHER` | B 拟同意 |
| `tournament_team.final_result_code` | `GROUP/R32/R16/QF/FOURTH/THIRD/RUNNER_UP/CHAMPION` | 最终成绩类别，不是官方排名；可为 NULL |
| `project_document.document_type` | `TEAM/PERSONAL` | PERSONAL 必须绑定成员 |
| `guestbook_message.status` | `PENDING/APPROVED/REJECTED` | 默认 `PENDING` |
| `admin_user.status` | `0/1` | 默认 1 |

---

## 7. 待确认与差异清单

| 编号 | 事项 | 当前处理 | 责任/状态 |
|---|---|---|---|
| O01 | B 对 10 项拟回复的最终审核 | 字典标注“B 拟同意/待审核” | B 负责人 |
| O02 | A 三张表的审计字段、`created_at`、`file_size` 是否可空 | 按 V1.1 起草，标注待 A 确认 | A |
| O03 | D 三张表的字段/默认值 | 按 V1.1 起草，标注待 D 确认 | D |
| O04 | `final_result_code` 的可靠值 | 已按 FIFA 官方总结补齐 48 队类别，来源见 docs/data-sources.md | B 协助 C |
| O05 | 8 篇故事全部 `is_published=0` | C 导入保留 0，不擅自发布 | B/D |
| O06 | `flag_path`/`cover_path` 为空 | C 存 NULL，页面占位；A 提供素材 | A |
| O07 | `sources.csv` 31 条复核人为空 | 保留为空，不冒填 | B/D |
| O08 | `tournament.csv` 接入方式 | C 的导入器显式读取；B 确认 | C/B |
| O09 | `page_size` 1—100 | B 后续实施；C 页面固定 20 | B |
| O10 | 本机无 MySQL/PHP，DDL 未执行 | 静态审查；需可用 MySQL 做 T01 | C/D |
| O11 | `wc_match.group_code` 在 GROUP 时允许 NULL | 按 B 拟回复；不做无条件非空 | B 拟同意 |
| O12 | `match_tag`/`match_story_tag` 不加审计字段 | 按 B 拟回复；字典已逐表列出 | B 拟同意 |

---

## 8. DDL 与建表顺序

正式 DDL 见 `data/install.sql`。建表顺序：

```text
tournament
national_team
stadium
tournament_team
wc_match
match_story
match_tag
match_story_tag
match_event
project_team
project_member
project_document
admin_user
guestbook_message
guestbook_reply
```

`install.sql` 只用于全新空库；如需重建，按反向顺序删除，不能对已有真实数据直接执行破坏性操作。

---

**说明：** 本文是 C 的冻结草案；B 的拟回复未最终审核、A/D 未确认前，不能把本文称为四方已签字的最终字典。C 的 DDL 以本文和 B 的最终审核结论同步更新。
