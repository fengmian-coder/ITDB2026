# B 模块规范化 CSV 交付说明

整理日期：2026-11-05。分支：feature/b-match。

## 本地导入命令（2026-11-11）

根据 B 负责人授权，本分支新增 `commands/ImportController.php` 与 `services/MatchDataImportService.php`，统一读取本目录的八份业务 CSV。C 继续统筹表结构及后续导入程序维护。

先在空数据库执行 C 的 `data/install.sql`，配置本机 `config/db.local.php`，然后在仓库根目录运行。

```powershell
php yii import/world-cup --dryRun=1
php yii import/world-cup
```

`--dryRun=1` 会在事务中验证全部业务写入后回滚。正式导入按 FIFA 代码、场馆名称及赛事内比赛编号映射内部主键，时间转换为 UTC DATETIME，空比分保留 NULL。MySQL 自增计数可能因试导入增长，内部 ID 由数据库实际生成。

内容一致的记录跳过；已有内容冲突会报告文件/记录并整批回滚。重复导入保留后台发布状态、封面和已有最终成绩。事件按全部业务字段及重复数量比对，允许 sort_order 并列，已有事件内容不一致时整批回滚。全部故事按源文件保持草稿。sources.csv 继续作为来源审计资料保存在仓库。

独立数据库回归使用 `php tests/integration/match-data-import.php`，覆盖试导回滚、失败回滚、映射、记录数、重复导入和手工编辑保护。逐场外部事实及素材许可按本说明的复核事项执行。

## 来源与范围

唯一源文件：../2026世界杯_B模块真实赛事数据采集模板_8场精选加48队基础资料版_复核修正版.xlsx

源文件 SHA-256：733f8bb3d2c7511d833e17e38d0535bd34cd860cfadf154c798b9019e91004ea

本批次是采集表的格式转换与内部一致性检查结果，不代表全部赛果逐场外部核验或数据库联调通过。
原始格式整理仅转换采集表。2026-11-11 新增的导入命令、执行方式及数据库验证范围见上节。

## 文件与记录数

| 文件 | 数据行数 | 用途 |
|---|---:|---|
| [tournament.csv](tournament.csv) | 1 | 补充赛事信息；C 原清单未单列本文件，需在导入器接入后使用 |
| [teams.csv](teams.csv) | 48 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [stadiums.csv](stadiums.csv) | 16 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [matches.csv](matches.csv) | 104 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [stories.csv](stories.csv) | 8 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [events.csv](events.csv) | 104 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [tags.csv](tags.csv) | 16 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [story_tags.csv](story_tags.csv) | 27 | 字段顺序遵循 origin/C c112acf 的 docs/c-reply-to-b.md §5.5 |
| [sources.csv](sources.csv) | 57 | 来源审计资料，不是业务表导入文件 |

## 格式、空值与映射

- UTF-8，无 BOM；逗号分隔，首行为表头，CRLF 记录分隔。逗号、引号和多行正文使用标准 CSV 转义；必须用 CSV 解析器，不按物理行计数或简单 split。
- 空字段在可空目标列转 SQL NULL；真实 0 保留，不将缺失比分补成 0。日期为 YYYY-MM-DD，kickoff_utc 为 ISO 8601 UTC（YYYY-MM-DDTHH:MM:SSZ），C 转 DB DATETIME 时明确按 UTC 解析。
- 没有数据库内部 id/tournament_id/team_id/stadium_id/match_id/story_id/tag_id。match_no 是同届业务编号，不是 wc_match.id；collection_no 未作主键或比赛编号。
- team_code→fifa_code；球队中文/英文名→name_zh/name_en；home/away/winner_team_code→home/away/winner_fifa_code；events.team_code→team_fifa_code。
- stadium_key 经源表的 16 座场馆映射为唯一官方 stadium_name，不将 STAD-* 当内部 ID。
- teams.csv 合并球队基础信息和参赛分组；final_result_code 源表未提供，全部留空交 C 确认。不得从 group_position 或比分自行推断最终成绩；导入时空值不得覆盖已有可靠成绩。
- group_position 只是分组名单顺序；洲际足联、东道主、容量、地址等采集辅助字段不新增为数据库列。
- 31 场阶段映射：ROUND_OF_32→R32（16）、ROUND_OF_16→R16（8）、QUARTER_FINAL→QF（4）、SEMI_FINAL→SF（2）、THIRD_PLACE→THIRD（1）；GROUP/FINAL 保持不变。
- 主客位置、比分、winner、正文、事件分钟/补时、sort_order 和源记录顺序保留。120 分钟比分为累计值，不与 90 分钟相加；点球大战不计场上比分。
- 不补造点球大战每轮事件；加时阶段的 PENALTY_GOAL 是场上点球，不标为 SHOOTOUT。

