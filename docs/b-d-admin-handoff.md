# B 给 D 的后台权限与路由交接文档

| 项目 | 内容 |
|---|---|
| 整理日期 | 2026-11-11 |
| 提供方 / 接收方 | B（赛事与比赛后端）/ D（管理员登录、后台入口与集成） |
| 当前分支 | feature/b-match |
| 已推送代码基线 | a287e22，包含 C 的 c45f159、B 的校验补齐和球队联调，分支 feature/b-match |
| 本文交付范围 | 赛事、场馆、比赛、故事/事件/标签后台，以及公开 API 的授权边界与联调清单 |
| 实际交付状态 | 后台已接 D 的 AdminUser::canManage，158 项真实 HTTP／数据库检查通过，PR #3 已合并到 main |

依据为 V1.1 第 1.2 节、第 5.1 节、第 6 节及安全要求。D 统一管理员身份、登录及后台入口，各业务负责人维护本模块 Controller。

2026-11-11 使用 PHP 8.2.35、Yii 2.0.55、MySQL 8.0.19，在唯一临时数据库运行 `php tests/integration/b-module.php`。158 项检查覆盖 D 的真实管理员登录／退出、四组 B 后台游客拒绝与管理员访问、CSRF、GET 删除拒绝、核心 CRUD、引用保护、发布控制及 C 统计 API。临时库与测试服务在结束后清理。CSV 导入另有 41 条真实数据库断言通过。

本轮浏览器操作验证了比赛筛选、比赛详情到 Journey 跳转和球队搜索。部署还原、完整故障注入与并发竞争继续按专项用例验收。

## 1. B 当前实现与交付边界

### 1.1 已在远端的 B 前台入口

| 路由 | 说明 | 权限 |
|---|---|---|
| wc-match/index | 比赛列表，stage/team_id/page/page_size 筛选 | 公开只读 |
| wc-match/view?id=... | 比赛详情及 Service 返回的已发布精选内容 | 公开只读 |
| journey/view?team_id=... | 球队赛程与晋级历程阶段实现 | 公开只读 |

上述页面允许 GET/HEAD。Journey 已读取 C 的球队资料、参赛资格和最终成绩字段，比赛及历程可展示真实球队名称。当前库 48 队最终成绩为空，页面显示“暂无记录”。

### 1.2 已推送的后台赛事文件（90e5d75）

| 文件 | 用途 |
|---|---|
| controllers/AdminTournamentController.php | 列表、详情、新增、修改、删除；权限、方法、异常及依赖处理 |
| models/TournamentSearch.php | 年份/名称筛选、排序、分页校验 |
| views/admin-tournament/index.php | 后台列表及搜索 |
| views/admin-tournament/view.php | 赛事详情与删除确认 |
| views/admin-tournament/create.php | 新增页 |
| views/admin-tournament/update.php | 修改页 |
| views/admin-tournament/_form.php | 共享表单 |

### 1.3 已推送的后台场馆文件（ab91be7）

| 文件 | 用途 |
|---|---|
| controllers/AdminStadiumController.php | 场馆 CRUD、权限、CSRF 与比赛引用保护 |
| models/StadiumSearch.php | 名称/城市/国家筛选、排序与分页 |
| views/admin-stadium/index.php | 列表和搜索 |
| views/admin-stadium/view.php | 详情和删除确认 |
| views/admin-stadium/create.php、update.php、_form.php | 新增、修改及共用表单 |

D 拉取当前分支后可检查两组路由。config/web.php 的 controllerMap 已对四组 B 后台配置 `[app\models\AdminUser::class, 'canManage']`，临时库已验证真实管理员访问与核心 CRUD。

### 1.4 已推送的故事、事件与标签后台（54bb116、13fe837、5ccaba4）

| 文件 | 用途 |
|---|---|
| controllers/AdminStoryController.php | 故事 CRUD、事件/标签管理、发布确认、权限与删除事务 |
| models/MatchStorySearch.php | 标题、比赛 ID、发布状态筛选及排序分页 |
| views/admin-story/index.php | 故事列表与搜索，后台可查看草稿 |
| views/admin-story/view.php | 故事详情及删除确认 |
| views/admin-story/create.php | 新增页 |
| views/admin-story/update.php | 修改页 |
| views/admin-story/_form.php | 共用表单及发布确认 |
| views/admin-story/events.php、event-form.php | 事件列表、增改删、排序与已发布内容修改确认 |
| views/admin-story/tags.php、tag-form.php | 共享标签字典、关联/解除、编辑与受保护删除 |

