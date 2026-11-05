# B 对 C 接口与数据库建议的回复

| 项目 | 内容 |
|---|---|
| 整理日期 | 2026-11-05 |
| 回复方 / 接收方 | B（赛事与比赛后端）/ C（球队、统计及正式数据库） |
| 对应 C 文档 | origin/C，提交 c112acf，docs/c-reply-to-b.md 第 4 节的 10 项事项 |
| B 数据交付 | feature/b-match，提交 c440dda，提交说明“上传整理的数据” |
| 当前状态 | **待 B 负责人审核，尚未确认或上传远端** |

本文逐项给出拟回复意见。文中的“拟同意”是审核草案，不表示 B 已批准 C 修改正式 DDL，也不表示代码或数据库已实现。B 审核后，应记录实际结论、日期和提交号，再由 C 核对落地；审核前继续以现有 V1.1 和已确认约定为准。

依据：[V1.1](../WorldCup_Journey_项目开发协作规范与接口约定_V1.1.md)、[B 字段确认单](b-field-confirmation.md)、[B/C 接口交接文档](b-c-interface-handoff.md)、C 在 origin/C 的回复及[本批数据说明](../data/import/README.md)。原确认单 Q01—Q11 不因这份草稿自动关闭。

## 1. 审计字段逐表方案（对应 C 第 1 项 / Q03）

**拟采用逐表方案，不统一替 A/C/D 决定所有 15 张表。** B 七表建议如下：

| B 表 | created_at / updated_at 建议 | 与当前代码的关系 |
|---|---|---|
| tournament | 增加两列，由数据库维护 | 当前 Model 未声明相关验证；正式确认后由 B 检查兼容性 |
| stadium | 增加两列，由数据库维护 | 同上 |
| wc_match | 增加两列，由数据库维护 | 同上 |
| match_event | 增加两列，由数据库维护 | 同上 |
| match_story | 保留两列，明确由数据库维护 | 当前已有字段验证，但未设置自动时间 Behavior |
| match_tag | 暂不增加 | 按只读标签字典处理；如后续确需追踪修改，另行协商 |
| match_story_tag | 暂不增加 | 保持 story_id、tag_id 两列及联合主键 |

对采用审计列的表，拟接受 C 提出的 DATETIME NOT NULL 策略：created_at 默认 CURRENT_TIMESTAMP；updated_at 默认 CURRENT_TIMESTAMP，并在更新时由数据库维护。连接、导入与写操作均使用 UTC；这些列不新增为本批 CSV 的必填人工采集项。

由 C 在正式数据字典和 DDL 中落实，B 在后续任务中核对 Model、保存行为及数据库默认值。不得向有默认值的非空审计列显式写入 NULL 来替代省略该列。其他成员负责的表由相应负责人确认。

## 2. B 七表外键删除与更新规则（对应 C 第 2 项 / Q06）

**拟同意历史引用使用 RESTRICT，故事标签关联按以下清单处理。** 不新增软删除列，不使用 SET NULL 静默丢失历史结论。

| 外键 | ON DELETE 建议 | ON UPDATE 建议 |
|---|---|---|
| wc_match.tournament_id → tournament.id | RESTRICT | RESTRICT |
| wc_match.home_team_id → national_team.id | RESTRICT | RESTRICT |
| wc_match.away_team_id → national_team.id | RESTRICT | RESTRICT |
| wc_match.stadium_id → stadium.id | RESTRICT | RESTRICT |
| wc_match.winner_team_id → national_team.id | RESTRICT | RESTRICT |
| match_event.match_id → wc_match.id | RESTRICT | RESTRICT |
| match_event.team_id → national_team.id | RESTRICT | RESTRICT |
| match_story.match_id → wc_match.id | RESTRICT | RESTRICT |
| match_story_tag.story_id → match_story.id | CASCADE | RESTRICT |
| match_story_tag.tag_id → match_tag.id | RESTRICT | RESTRICT |

删除故事时可清理其标签关联，不能反向删除比赛；删除标签前应先处理关联。比赛被故事或事件引用时应拒绝直接删除，由后台显示明确提示。C 的 tournament_team 两个外键请在正式字典中一并列明，并与历史保护策略对齐。

## 3. CHECK、唯一索引与应用层分工（对应 C 第 3 项 / Q04、Q05）

**拟同意对已明确且可以用本行字段表达的规则增加 CHECK，不扩大业务限制。** 请 C 提供具体约束表达式供 B 复核，并用真实数据库测试证明生效。

