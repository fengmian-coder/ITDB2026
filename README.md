# WorldCup Journey

2026 世界杯数据探索与记忆平台。开发基线为小组的《WorldCup_Journey_项目开发协作规范与接口约定_V1.1.md》。原始规范与课程 PDF 本次不随骨架提交，由组长统一管理和分发。

## 当前范围与协作

main 已通过 PR #2 集成 A 前台、B 赛事与数据导入、C 的 15 表结构以及 D 的管理员与留言功能。PR #3 已在 99fe78d 合并 C 的 c45f159 球队 Model、球队列表／DNA 详情及统计服务，以及 B/C 联调代码。

按 2026-11-03 项目负责人的最新安排，本次骨架在 `feature/b-match` 开发并提交、推送至同名远程分支，这是对 V1.1 原定 `develop → chore/yii2-bootstrap` 流程的本次分支例外。后续 PR 目标由组长确认，不直接向 main/develop 推送。

B 负责骨架、赛事与旅程、统一 API 入口；C 负责数据字典、SQL、球队统计；A 负责布局和团队模块；D 负责管理员认证、留言与集成。其他成员克隆统一项目后只运行 `composer install`，不要各自创建 Yii2 项目。

## 环境与版本

- PHP 8.2.x，当前机器为 8.2.35 x64 NTS。
- Composer 2，当前机器为 2.11.1。
- MySQL 8.0.16 或更新的 8.0 版本，用于执行建表脚本中的 CHECK 约束。
- 官方 Basic 模板 2.0.55；当前锁定并验证的 Yii 框架为 2.0.55，以 composer.lock 为准。
- 必须启用 pdo_mysql、mbstring、openssl、intl、curl、zip、gd、fileinfo。
- composer.json 限定 PHP 8.2.x 和稳定依赖，不使用 `--ignore-platform-reqs`。

当前机器 PHP 位于 `E:\devtools\php-8.2\php.exe`，Composer 位于 `E:\devtools\composer\composer.bat`。保留原 XAMPP，运行本项目使用 PHP 8.2。

## Windows / PowerShell 启动

现有 PHP 8.2、Composer 2 和 MySQL 环境下，可在项目根目录运行一键配置、还原和启动。

```powershell
.\scripts\start.ps1
```

首次运行输入自己的 MySQL 账号。脚本会安装锁定依赖、创建缺少的项目库、还原 C 的 15 表、导入赛事数据、初始化团队及课程资料，并启动本地网页。空管理员表时生成 wcj_admin 随机密码并在终端显示一次。参数、重复运行和失败恢复见 [Windows 一键部署说明](docs/windows-deployment.md)。

在项目根目录执行：

```powershell
Set-Location 'E:\dasanshang\internet database development\ITDB2026'
php -v
composer --version
composer install
composer check-platform-reqs
composer show yiisoft/yii2
php yii serve --port=8080
```

打开 <http://localhost:8080/> 查看 WorldCup Journey 首页。关闭服务按 Ctrl+C，端口占用时改用 8081 等空闲端口。网站公开根目录为 web。

本机验证使用 <http://127.0.0.1:8081/>，命令为 `php yii serve 127.0.0.1 --port=8081`。

若 PATH 尚未生效，使用绝对路径：

```powershell
& 'E:\devtools\composer\composer.bat' install
& 'E:\devtools\php-8.2\php.exe' yii serve --port=8080
```

安装脚本会自动执行 `php yii bootstrap --interactive=0`，仅创建缺失的 config/db.local.php 和带随机 Cookie 密钥的 config/params-local.php；已有文件保持原样。可手动重复执行此命令。vendor、runtime、web/assets 应允许当前用户写入。

## 数据库与本地配置

config/db.php 读取无密码的 config/db.example.php，再应用 config/db.local.php。Web 与 Console 共用此入口。连接惰性建立，因此默认首页无需数据库已经建好。

只在 config/db.local.php 填写本机账号密码。默认库名为 worldcup_journey，字符集 utf8mb4，连接时区 UTC；最终库和结构以 C 的 SQL 为准，排序规则统一 utf8mb4_0900_ai_ci。本地配置和密钥已排除在 Git 之外。

先创建空库 worldcup_journey，按 data/install.sql 文件开头的说明执行建表脚本。脚本仅含表结构，赛事 CSV 及字段映射见 data/import/README.md。首次登录前运行 php yii admin/create <username> 生成本地管理员，再访问 index.php?r=auth/login。

数据库配置和管理员创建步骤见 docs/handoffs/d-auth-guestbook.md。不要提交本地密码。

## 目录约定

```text
assets/ commands/ config/ controllers/ models/ views/ widgets/ mail/
services/                     比赛、球队统计、历程、文件及纪念卡等业务服务
web/index.php                 Web 入口；web 为唯一公开根目录
runtime/ vendor/              本机生成，不提交
config/db.example.php         无密码数据库示例
config/db.local.php           本机账号，不提交
config/params-local.php       本机随机密钥，不提交
config/params-local.example.php 无密钥参数示例
data/install.sql              C 维护的 15 表建表草案
data/import/                  赛事源数据
data/team/                    团队作业
data/personal/                个人作业
docs/                         接口、交接和检查记录
tests/                        保留官方模板测试框架
```

