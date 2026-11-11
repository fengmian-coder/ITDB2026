# WorldCup Journey

2026 世界杯数据探索与记忆平台。开发基线为小组的《WorldCup_Journey_项目开发协作规范与接口约定_V1.1.md》。原始规范与课程 PDF 本次不随骨架提交，由组长统一管理和分发。

## 当前范围与协作

main 已集成 B 的赛事与旅程、C 的 15 表建表草案以及 D 的管理员与留言功能。团队主页、数据导入和球队统计仍待补齐。

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

打开 <http://localhost:8080/>，应显示官方 Basic 默认首页。关闭服务按 Ctrl+C；端口占用时改用 8081 等空闲端口。只公开 web 目录，不把仓库根目录设为网站根目录。

本机本次验证使用 <http://127.0.0.1:8081/>（8080 已被其他程序占用），命令为 `php yii serve 127.0.0.1 --port=8081`。默认首页标题为 “Build with Yii Framework”。

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
services/                     后续共享服务
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

首页和联系页仍为模板内容。后台登录使用数据库管理员账号，原 site/login 会转到 auth/login。

本项目验证 Windows 原生 PHP 8.2，不需要 Docker / Vagrant；这些未使用的模板示例仅保留在初始化机器本地，不纳入 Git。

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

A 已实现数据库驱动首页、公共导航与页脚、团队成员前后台、课程文件上传下载与浏览器纪念卡。初始化、资料包登记、路由、共享文件修复、验收记录和待联调事项见 [A 模块交接](docs/handoffs/a-team-site.md)。本节对应 feature/a 本地改动，不表示已经合入 main。

建表并配置本地数据库后执行 `php yii project/init`。如需登记仓库中的六份课程资料，核对团队及 A 的成员 ID，再执行 `php yii project/register-bundle <团队ID> <成员A的ID>`。创建管理员仍使用 `php yii admin/create <username>`。登录后从后台首页进入“团队与成员”或“作业管理”。

正式赛事数据尚待 B/C 导入；没有数据时首页数量为零、球队入口和纪念卡制作页显示空状态，首页单独展示个人作业 2 的卡片示例。`site/explore` 是临时只读衔接入口，C 的正式球队控制器交付后自动转交；不会在 A 模块重复实现球队统计。
