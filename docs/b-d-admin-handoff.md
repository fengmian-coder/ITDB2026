# B 给 D 的后台权限与路由交接文档

| 项目 | 内容 |
|---|---|
| 整理日期 | 2026-11-05 |
| 提供方 / 接收方 | B（赛事与比赛后端）/ D（管理员登录、后台入口与集成） |
| 当前分支 | feature/b-match |
| 已推送代码基线 | 21ce5f8：Journey 服务、Controller 和前台页面 |
| 本文交付范围 | 后台赛事管理的本地实现说明、授权接入建议、路由与联调清单 |
| 实际交付状态 | **本文先提交供 D 对齐接口；下述新增后台代码仍在 B 本地，尚未随本文提交或推送** |

依据：V1.1 第 1.2 节、第 5.1 节、第 6 节及安全要求。D 统一管理员身份、登录及后台入口，各业务负责人提供路由清单并维护自己的 Controller。本文不代表 D 已经确认某个授权方法，也不代表数据库或管理员联调已通过。

## 1. B 当前实现与交付边界

### 1.1 已在远端的 B 前台入口

| 路由 | 说明 | 权限 |
|---|---|---|
| wc-match/index | 比赛列表，stage/team_id/page/page_size 筛选 | 公开只读 |
| wc-match/view?id=... | 比赛详情及 Service 返回的已发布精选内容 | 公开只读 |
| journey/view?team_id=... | 球队赛程与晋级历程阶段实现 | 公开只读 |

上述页面允许 GET/HEAD，不应因接入后台授权而整体加上管理员限制。Journey 的真实球队资料、参赛资格与最终成绩仍待 C 对接，不能视为完整数据库联调通过。

### 1.2 B 本地已编写、待单独交付的后台赛事文件

| 文件 | 用途 |
|---|---|
| controllers/AdminTournamentController.php | 列表、详情、新增、修改、删除；权限、方法、异常及依赖处理 |
| models/TournamentSearch.php | 年份/名称筛选、排序、分页校验 |
| views/admin-tournament/index.php | 后台列表及搜索 |
| views/admin-tournament/view.php | 赛事详情与删除确认 |
| views/admin-tournament/create.php | 新增页 |
| views/admin-tournament/update.php | 修改页 |
| views/admin-tournament/_form.php | 共享表单 |

**D 仅拉取本文所在提交，还不能运行这些后台路由。** B 完成代码审查并单独提交推送后，会提供后台代码提交号，再进入实际接入。本文先用于明确实现边界，不要求 D 假定文件已存在或在自己的分支补写这些文件。

### 1.3 后续后台规划，尚未实现