- 阶段、状态及字段数值范围遵循 V1.1；比分 90/120/点球分别成对，FINISHED 必填 90 分钟比分。
- 有 120 分钟比分时，必须已有 90 分钟比分，且各侧累计值不小于对应 90 分钟值；点球大战必须能分出胜负，不能加入场上比分。
- winner_team_id 非空必须属于本场双方；小组赛不填获胜方，已结束淘汰赛、季军赛和决赛必须填写，并与决胜比分一致。
- SCHEDULED、POSTPONED、CANCELLED 不填写赛果；IN_PROGRESS 的进一步状态流转限制不在本次猜测补齐。
- **保持当前 group_code 边界**：GROUP 时允许 NULL；非空须为 A—L；非 GROUP 必须为 NULL。不得因为本批数据已完整分组，就把字段改成无条件非空或新增未经确认的必填规则。
- 双方不同可同时由应用层及本行 CHECK 保护。球队存在性由 FK 保护；双方属于该赛事、事件球队属于本场双方等跨表规则，由导入程序及 B 后续 Model/Service 验证承担。
- CHECK 必须显式考虑 NULL，不能依赖比较结果为 UNKNOWN 来冒充完整验证；具体 SQL 由 C 整理，本文件不提供或执行 DDL。

拟接受 V1.1 明确的唯一键及索引：tournament.year、wc_match(tournament_id,match_no)、match_story.match_id、match_tag.code，match_story_tag(story_id,tag_id) 联合 PK；wc_match 的赛事/开球时间、主客球队、阶段索引按基线落实。

拟接受 match_event(match_id,sort_order)、match_story_tag(tag_id,story_id) 的建议索引，由 C 核对 FK 配套及是否重复。不给 stadium.name、match_tag.name 增加未约定的唯一约束；C 自有表索引由 C 整理。

## 4. 事件枚举、分钟与排序（对应 C 第 4 项 / BC03、Q07）

**拟同意应用层白名单加数据库 CHECK，使用 C 提出的集合：**

- period_code：H1、H2、ET1、ET2、SHOOTOUT。
- event_type：GOAL、OWN_GOAL、YELLOW、RED、PENALTY_GOAL、PENALTY_MISS、OTHER。
- 非 SHOOTOUT 的 minute 必填；无补时的 added_minute 使用 NULL；SHOOTOUT 分钟可空。现有 minute/added_minute 数值范围保持原字段类型约定，不另行推定新的分钟上限。
- 排序保持 sort_order ASC、id ASC，View 不按分钟重排；C 的统计不通过事件数计算进球。

本批 CSV 已将 9 条原 ET 事件按明确的基础分钟分为 ET1（3 条）/ET2（6 条），逐条记录见 data/import/README.md；120+5 的加时赛点球属于 ET2，不是 SHOOTOUT。本批没有新增点球大战每轮罚球事件。

这些是拟冻结的后续验证规则。当前 MatchEvent 尚未加入枚举白名单及条件必填验证，不能声称已实现；审核通过后由 B 单独修改并测试，C 负责 DDL 和导入校验。

## 5. page_size 上限（对应 C 第 5 项 / BC05）

**拟同意 page_size 范围为 1—100，缺省仍为 20，由 B 后续实施。** 超过上限返回 400，不静默截断；保留当前空结果、超页及其他筛选语义。C 页面可继续固定使用 20。

当前 MatchQueryService 还没有此业务上限，本回复草稿不改变现有接口。B 修改、测试并通知后，再更新共享契约及调用方验收。Journey 查询全部比赛不能依赖默认第一页，也不能假设单页能装下 104 场。

## 6. 事件球队归属（对应 C 第 6 项 / BC03）

**拟同意 match_event.team_id 可空，非空时必须为该场 home_team_id 或 away_team_id。** 空值表示该事件未关联具体球队，不用虚构球队 ID 补齐；若日后确需其他归属范围，应先说明场景并协商。

B 后续补 NationalTeam 存在性及事件归属验证；C 在源代码映射、导入及资格检查中同步校验。普通球队 FK 本身不能保证“属于本场双方”。

## 7. C 侧查询异常与统计空值（对应 C 第 7 项 / BC02、BC06）

**拟同意 C 的 400/404/500 约定：**非法 ID 返回 400；球队不存在或存在但未参加 2026 返回 404，消息明确；同队比较返回 400；数据库异常、赛事未初始化、FINISHED 缺失必需比分等返回友好 500 并写服务端日志，不返回一组伪造的成功统计。

有效本届球队没有完赛记录时，场次、胜平负、进失球及点球胜场返回已核实的 0；场均及胜率返回 NULL，最终成绩无可靠结论时 NULL。点球决胜前场上打平计 draws，点球胜场独立计 penalty_wins，W+D+L=matches_played。

此条针对 C 的球队与统计接口；**不自动改变 B 当前比赛列表**中“合法数字球队 ID 无匹配时返回空列表”的行为。当前 B 查询结构与异常边界继续按已交接契约执行，若要增加球队资格检查需单独评审。

停用球队保留按 ID 读取与历史关联，C 的公开列表默认筛选启用球队；不要通过全局默认查询过滤让历史比赛找不到球队。

## 8. getScoringRanking 返回与并列规则（对应 C 第 8 项 / BC06）

**拟同意每项返回完整 TeamStats，按 goals_for DESC、fifa_code ASC、team_id ASC 排序。** 排名集合为本届 48 支参赛球队，包含无完赛记录的球队，不能混入非本届或仅因停用而遗漏本届历史记录。

保持三个既定实例方法：getTeamStats(int $teamId): array、getCompareStats(int $leftId, int $rightId): array、getScoringRanking(): array。比较结果 left/right 与输入顺序一致，Service 只返回业务数组，不重复包 success/data/error；由 B 后续 ApiController 统一包装。