故事主体使用 index/view/create/update/delete，发布或撤回通过 POST create/update 的 MatchStory[is_published] 完成，不新增发布路由。保存为已发布时还要求 confirm_publish=1，确认事实与素材许可已核对；编辑已发布故事后再次保存同样需要确认。该勾选是人工审核确认，不代表系统已自动核实事实或版权。

删除故事在同一事务中清理标签关联及故事，保留比赛、事件与标签字典。新增事件/标签 Action 同样受该 Controller 的 AccessControl、VerbFilter 与 CSRF 保护，不能只保护故事五个基础 Action。

### 1.5 已推送的比赛后台与公开 API

| 提交 | 文件 | 内容 |
|---|---|---|
| 91f5350 | controllers/AdminMatchController.php、models/WcMatchSearch.php、views/admin-match/{index,view,create,update,_form}.php | 比赛 CRUD、筛选、比分表单、故事/事件引用保护 |
| 3842640 | controllers/ApiController.php | 三个 GET 只读 JSON 入口；不属于管理员菜单 |

四组后台代码均已完成本地验证；球队数据与参赛资格、正式数据库约束、审计时间策略、正式登录及授权仍待 C/D 联调。没有独立 backend 应用或 admin 模块，也未修改 A 的公共布局或全局资源。

## 2. 后台路由与请求规则

默认 URL 形式为 index.php?r=admin-tournament/action；链接通过 Yii Html/Url 构建。

| 路由 | 允许方法 | 参数 / 数据 | 行为 |
|---|---|---|---|
| admin-tournament/index | GET / HEAD | TournamentSearch[year]、TournamentSearch[name]、page、per-page、sort | 搜索及列表，默认每页 20 |
| admin-tournament/view | GET / HEAD | id | 赛事详情 |
| admin-tournament/create | GET / HEAD | 无 | 展示新增表单，不保存 |
| admin-tournament/create | POST | Tournament[...]、CSRF token | 验证并新增，成功重定向至详情 |
| admin-tournament/update | GET / HEAD | id | 展示编辑表单，不保存 |
| admin-tournament/update | POST | id、Tournament[...]、CSRF token | 验证并修改，成功重定向至详情 |
| admin-tournament/delete | POST | id、confirm_delete=1、CSRF token | 检查依赖后删除；不能通过 GET 删除 |

id 必须为 PHP 整数范围内的正整数。page 默认 1，per-page 默认 20、允许 1—100；空列表允许第一页，超页返回 400。sort 仅接受 year/-year/name/-name/id/-id。

表单业务字段仅为 year、name、start_date、end_date、description；不接受修改 id、审计列或其他模块字段。审计字段是否落库、由谁维护按 B/C 另行确认的字典执行。

### 场馆后台（ab91be7）

| 路由 | 允许方法 | 参数 / 数据 |
|---|---|---|
| admin-stadium/index | GET / HEAD | StadiumSearch[name]、StadiumSearch[city]、StadiumSearch[country_code]、page、per-page、sort |
| admin-stadium/view | GET / HEAD | id |
| admin-stadium/create | GET / HEAD 展示，POST 保存 | Stadium[...]、CSRF token |
| admin-stadium/update | GET / HEAD 展示，POST 保存 | id、Stadium[...]、CSRF token |
| admin-stadium/delete | POST | id、confirm_delete=1、CSRF token |

仅允许写 name、city、country_code、timezone_name；国家代码 CA/MX/US，空时区归一为 NULL，不设默认时区或名称唯一约束。sort 白名单为 name/city/country_code/id 及各自的降序形式；分页规则同赛事后台。

场馆删除先检查 wc_match.stadium_id 引用，再由正式 FK 保护并发情况；已有比赛引用时拒绝删除。

### 精选故事主体后台

| 路由 | 允许方法 | 参数 / 数据 |
|---|---|---|
| admin-story/index | GET / HEAD | MatchStorySearch[title]、MatchStorySearch[match_id]、MatchStorySearch[is_published]、page、per-page、sort |
| admin-story/view | GET / HEAD | id（故事 ID） |
| admin-story/create | GET / HEAD 展示，POST 保存 | MatchStory[...]、confirm_publish、CSRF token |
| admin-story/update | GET / HEAD 展示，POST 保存 | id、MatchStory[...]、confirm_publish、CSRF token |
| admin-story/delete | POST | id、confirm_delete=1、CSRF token |

