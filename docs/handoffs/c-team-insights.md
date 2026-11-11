# C 模块球队 PK 与动态图表交接

所属模块为 C 球队与统计，B 按负责人指令协助实现。日期为 2026-11-11，开发分支为 `feature/b-match`，交付通过该分支到 `main` 的 Pull Request。

## 已实现功能

- 两队 PK，展示已完赛场次、胜平负、进失球、场均进失球、胜率、点球胜场和已有最终成绩。
- 三张动态图表，分别展示球队进球排名、两队胜平负、阶段有效场次／完赛场次／进球数。
- 排名前 10 队、前 20 队、全部球队切换，两队选择，刷新接口及错误提示。
- 数据表供无 JavaScript 环境和辅助阅读使用，支持桌面、手机及深浅色模式。
- 公共导航增加球队 PK 和数据图表入口，球队 DNA 详情增加预选本队的 PK 链接。

## 文件和依赖

| 文件 | 用途 |
| --- | --- |
| `controllers/NationalTeamController.php` | 新增 actionCompare，复用球队 ID 和参赛校验 |
| `controllers/InsightController.php` | 汇集排名和阶段统计，渲染图表页 |
| `controllers/ApiController.php` | insights 增加 scoring-ranking 选项，保留统一 JSON 和 HTTP 状态 |
| `views/national-team/compare.php` | GET 选择、两队比较及交换 |
| `views/national-team/view.php` | 增加 PK 入口 |
| `views/insight/index.php` | 三张图表、服务端数据表及安全 JSON 初值 |
| `assets/TeamStatsAsset.php` | 依赖 AppAsset，注册页面样式 |
| `assets/InsightAsset.php` | 在图表页统一加载本地 ECharts 和脚本 |
| `web/css/stats.css` | 页面内样式及响应式布局 |
| `web/js/insight-charts.js` | 绘制、刷新、主题适配和请求取消 |
| `web/vendor/echarts/` | 固定的 Apache ECharts 6.1.0、原始授权和下载来源 |
| `views/layouts/_header.php` | 增加入口，折叠菜单断点调整到 xxl，避免中等桌面宽度换行遮住内容 |
| `tests/integration/b-module.php` | 扩展隔离数据库与 HTTP 回归 |

现有 TeamStatsService、TeamDirectoryService、MatchQueryService 和 Model 保持现有实现。依赖 Yii 2.0.55、PHP 8.2、MySQL 8.0 及现有 C 建表文件。数据库表结构和导入文件沿用现有版本。

## 页面与参数

| 请求 | 参数和行为 |
| --- | --- |
| `GET national-team/compare` | 无参数显示选择表单 |
| `GET national-team/compare?left_id=ID` | 预选本届参赛球队，支持历史停用球队 |
| `GET national-team/compare?left_id=ID&right_id=ID` | 两支不同参赛球队的对比，可交换双方 |
| `GET insight/index` | 读取真实数据库生成初始图表和数据表 |

两页支持 HEAD。POST 返回 405。非法、零值、数组或溢出 ID 返回 400，同队比较返回 400，缺失球队和非本届参赛球队返回 404，数据库等服务器异常交由 Yii 统一处理。

页面使用 Yii Basic 的 `index.php?r=...` 路由。PK 表单通过 GET 生成路由参数，分享链接保留双方球队 ID。图表默认比较排名前两支真实球队，用户可切换。PK 完整页初次进入时由用户选择。

## 统计服务和 JSON

继续调用 C 的现有方法。

```php
TeamStatsService::getCompareStats(int $leftId, int $rightId): array
TeamStatsService::getScoringRanking(): array
MatchQueryService::getStageSummary(): array
```

以上表示实例方法的签名。页面及 Controller 无聚合 SQL，比分和胜平负计算集中在既有 Service。

| 接口 | data 内容 |
| --- | --- |
| `GET api/team-compare?left_id=ID&right_id=ID` | `{left: TeamStats, right: TeamStats}`，沿用现有接口 |
| `GET api/insights?chart=stage-goals` | StageSummary 数组，沿用现有接口 |
| `GET api/insights?chart=scoring-ranking` | 完整 TeamStats 排名数组，本次扩展 |

统一成功响应为 `{success: true, data: ..., error: null}`。失败保留真实 HTTP 状态及既有 `{success: false, data: null, error: {code, message}}` 格式。未知 chart 返回 400。前端刷新失败时保留上次成功图表并显示失败提示。

TeamStats 字段沿用 C 的返回值。

```text
team_id, team_name, fifa_code, matches_played, wins, draws, losses,
goals_for, goals_against, goals_per_match, goals_against_per_match,
win_rate, penalty_wins, final_result_code
```

StageSummary 字段沿用 B 的返回值。

```text
stage_code, match_count, finished_match_count, total_goals
```

仅统计 2026 届 FINISHED 比赛。进球优先采用 120 分钟累计比分，否则采用 90 分钟比分，点球比分单独处理。点球大战前场上战平计为平局，penalty_wins 单列。有效赛程排除 CANCELLED，阶段无完赛记录时 total_goals 为 null，有完赛的真实零进球为 0。缺少最终成绩的球队显示“暂无”。

## 安全和资源

- 模板动态文本经 Html 编码，初始 JSON 经 Json::htmlEncode 编码。
- JavaScript 更新表格使用 textContent，图表提示使用 richText，球队名称保持文本语义。
- 每类刷新独立使用 AbortController，快速切换时旧响应无法覆盖新选择。
- ECharts 由 InsightAsset 单次加载，版本和 SHA-256 记录在 vendor 资源的 README。AppAsset 保持现有内容。

## 已完成验证

环境为 PHP 8.2.35、Yii 2.0.55、MySQL 8.0.19。

- 新增和修改 PHP 文件语法检查，前端脚本 Node 语法检查。
- `php tests/integration/b-module.php` 通过 193 项真实 HTTP／数据库检查，涵盖 PK 参数、同队及非参赛校验、输出编码、统计接口、取消比赛、加时与点球、无完赛／无有效赛程以及数据库比分修改后的刷新接口。
- `php tests/integration/match-data-import.php` 通过 84 条真实数据库导入断言，包含八篇故事还原后的 HTTP 展示。
- 浏览器实际绘制三张 SVG 图表，验证排名范围、球队切换、同队提示、刷新、PK 交换和 GET 提交。
- 独立临时库中将测试比分从 1 比 0 改为 5 比 0，浏览器刷新后球队累计进球由 3 变为 7，小组赛总进球由 1 变为 5。临时库与预览进程在验证结束后清理。
- 桌面及 390 像素手机宽度检查，通过深浅色模式检查，浏览器控制台无新增错误。

## 后续工作

- C 补齐有可靠来源的 tournament_team.final_result_code，现有空值继续显示“暂无”。
- A 补充合法球队国旗和故事封面素材。
- D 完成独立数据复核，D/C 完成一键部署和新环境还原验收。

本次 PK 与图表的可用性依据当前数据库和数据包。真实赛事资料的独立复核继续按团队分工完成。