| 规范中预定的 Controller / 路由 | 状态 |
|---|---|
| AdminStadiumController / admin-stadium/* | 待 B 开发 |
| AdminMatchController / admin-match/* | 待 B 开发，球队选择及资格依赖 C |
| AdminStoryController / admin-story/* | 待 B 开发，包含故事、事件及标签管理 |

不建立独立 backend 应用或 admin 模块，不修改 A 的公共布局/全局资源。后续路由逐项补充，未实现入口不应当作可用菜单提前发布。

## 2. 后台赛事路由与请求规则

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

## 3. 管理员授权接入建议（待 B/D 对齐）

### 3.1 本地 Controller 当前行为

AdminTournamentController 使用 Yii AccessControl 和 VerbFilter，CSRF 验证保持开启。

授权需要同时满足：

1. Yii user 组件判断为已登录。
2. Controller 的 adminAccessChecker 是服务器端配置的可调用对象。
3. 回调对当前 identity 的返回值严格等于布尔 true。

回调未配置、返回 false、返回数字 1 或其他非布尔 true 时均拒绝。未登录也不能仅靠回调通过。默认拒绝包括 Yii 模板的 admin/demo 账号，不按用户名、固定 ID、会话字符串或前端参数认定管理员。

当前未授权响应是 403，不自动跳转到示例 site/login。若 D 需要跳转真实管理员登录页，应先与 B 对齐登录 URL、返回地址及拒绝策略，不能仅关闭 AccessControl。

### 3.2 回调接口与配置位置

当前本地预留属性：

```php
public mixed $adminAccessChecker = null;
```

建议回调语义：

```text
输入：Yii user 组件提供的真实 IdentityInterface 身份对象
输出：bool；只有有效、启用且有赛事管理权限的管理员返回 true
未登录：由 AccessControl 先拒绝，不依赖此回调补救
配置位置：服务器端 Yii controllerMap 中的 admin-tournament Controller 配置
```

这里没有承诺或调用 D 尚未交付的 AdminIdentity、isAdmin()、某个 RBAC 权限名等类或方法。D 确定自己的实际实现后提供正式接入方式；若采用统一的其他权限方案，由 B 调整自己的业务 Controller，不要求 D 直接修改 B 文件。

此属性不从 GET/POST 加载。不得把回调配置为“所有已登录用户均 true”，也不得通过放行模板账号代替真实授权测试。

### 3.3 D 需要提供的信息

- 正式管理员 identityClass、文件路径、实现提交号；身份来自项目数据库，密码使用哈希。
- 登录/退出、后台首页的实际路由及登录成功后返回管理页的处理方式。V1.1 的 AuthController、AdminDashboardController 属于 D 的交付目标，不是当前已经存在的文件。
- 如何判断管理员有效、启用及具有后台访问权限；注销/停用后如何使访问失效。
- 是否接受本回调接入方式，以及实际配置代码的负责人。config/* 为共享文件，按 B/D 共同审核流程修改。
- 会话、自动登录和生产环境设置；不要在交接文档记录账号密码、cookie 密钥或访问令牌。

## 4. 状态码、表单与历史保护

| 场景 | 当前本地行为 |
|---|---|
| 未登录 / 授权失败 / 回调未配置 | 403，优先拒绝，业务查询不执行 |
| 已授权请求使用错误 HTTP 方法 | 405 |
| 已授权 POST 缺少或错误 CSRF token | 400，业务写入不执行 |
| 已授权请求的 id、筛选、分页、表单格式错误 | 400 |
| 赛事不存在 | 404 |
| Model 验证不通过 | 保留表单、显示字段错误，不重定向为成功 |
| 保存成功 | Flash 提示并 302 重定向详情 |
| 未勾选删除确认 | 400，不删除 |
| 已有 B 的比赛引用 | 不删除，Flash 提示并重定向详情 |
| 数据库 FK 拒绝删除或并发产生依赖 | 不删除，友好提示并重定向详情；原始异常仅进服务端日志 |
| 其他数据库异常 | 友好 500，并记录服务端日志 |

当前 B 会预查 WcMatch 的赛事引用；C 的参赛关系由正式外键保护，不在 B 中假造 C 的 Model。预检查不能替代真实 FK，也不能阻止并发新增引用，因此删除同时处理 IntegrityException。

权限/方法/CSRF 检查的顺序影响响应：未授权请求可先得到 403，而非 400/405。此行为不代表参数或写操作已经执行。

## 5. 后台入口与协作分工

| 工作 | 负责人 / 边界 |
|---|---|
| 正式管理员身份、登录/退出、后台首页与入口 | D |
| 赛事 Controller、SearchModel、业务 View 及权限过滤 | B |
| controllerMap 授权接入与 user 配置 | B/D 对齐并共同审核；本次未修改配置 |
| 正式 DDL、FK、数据导入 | C，依据已确认设计；不由 D 临时建另一套赛事表 |
| 公共布局、导航和全局资源 | A 审核；B 不直接修改公共布局 |
| 赛事管理菜单链接 | D 在后台代码正式交付、授权接入并验收后添加 admin-tournament/index |

## 6. 联调清单（待正式代码交付后执行）

| 编号 | 用例 | 预期 |
|---|---|---|
| D01 | 访客直接访问全部赛事后台 Action | 被拒绝，未读写业务数据 |
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

已完成的本地隔离测试为 73 个用例、236 条断言，包含实际 Yii AccessControl/VerbFilter/CSRF、搜索 SQL 编译及 View 渲染；写操作使用替代数据对象，未证明真实数据库保存、唯一约束、FK、D 的登录系统或浏览器完整流程已经通过。

请 D 的联调记录注明日期、代码提交号、数据库版本、测试身份类别、结果和证据位置；不要记录明文密码或会话令牌。

## 7. D 可填写的反馈

| 确认项 | D 的反馈 |
|---|---|
| 核对人 / 日期 | 待填写 |
| 正式身份类及认证代码提交号 | 待填写 |
| 登录、退出、后台首页路由 | 待填写 |
| 管理员有效性/停用判断方式 | 待填写 |
| 接受回调或提出替代接入方案 | 待确认 |
| 未登录使用 403 或跳转登录的约定 | 待确认 |
| 共享配置修改负责人及评审方式 | 待确认 |
| B 后台代码正式交付提交号 | 待 B 单独提交后补充 |
| 联调日期及遗留事项 | 待填写 |

本次只提交本文，不修改 D 的账号、登录代码或共享配置，不将未提交的后台文件冒充已远端交付，不执行 SQL 或启用默认管理员访问。