team_name 优先中文、为空时回退英文；简称使用 fifa_code，不新增 short_name、abbreviation 或 logo_url。实际 ID 保持 PHP 整数范围内的约定；不通过强转支持超大 ID。

## 9. 已交付数据与导入顺序（对应 C 第 9 项 / BC08）

**拟按 C 的 CSV 契约交付，数据文件已推送至 feature/b-match，提交 c440dda。** [data/import/README.md](../data/import/README.md) 为本批次交接入口。

| 文件 | 记录数 | 说明 |
|---|---:|---|
| teams.csv | 48 | 基础信息及 48 条参赛分组；C 维护两表并统一映射 |
| stadiums.csv | 16 | 官方场馆名称为比赛映射键 |
| matches.csv | 104 | 本届 match_no 为 1—104，不作为内部比赛 ID |
| stories.csv | 8 | 全部保留 is_published=0 |
| events.csv | 104 | 精选关键事件，不代表全赛事事件穷尽采集 |
| tags.csv | 16 | 统一标签代码 |
| story_tags.csv | 27 | 用本届 match_no 和 tag_code 映射 |
| tournament.csv | 1 | 补充赛事信息，需 C 确认导入接入方式 |
| sources.csv | 57 | 来源登记资料，不是新增业务表 |

7 份 C 约定业务文件的表头已对齐；后两份是补充交付，不擅自视为 C 导入器已有支持。请 C 明确补充赛事文件的接入方式，并保留来源登记资料。

交付边界：

- UTF-8 无 BOM，标准 CSV 引号转义；正文包含换行，不能按物理行拆分。空字段按可空列映射为 NULL，真实 0 保留。
- kickoff_utc 使用 ISO 8601 UTC，C 按 UTC 转为数据库 Y-m-d H:i:s；不做重复时区偏移。
- 通过 year=2026 定位赛事；球队以 fifa_code、场馆以官方名称、比赛以赛事+match_no、标签以 code 映射；所有内部 ID 由 C 生成或查询，不假定赛事 ID=1。
- final_result_code 未提供，留空交 C 核对补充，不从分组名单位置或不完整推断生成，也不得用空值覆盖已有可靠成绩。
- 故事未发布、国旗与封面素材未补齐；来源登记仍有 31 条复核人为空。发布、事实及素材许可审核仍需完成，不因 CSV 导出成功而视为全部审核通过。
- 本批没有上传原始 Excel。需要逐行核对原始来源备注或分钟差异时，由 B 提供 README 指定版本；不混用其他旧版工作簿。

拟按“C 完成正式结构与模型 → 核对本批源文件 → C 导入独立测试库及校验 → B 联调比赛查询 → D 独立复核与空库恢复”推进。不要求 D 在正式 SQL、导入文件和恢复步骤未齐全时提前签署通过。

## 10. 共享接口文档维护（对应 C 第 10 项）

**拟由 B 建立 docs/api-contract.md，C 补充并核对球队/统计条目，D 参与整体维护与交接检查。** 本次仅提出分工，不代 D 作出承诺；如组内已有统一位置，由 D 协调后使用同一份契约。

该文件当前尚未创建；不能把本回复当成共享接口文档已经完成。各负责人维护自己的实现，跨模块签名、返回字段、状态或统计口径变化先协商契约，再分别修改各自代码并测试，不直接改对方业务文件。

## 11. 请 C 后续反馈及实施边界

审核通过并交付 C 后，请 C 返回：

1. 正式 docs/data-dictionary.md、DDL / data/install.sql 的版本与提交号，包含逐表审计列、NULL/默认值、索引及逐 FK/CHECK 明细。
2. NationalTeam、TournamentTeam 的可用提交、命名空间、关系方法和联合主键实现。
3. TeamStatsService 的实现版本、返回字段/类型、异常语义及实际测试结果。
4. 数据导入程序、补充 tournament.csv 的接入方式、独立测试库准备结果及遗留问题清单；不在文档填写真实账号密码。
5. 与本回复不一致的项目逐项列出，不能以“默认同意”略过差异。

B 在正式依赖交付后，另开任务补球队关联、参赛资格及事件规则、分页上限，并进行语法、功能及代码审查。JourneyService、后台 CRUD、ApiController 和真实数据库联调尚未完成；Journey 时间格式、取消赛程展示、决赛/季军赛结果及返回边界另行明确，不由本次回复自动冻结。

## 12. 审核记录（B 检查后填写）

| 项目 | 结论 |
|---|---|
| B 负责人是否同意第 1—10 项建议 | 待审核 |
| 需要修改的条款及替代方案 | 待填写 |
| B 审核日期 | 待填写 |
| 确认稿提交号 / 远端交付状态 | 未提交、未推送 |
| C 核对结果及正式实施版本 | 待反馈 |

本次只保存此待审回复稿，不修改已有规范、Model、Service、Controller、View、CSV、数据库配置或 install.sql，不执行 SQL，不 Commit 或 Push。