可写字段仅为 match_id、title、summary、body、cover_path、source_url、is_published。match_id 是比赛主键，不是比赛编号；id、created_at、updated_at 不从请求加载。本次未增加时间自动维护机制，审计字段仍按正式字典联调。

is_published 只接受 0/1；新增默认草稿。保存为已发布但未提交 confirm_publish=1 时保留表单并提示错误；撤回为草稿不需要发布确认。空封面路径及来源链接归一为 NULL。列表发布状态筛选空值表示不限，0 表示草稿，1 表示已发布；分页规则同赛事后台，sort 白名单为 id/title/match_id/is_published 及各自的降序形式。

后台允许查看草稿；前台仍由 MatchQueryService 控制，仅返回已发布故事及其关联事件、标签。事件和标签已提供以下入口，不绕过 Service 的发布控制。

### 比赛后台

| 路由 | 允许方法 | 参数 / 数据 |
|---|---|---|
| admin-match/index | GET / HEAD | WcMatchSearch[tournament_id]、WcMatchSearch[team_id]、WcMatchSearch[stage_code]、WcMatchSearch[status]、page、per-page、sort |
| admin-match/view | GET / HEAD | id（比赛主键，不是 match_no） |
| admin-match/create | GET / HEAD 展示，POST 保存 | WcMatch[...]、CSRF token |
| admin-match/update | GET / HEAD 展示，POST 保存 | id、WcMatch[...]、CSRF token |
| admin-match/delete | POST | id、confirm_delete=1、CSRF token |

白名单为 tournament_id、match_no、home_team_id、away_team_id、stadium_id、kickoff_utc、stage_code、group_code、status、home_score_90、away_score_90、home_score_120、away_score_120、home_penalties、away_penalties、winner_team_id、summary。球队填写 national_team.id，Model 校验对应赛事的 tournament_team 参赛记录。开赛时间为 UTC，比分复用 WcMatch 规则。

默认按 kickoff_utc、match_no、id 升序；sort 白名单为 id/kickoff_utc/match_no/stage_code/status 及降序形式。分页默认 20、上限 100、超页 400。删除先检查 MatchStory/MatchEvent，存在引用则拒绝；其他引用及并发依赖由正式 FK 保护。

### 事件时间轴（均为 admin-story 子路由）

以下 id 一律为故事 ID；Controller 从故事确定 match_id，不接受表单改变事件比赛归属。

| 路由 | 允许方法 | 参数 / 数据 |
|---|---|---|
| admin-story/events | GET / HEAD | id、page、per-page；默认 20，上限 100，超页 400 |
| admin-story/event-create | GET / HEAD 展示，POST 保存 | id、MatchEvent[...]、CSRF token |
| admin-story/event-update | GET / HEAD 展示，POST 保存 | id、event_id、MatchEvent[...]、CSRF token |
| admin-story/event-delete | POST | id、event_id、confirm_delete=1、CSRF token |

事件可写字段仅 team_id、player_name、minute、added_minute、period_code、event_type、description、sort_order。更新/删除按 event_id 和本故事 match_id 同时查找，不属于该比赛返回 404。排序为 sort_order ASC、id ASC，不按分钟重新排序，排序值通过编辑表单修改。

已发布故事的事件保存或删除须提交 confirm_publish=1，保存缺确认则保留字段错误，删除缺确认则 400。period_code 允许 H1/H2/ET1/ET2/SHOOTOUT，event_type 允许 GOAL/OWN_GOAL/YELLOW/RED/PENALTY_GOAL/PENALTY_MISS/OTHER。非 SHOOTOUT 必填 minute，填写 added_minute 时也须有 minute，team_id 非空须为本场双方之一。这些规则已在 Model、表单说明及 HTTP 保存测试中实施。

### 共享标签字典与故事关联

以下 id 仍为故事 ID，tag_id 为标签字典主键。

| 路由 | 允许方法 | 参数 / 数据 |
|---|---|---|
| admin-story/tags | GET / HEAD | id、page；固定每页 20，按 code、id 升序，超页 400 |
| admin-story/tag-create | GET / HEAD 展示，POST 保存 | id、MatchTag[code]、MatchTag[name]、CSRF token |
| admin-story/tag-update | GET / HEAD 展示，POST 保存 | id、tag_id、MatchTag[code]、MatchTag[name]、confirm_shared=1、CSRF token |
| admin-story/tag-delete | POST | id、tag_id、confirm_delete=1、confirm_shared=1、CSRF token |
| admin-story/tag-add | POST | id、tag_id、CSRF token |
| admin-story/tag-remove | POST | id、tag_id、CSRF token |

