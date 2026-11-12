# Windows 本地一键部署与还原

日期为 2026-11-11，由 B 协助 C/D 实现。适用于 Windows 11、PowerShell 5.1 或更新版本、PHP 8.2.x、Composer 2、正在运行的 MySQL 8.0.16+ 的 8.0 版本。

## 启动

在项目根目录运行。

```powershell
.\scripts\start.ps1
```

默认从 8080 开始检查，遇到端口占用或 Windows 拒绝绑定时，在 8080—8100 内选择首个可用端口。以终端显示的地址为准，当前终端按 Ctrl+C 结束该本地服务。显式指定 `-Port` 时仅检查指定端口，失败会提示重新选择。可指定端口和工具路径。

```powershell
.\scripts\start.ps1 -Port 8082 `
  -Php 'E:\devtools\php-8.2\php.exe' `
  -Composer 'E:\devtools\composer\composer.bat'
```

只准备配置、依赖和数据库时使用 `-PrepareOnly`。脚本只调整当前进程的 PHP PATH，并在退出后恢复。启动进程采用 `YII_ENV=prod`，关闭调试与 Gii。

## 首次配置和执行步骤

首次运行若缺少数据库账号，脚本依次询问 MySQL 主机、端口、项目库名、用户名和密码。密码以隐藏输入读取，通过标准输入传给 PHP，仅保存于 Git 忽略的 `config/db.local.php`。已有完整本地配置直接复用。

脚本依次执行以下步骤。

1. 核对 PHP 为 8.2.x，检查启动端口。
2. 使用现有 composer.lock 执行 composer install 和 check-platform-reqs。
3. 运行现有 bootstrap，生成缺少的本机 Cookie 密钥。
4. 运行 `php yii deploy/prepare --interactive=0`，创建缺少的项目数据库并按 C 的 `data/install.sql` 建立 15 表。
5. 复用 MatchDataImportService 导入赛事、48 队及参赛关系、16 场馆、104 场比赛、8 篇已发布故事及事件标签。
   2026-11-12 起，随后增量检查参赛表的 profile_json 字段，并登记 48 队的本届简介、教练和三位代表球员。已有非空档案保留，字段及资料说明见 docs/team-profiles-2026.md；现有库也可单独运行 `php yii team-profile/prepare`，无需重建表。
6. 复用 A 的 project/init 建立团队与成员。按清单中的 A 成员身份匹配并登记六份课程资料。
7. 空管理员表时通过 D 的 admin/create 创建 `wcj_admin`，随机密码在当前终端显示一次。已有管理员继续使用原账号。
8. 以 web 为公开目录启动仅绑定 127.0.0.1 的 PHP 本地服务。

创建新库的 MySQL 账号需要 CREATE 权限。目标库已存在时直接连接该库。MySQL、PHP 和 Composer 属于本机运行前提，脚本会报告版本或连接错误。

首次安装 Composer 依赖需要网络。当前仓库的 Docker Compose 仍是本机保留的上游示例。本次交付采用原生 Windows 运行方式。

## 重复运行及失败恢复

完整的 15 表沿用已有结构。业务数据相同则跳过，已有故事的发布／撤回和封面继续保留。已核对最终成绩只补齐 NULL，同值跳过，非空冲突中止并回滚业务导入。

建表期间使用 MySQL 命名锁和 runtime 中的建表指纹检查点。中断后再次运行，可以按同一版本的 C 建表文件继续创建缺少的表。遇到陌生部分结构或项目之外的表时会报出具体原因，需核对目标数据库配置。

业务导入采用事务。数据库 DDL 由 MySQL 逐表提交，依赖检查点恢复。课程文件校验和登记复用 A 的命令，资料有缺失或 SHA-256 不匹配时报告错误。

团队或成员已被维护成多个候选时，脚本提示通过 `project/register-bundle <团队ID> <成员A的ID>` 指定归属，避免把个人资料登记给错误成员。

## 验收

进入首页、球队列表、球队 PK、数据图表、比赛列表、精选故事详情和作业下载。管理员从 `index.php?r=auth/login` 登录后访问后台。

```powershell
php tests/integration/deployment.php
php tests/integration/match-data-import.php
php tests/integration/b-module.php
```

这些脚本各自创建随机命名的临时测试库，结束后清理本次测试库。测试数据库账号需要相应建库和删库权限。

当前独立库还原回归通过 37 项检查，包含首次私有配置、15 表、完整赛事数据、48 队最终成绩及中文展示、六份资料、随机管理员密码、重复运行记录指纹、公开 HTTP 页面、后台访问控制和中断续建。Windows 自带 PowerShell 5.1 已实际运行一键启动，七个关键页面返回 200，调试工具关闭。测试用 8082 服务验证后已停止。

2026-11-11 补充端口回归。8080 拒绝绑定、8081 已占用时，不带参数启动自动选择 8082，图表页面 HTTP 200。显式指定不可用的 8080 时提示选择其他端口。