路由保持 `index.php?r=controller/action`，无需修改 Apache；业务链接使用 Url::to()。CSRF 开启。服务端时间为 UTC，业务页面后续以 Asia/Shanghai 显示并标注北京时间。

首页由 A 的项目页面提供。球队入口 site/explore 转到 C 的 national-team/index，比赛入口为 wc-match/index，球队历程为 journey/view?team_id=内部球队ID。后台使用 admin_user 中的管理员账号登录，原 site/login 转到 auth/login。

当前已实现并验证 Windows 原生 PHP 8.2 的一键初始化及启动流程。本机保留的 Docker / Vagrant 模板尚未纳入项目部署方案。

## 部署与敏感文件

本地默认 dev。正式演示/部署必须给运行进程设置 YII_ENV=prod，入口将关闭 debug/Gii 和详细调试：

```powershell
$env:YII_ENV = 'prod'
php yii serve --port=8080
# 本机验证结束后恢复：Remove-Item Env:YII_ENV
```

PHP 内置服务只用于本地开发与验证。正式托管的 Web 根目录必须为 web；本次未修改 Apache。部署不发布 web/index-test.php、测试目录及默认演示登录功能。

每个环境单独生成 Cookie 密钥，不提交本地配置、auth.json、日志、缓存、vendor 或自动生成资源。composer.json 与 composer.lock 必须一起提交；其他成员用 composer install，不随意 update。

## 验证与交接

检查记录与文件清单见 docs/handoffs/yii2-bootstrap.md。项目负责人已确认将骨架提交并推送到 feature/b-match。提交说明：

```text
chore(bootstrap): initialize Yii2 Basic application skeleton
```

原项目规范与两份课程 PDF 保持原名原位置。官方模板版权见 LICENSE.md；参考 [Yii 2.0 官方安装文档](https://www.yiiframework.com/doc/guide/2.0/en/start-installation)。

## A 模块首版（feature/a）

A 已实现数据库驱动首页、公共导航与页脚、团队成员前后台、课程文件上传下载与浏览器纪念卡，已通过 PR #2 合入 main。初始化、资料包登记、路由、共享文件修复及验收记录见 [A 模块交接](docs/handoffs/a-team-site.md)。

建表并配置本地数据库后执行 `php yii project/init`。如需登记仓库中的六份课程资料，核对团队及 A 的成员 ID，再执行 `php yii project/register-bundle <团队ID> <成员A的ID>`。创建管理员仍使用 `php yii admin/create <username>`。登录后从后台首页进入“团队与成员”或“作业管理”。

本机开发库已导入 1 届赛事、48 队／参赛关系、16 场馆、104 场比赛及 8 篇已发布故事。其他机器按 data/import/README.md 执行 `php yii import/world-cup --dryRun=1` 试导，再执行 `php yii import/world-cup` 正式导入。新库按数据包还原故事发布状态，已有故事保留后台发布或撤回状态。球队 DNA、两个球队统计 API 和纪念卡统计复用 TeamStatsService。

## B/C 联调验证

本轮使用 PHP 8.2.35、Yii 2.0.55、MySQL 8.0.19。以下脚本分别创建独立临时测试库并在结束时清理，数据库账号需要创建和删除这些临时库的权限。

```powershell
php tests/integration/b-module.php
php tests/integration/match-data-import.php
```

2026-11-11 的 B/C 联调通过 158 项真实 HTTP／数据库检查，本轮数据包导入回归通过 84 条断言，包含八篇故事还原后的实际 HTTP 展示。比赛列表支持阶段、参赛球队和分页筛选，page_size 为 1—100。球队不存在或未参赛时返回 404。球队名称与 FIFA 代码已接入，flag_path 有合法素材时显示图片。

球队 PK 和三张动态图表已由 B 协助 C 实现。入口分别为 `index.php?r=national-team/compare` 和 `index.php?r=insight/index`，公共导航和球队 DNA 页已接入。图表使用本地 Apache ECharts 6.1.0，统计复用既有服务，提供完整数据表、球队切换和刷新。扩展后的 B/C 回归通过 193 项真实 HTTP／数据库检查，数据包导入回归通过 84 条断言。详情见 [C 球队 PK 与图表交接](docs/handoffs/c-team-insights.md)。

球队后台已补齐，管理员登录后从侧栏或工作台进入“球队管理”，路由为 `admin-team/index`。支持球队资料 CRUD、启停用、参赛分组及最终成绩维护，已有引用时阻止删除。最新 B/C 回归通过 220 项真实 HTTP／数据库检查，数据导入回归通过 92 条断言。操作和路由见上述 C 模块交接。

48 队最终成绩已按 FIFA 官方赛事总结补齐，来源和类别映射见 [成绩来源记录](docs/data-sources.md)。本轮导入回归通过 92 条断言，Windows 一键还原及重复运行通过 37 项隔离库检查。A 继续提供合法国旗和封面，D 完成独立数据复核及其他演示机器验收。八篇故事已按 B 负责人指令发布并同步到数据包。接口和测试范围见 docs/b-c-interface-handoff.md 与 docs/b-d-admin-handoff.md。