标签 code/name 均最多 40 字符，code 唯一；新建字典项不自动关联故事。字典编辑影响所有关联故事，必须人工确认共享影响；删除被任一故事引用的字典项会被拒绝，不自动清理其他故事。

关联写入的 story_id/tag_id 由服务器根据路由生成，不从表单加载；重复添加按幂等处理，正式联合主键仍须验证并发保护。解除只删除当前故事与该标签的关联。已发布故事的关联/解除还必须提交 confirm_publish=1，否则 400。

### 公开统计 API（不加管理员过滤）

| 路由 | 方法 | 行为 |
|---|---|---|
| api/insights?chart=stage-goals | 仅 GET | MatchQueryService::getStageSummary() |
| api/team-stats?id=... | 仅 GET | 调用 C 的 getTeamStats(int $teamId): array |
| api/team-compare?left_id=...&right_id=... | 仅 GET | 调用 C 的 getCompareStats(int $leftId, int $rightId): array |

成功返回 {success:true,data:业务数组,error:null}；失败保持真实 HTTP 状态并返回 {success:false,data:null,error:{code,message}}。非法参数/同队对比 400，非法图表 400 INVALID_CHART，未知/非参赛球队 404 TEAM_NOT_FOUND，缺 C 依赖或数据库错误为友好 500 SERVER_ERROR，错误方法 405 METHOD_NOT_ALLOWED。HEAD 不放行；写请求可能先被 CSRF 检查以 400 拒绝。响应不暴露 SQL 或内部路径。

C 的 TeamStatsService 已接入，两个球队 API 已通过真实服务、HTTP 状态与返回结构检查。当前默认 DI 可创建该服务。

## 3. 管理员授权接入

### 3.1 已实现 Controller 当前行为

AdminTournamentController、AdminStadiumController、AdminMatchController 与 AdminStoryController 均使用 Yii AccessControl 和 VerbFilter，CSRF 验证保持开启。

授权需要同时满足：

1. Yii user 组件判断为已登录。
2. Controller 的 adminAccessChecker 是服务器端配置的可调用对象。
3. 回调对当前 identity 的返回值严格等于布尔 true。

回调未配置、返回 false、返回数字 1 或其他非布尔 true 时均拒绝。未登录也不能仅靠回调通过。默认拒绝包括 Yii 模板的 admin/demo 账号，不按用户名、固定 ID、会话字符串或前端参数认定管理员。

当前未授权响应是 403，不自动跳转到示例 site/login。若 D 需要跳转真实管理员登录页，应先与 B 对齐登录 URL、返回地址及拒绝策略，不能仅关闭 AccessControl。

### 3.2 回调接口与配置位置

四组后台 Controller 均预留属性：

```php
public mixed $adminAccessChecker = null;
```

建议回调语义：

```text
输入：Yii user 组件提供的真实 IdentityInterface 身份对象
输出：bool；只有有效、启用且有对应后台管理权限的管理员返回 true
未登录：由 AccessControl 先拒绝，不依赖此回调补救
配置位置：服务器端 Yii controllerMap 中分别配置 admin-tournament、admin-stadium、admin-match、admin-story
```

当前身份类为 `app\models\AdminUser`，配置回调为 `AdminUser::canManage`。后台通过 D 的 `AuthController` 登录，进入 `AdminDashboardController` 提供的管理入口。

此属性不从 GET/POST 加载。不得把回调配置为“所有已登录用户均 true”，也不得通过放行模板账号代替真实授权测试。

### 3.3 已交付接口及后续验证