## ET 事件转换记录

仅对源 period_code=ET 的 9 条记录，依据已提供的基础分钟：91—105→ET1、106—120→ET2。
本批没有缺失或边界不明的 ET 基础分钟。minute、added_minute、sort_order 均保留，120+5 仍为 minute=120、added_minute=5，属 ET2。
此转换不改原工作簿，不等于已修改 Model 的枚举验证或数据库 CHECK。

| 源工作表行 | match_no | sort_order | minute | added_minute | 转换 |
|---:|---:|---:|---:|---:|---|
| 50 | 75 | 13 | 110 | NULL | ET → ET2 |
| 51 | 75 | 14 | 113 | NULL | ET → ET2 |
| 66 | 82 | 15 | 93 | NULL | ET → ET1 |
| 67 | 82 | 16 | 94 | NULL | ET → ET1 |
| 68 | 82 | 17 | 96 | NULL | ET → ET1 |
| 69 | 82 | 18 | 109 | NULL | ET → ET2 |
| 70 | 82 | 19 | 120 | 5 | ET → ET2 |
| 106 | 104 | 6 | 106 | NULL | ET → ET2 |
| 107 | 104 | 7 | 111 | NULL | ET → ET2 |

## C 的导入接入要求

1. 先按 year=2026 定位或创建 tournament，不假定内部 ID 为 1。补充 tournament.csv 的接入方式由 C 确认。
2. teams.csv 为交 C 核对的源资料，不替代 C 对球队和参赛关系的维护；按 fifa_code 映射 national_team，再按本届赛事核对/生成 tournament_team。
3. 场馆导入后以官方名称解析 stadium_id；比赛按 tournament_id+match_no 定位，双方均需有该届参赛关系。
4. stories/events 通过本届 match_no 映射 match_id；story_tags 通过本届 match_no 定位故事、tag_code 定位标签。所有内部 ID 由 C 映射。
5. 审计时间不在本批手工填写，由正式确认的 DB 默认值或导入脚本维护，不向 NOT NULL 审计列显式写入空值。
6. B/C 尚未共同冻结的审计列、CHECK、删除规则等仍须单独确认；格式转换不表示批准全部 DDL 建议。
7. 使用上述统一导入命令，先在独立测试库验证，再导入开发库。当前命令使用已有 B Model 和数据库查询完成映射；C 的球队 Model 与统计服务接入继续单独联调。业务导入失败整批回滚并报告文件/记录。
8. sources.csv 仅作来源登记，不新建来源业务表；这些 CSV 不是已完成空库恢复的一键程序，不改写 install.sql。

## 发布、素材和复核事项

- 8 篇故事均保留 is_published=0；前台暂不显示故事、事件及标签。审核与素材许可核对完成后按后台流程发布，不在格式转换中自动改为 1。
- 源表的 48 个 flag_path、8 个 cover_path 为空；teams.csv 按 C 清单不含 flag_path，没有丢弃现有国旗路径，等待 A 提供素材。
- sources.csv 原样保留 URL、访问日期、核验备注、复核人和状态；31 条复核人缺失保持为空，不冒填。
- 来源登记不等于逐记录独立复核；需回溯事件分钟差异、行级 URL 或原始核验备注时，应使用上列唯一源工作簿，不混用其他旧版本。
- 本次未开展全量外部事实和版权审核，不将生成 CSV 视为全部审核通过。

## 本次验证范围

- 数量：赛事 1、球队与参赛关系 48/48、场馆 16、比赛 104、故事 8、事件 104、标签 16、故事标签关联 27。
- 业务键不重复；比赛编号 1—104 完整；12 组各 4 队、每组 6 场、每队 3 场小组赛。
- 主客不同，球队/场馆/故事/标签引用可解析；比分成对、累计比分、点球与 winner 内部规则通过核对。
- 阶段/状态/事件代码、字段长度和数值范围检查。104 场 UTC、ET、当地时间一致性已在前次只读核验中检查。
- 文件写入后以 UTF-8 和 csv.DictReader 回读所有字段，核对表头/记录数/正文换行/逗号引号/重音字符/0 与空值。
- 2026-11-11 已在 MySQL 8.0.19 的独立库执行导入回归，41 条断言覆盖 C 的 15 表恢复、外键映射、试导及失败回滚、重复导入、草稿发布状态和已有手工编辑保护。逐场外部事实、完整浏览器流程和最终部署恢复继续按验收清单核对。