- 正式身份类 app\\models\\AdminUser，文件 models/AdminUser.php，身份来自 admin_user，密码使用哈希。
- 登录为 auth/login，POST 退出为 auth/logout，后台首页为 admin-dashboard/index，对应 AuthController、AdminDashboardController。
- 如何判断管理员有效、启用及具有后台访问权限；注销/停用后如何使访问失效。
- 是否接受本回调接入方式，以及实际配置代码的负责人。config/* 为共享文件，按 B/D 共同审核流程修改。
- 会话、自动登录和生产环境设置；不要在交接文档记录账号密码、cookie 密钥或访问令牌。

## 4. 状态码、表单与历史保护

| 场景 | 后台当前行为 |
|---|---|
| 未登录 / 授权失败 / 回调未配置 | 403，优先拒绝，业务查询不执行 |
| 已授权请求使用错误 HTTP 方法 | 405 |
| 已授权 POST 缺少或错误 CSRF token | 400，业务写入不执行 |
| 已授权请求的 id、筛选、分页、表单格式错误 | 400 |
| 赛事、场馆、比赛、故事、事件或标签不存在 | 404 |
| Model 验证不通过 | 保留表单、显示字段错误，不重定向为成功 |
| 保存成功 | Flash 提示并 302；主体 CRUD 返回详情，事件/标签返回对应管理列表 |
| 未勾选删除确认 | 400，不删除 |
| 赛事或场馆已有 B 的比赛引用 | 不删除，Flash 提示并重定向详情 |
| 数据库 FK 拒绝删除或并发产生依赖 | 不删除，友好提示并重定向详情；原始异常仅进服务端日志 |
| 其他数据库异常 | 友好 500，并记录服务端日志 |

赛事管理预查 WcMatch.tournament_id，场馆管理预查 WcMatch.stadium_id；C 的赛事参赛关系由正式外键保护，球队读查询通过已交付 Model。预检查不能替代真实 FK，也不能阻止并发新增引用，因此两组删除操作均处理 IntegrityException。

故事删除不套用上述赛事/场馆引用拒删规则：先删除 MatchStoryTag 中对应关联，再删除 MatchStory，并在同一事务中提交；任一步失败均回滚。不会删除 WcMatch、MatchEvent 或 MatchTag。真实库的故事／关联删除及外键引用保护已覆盖，并发竞争专项继续验证。

权限/方法/CSRF 检查的顺序影响响应：未授权请求可先得到 403，而非 400/405。此行为不代表参数或写操作已经执行。

## 5. 后台入口与协作分工

| 工作 | 负责人 / 边界 |
|---|---|
| 正式管理员身份、登录/退出、后台首页与入口 | D |
| 赛事/场馆/比赛/故事（含事件/标签）Controller、SearchModel、业务 View 及权限过滤 | B |
| controllerMap 授权接入与 user 配置 | B/D 对齐并共同审核；本次未修改配置 |
| 正式 DDL、FK、数据导入 | C，依据已确认设计；不由 D 临时建另一套赛事表 |
| 公共布局、导航和全局资源 | A 审核；B 不直接修改公共布局 |
| 管理菜单链接 | D 收到对应代码并完成正式授权接入与验收后，添加 admin-tournament/index、admin-stadium/index、admin-match/index、admin-story/index |

## 6. 联调清单

本轮已执行的真实 HTTP／数据库检查见本文开头。以下全 Action 权限遍历、停用会话、并发竞争、故障注入与完整素材流程需继续专项验收。

| 编号 | 用例 | 预期 |
|---|---|---|
| D01 | 访客直接访问四组后台全部 Action（含事件和标签子路由） | 被拒绝，未读写业务数据 |
| D02 | 普通身份、模板账号、已停用管理员 | 不获得后台权限，历史与会话策略符合正式约定 |
| D03 | 管理员已登录但回调未配置/返回非 true | 403，默认拒绝有效 |
| D04 | 正式有效管理员配置完成 | 可访问允许的管理页，B/D 对齐实际登录和返回地址 |
| D05 | 登录失败、注销、会话失效后再次访问 | 不能继续执行后台操作 |
| D06 | GET 删除、PUT/PATCH 修改、POST 查询 | 已授权时 405，不能写入 |
| D07 | POST 缺失/伪造/数组形式 CSRF token | 400，数据库不变 |
| D08 | 非法/越界 id、查无 id、超页/非法排序 | 按 400/404 规则响应 |
| D09 | 正常新增、修改、验证失败和重复年份 | 正常保存或保留字段错误，不能伪报成功 |
| D10 | 尝试 POST id、created_at 或其他额外属性 | 字段白名单生效，不能越权赋值 |
| D11 | 有比赛或参赛关系引用、并发新增引用后删除 | 由预查及真实 FK 保护历史，不级联清空 |
| D12 | 名称/简介中的 HTML、脚本和引号 | 页面正确转义，不执行脚本 |
| D13 | 桌面和移动端后台菜单、返回及提示 | 链接正确、表单可用、提示清晰 |
| D14 | 场馆名称/城市/国家筛选、非法国家与空时区 | 筛选和字段规则生效；空时区为 NULL，不默认某一时区 |
| D15 | 删除已有比赛引用的场馆 | 拒绝删除；并发新增引用时由真实 FK 保护 |
| D16 | 新建草稿、发布、编辑已发布故事、撤回 | 草稿可在后台查看；每次保存为已发布均需 confirm_publish=1；撤回后前台不返回故事、事件及标签 |
| D17 | 同一比赛重复建故事、非法比赛 ID、伪造审计字段 | 正式唯一约束及外键生效，失败有提示；额外字段不能赋值 |
| D18 | 删除有标签关联的故事，并模拟中途失败 | 成功时仅清理故事及关联；失败时事务回滚；比赛、事件及标签字典保留 |
| D19 | 故事标题/正文中的脚本及危险路径、来源链接 | 文本正确转义；后台详情不将路径或链接直接渲染为可执行内容 |
| D20 | 跨比赛访问 event_id、编辑排序值、已发布事件修改 | 跨比赛 404；排序与前台一致；发布确认生效，不改比分 |
| D21 | 共享标签编辑/删除、重复关联、解除关联 | 共享确认、引用拒删与正式联合主键生效；不改其他故事关联 |
| D22 | 比赛阶段/球队组合筛选、比分成对、赛果及删除引用 | 复用 WcMatch 规则；正式资格、FK/唯一约束验证，失败不伪报成功 |
| D23 | 三个公开 API 的合法/非法输入、C 缺依赖与数据库故障 | JSON 结构及状态正确；不加管理员门槛，不以假数据掩盖缺依赖 |

此前赛事后台隔离测试为 73 个用例、236 条断言；场馆后台为 87 个用例、279 条断言。包含实际 Yii AccessControl/VerbFilter/CSRF、查询编译及 View 渲染；未证明真实数据库或 D 登录流程已通过。

最近一轮新增后台/API 的 19 个 PHP 文件语法通过。本地测试：故事/事件/标签 160 用例、462 断言；故事删除事务控制流 6/32；比赛后台 62/161；API 43/167，共 271 用例、822 断言。CRUD、事务、C 服务使用替代对象，未访问真实数据库；不代表 InnoDB 回滚、唯一约束、外键、真实登录或浏览器完整流程已通过。测试数字是阶段运行记录，不承诺仓库包含可独立运行的完整自动化测试套件。

请 D 的联调记录注明日期、代码提交号、数据库版本、测试身份类别、结果和证据位置；不要记录明文密码或会话令牌。

## 7. D 可填写的反馈

| 确认项 | D 的反馈 |
|---|---|
| 核对人 / 日期 | 待填写 |
| 正式身份类 | app\\models\\AdminUser，已在当前集成分支交付 |
| 登录、退出、后台首页路由 | auth/login、auth/logout、admin-dashboard/index |
| 管理员有效性/停用判断方式 | 待填写 |
| 回调接入 | 四组 B 后台配置 AdminUser::canManage |
| 未登录访问 B 后台 | 403，临时库 HTTP 验证通过 |
| 共享配置修改负责人及评审方式 | 待确认 |
| B 后台代码交付提交号 | 最新 a287e22，历史交付为赛事 90e5d75、场馆 ab91be7、故事 54bb116、事件 13fe837、标签 5ccaba4、比赛 91f5350；API 3842640，均已推送 feature/b-match |
| 联调日期及遗留事项 | 待填写 |

请 D 基于 a287e22 检查当前集成版本，并继续完成 V1.1 第 3.5 节要求的独立数据复核及部署还原。B 四组后台、C 统计服务和 D 管理员授权的本机联调已执行，现有路由与状态码见本文。

## 8. 当前需要 D 继续处理的工作

- 独立抽样核对赛事时间、比分和点球结果，记录复核人及结论。V1.1 第 3.5 节要求“D执行一次独立复核”。
- 完成 [八篇精选故事文案](b-story-publication-draft.md)、104 条事件及素材使用许可的独立数据复核。八篇故事已按 B 负责人要求发布，数据包已同步正文及发布状态，后续由 B 根据复核反馈维护内容。
- 与 C 完成一键部署、空库还原及最终演示环境验证。安装、数据导入和管理员创建命令以当前 README 为准。
- 继续验证停用账号、会话失效、登录限流及部署环境设置，保留对应运行证据。

账号密码、会话令牌和本地 Cookie 密钥仅存各自本机配置。本文及 Git 提交均不记录这些值。
