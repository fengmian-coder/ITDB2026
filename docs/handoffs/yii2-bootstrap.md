# Yii2 Basic 骨架交接

负责人：B。日期：2026-11-03。项目负责人已确认将本次骨架提交并推送到 feature/b-match；PR 与合并另行安排。

## 分支与来源

- 仓库：`https://github.com/fengmian-coder/ITDB2026.git`。
- 按用户最新明确安排，在本地 `feature/b-match` 完成骨架；没有从远程 develop 派生。此为本次分支流程例外，原 V1.1 文档未改写，后续 PR 目标需组长确认。
- 本次为骨架首次提交，仅推送 feature/b-match，不直接操作 main 或 develop。提交记录以 git log 为准。
- 使用 Composer 在仓库外临时目录创建官方稳定版 `yiisoft/yii2-app-basic` 2.0.55，再逐项检查同名冲突后复制；未复制或覆盖 .git。
- 原规范文档和两份课程 PDF 的 SHA-256 与初始化前一致，本次不提交这些原始资料，由组长统一管理。未使用的 Docker/Vagrant 示例保留本地并忽略。
- `yiisoft/yii2` 锁定为 2.0.55；共 93 项稳定依赖，包含官方模板测试/开发工具；没有删除依赖、修改 vendor 或框架核心、绕过平台要求。

## 已完成

- Basic 标准目录、web 唯一公开入口、默认页面与原模板文件。
- PHP 8.2.x 和八项扩展约束、稳定版 Composer 依赖及锁文件。
- Web / Console 共用数据库配置，示例不含真实账号密码，utf8mb4、UTC。
- `php yii bootstrap --interactive=0` 生成缺失的 db.local.php 和随机 Cookie 密钥，不覆盖已有文件；Composer 安装后自动执行。
- Git 忽略本地配置、vendor、runtime、自动生成资源与认证文件；示例文件保留。
- data/install.sql 为注释占位，data/team、data/personal、data/import 目录保留。没有创建库、表、迁移或导入数据。
- README 包含 Windows 安装、启动、环境区分、配置、协作与当前限制。
- 保留官方演示 MVC，没有实现赛事业务、API 统计、后台或正式管理员认证。

## 实际验证结果

| 检查 | 结果 |
|---|---|
| PHP / Composer | 8.2.35 x64 NTS / 2.11.1 |
| Composer install | 成功；初次网络请求部分停滞，保留锁文件和缓存，降低当前进程并发后续装成功 |
| Composer validate --strict --no-check-publish | 通过 |
| Composer check-platform-reqs | 全部通过 |
| Composer install --dry-run --no-scripts | 锁文件与当前依赖一致，无需安装、升级或删除 |
| Composer audit --locked | 未发现安全漏洞公告 |
| Composer show yiisoft/yii2 | 2.0.55 |
| requirements.php | 0 错误，6 建议警告，见下文 |
| 本组新增/修改 PHP 文件语法 | 通过 |
| 数据库配置 | Web / Console 加载同一配置，Connection 实例创建成功，保持未连接状态 |
| 本地初始化重复运行 | 通过；本地配置与密钥文件哈希不变 |
| CSRF / Cookie | CSRF 开启，随机密钥仅保存在被忽略的本地参数文件 |
| prod 配置 | 不加载 debug/Gii，日志 traceLevel 为 0 |
| 本地 Web 服务 | `php yii serve 127.0.0.1 --port=8081` 正常启动，根目录为仓库的 web |
| HTTP 首页 | 200，包含官方模板的 Build with Yii Framework 标题 |
| 本地 CSS / JS / 图片 | 页面引用的 9 个资源均为 HTTP 200 |
| 私有路径 | /config/db.php、/data/install.sql、/composer.json 均为 HTTP 404 |
| 密钥泄露检查 | 随机本地 Cookie 密钥未出现在任何非忽略文件中 |
| 浏览器目视检查 | 未完成；浏览器自动化因无法可靠识别当前 URL 而停止，未继续操作浏览器 |

Yii 环境检查的 6 项建议警告为 pcntl、PDO SQLite、PDO PostgreSQL、Memcache、ImageMagick、expose_php。前三类替代/可选能力不用于当前 MySQL 骨架；已有 GD + FreeType 可承担验证码图像功能。Windows 无 pcntl 不影响本阶段。正式部署前建议在部署 PHP 配置关闭 expose_php，当前未修改全局 PHP 配置。

没有数据库账号和正式建库脚本，因此未验证数据库认证或实际 SQL。浏览器目视结果不得写为“已通过”；可手动打开上述地址复核。保留的官方演示账号/测试密码属于模板示例，不是项目的正式管理员账号，D 实现正式认证时应替换。

## 检查与后续

关键定制文件为 composer.json、.gitignore、config/db.php、config/db.example.php、config/params.php、config/params-local.example.php、config/web.php、config/console.php、commands/BootstrapController.php、web/index.php、yii、README.md 及本交接记录。

本地 db.local.php 的账号密码仍为空，需各成员自行填写；不要提交真实值。C 后续统一提供正式 install.sql；D 负责正式管理员身份与登录控制，A 负责布局。B 的赛事业务另行开发。

提交说明：

```text
chore(bootstrap): initialize Yii2 Basic application skeleton

- import the official stable Basic template and lock dependencies
- separate local database credentials and cookie validation keys
- document Windows setup and preserve course data conventions
```

## 本次新增文件清单

以下清单排除原有三个文档与所有被 Git 忽略的本地文件。因仓库尚无首次提交，Git 将这些文件全部显示为未跟踪，而不是已跟踪修改。

```text
.bowerrc
.editorconfig
.gitignore
LICENSE.md
README.md
assets/AppAsset.php
c3.php
codeception.yml
commands/BootstrapController.php
commands/HelloController.php
composer.json
composer.lock
config/__autocomplete.php
config/console.php
config/db.example.php
config/db.php
config/params-local.example.php
config/params.php
config/test.php
config/test_db.php
config/web.php
controllers/SiteController.php
data/import/.gitkeep
data/install.sql
data/personal/.gitkeep
data/team/.gitkeep
docs/handoffs/yii2-bootstrap.md
mail/layouts/html.php
mail/layouts/text.php
models/ContactForm.php
models/LoginForm.php
models/User.php
phpcs.xml.dist
phpstan.neon
requirements.php
runtime/.gitignore
services/.gitkeep
tests/Acceptance.suite.yml
tests/Acceptance/AboutCest.php
tests/Acceptance/ContactCest.php
tests/Acceptance/HomeCest.php
tests/Acceptance/LoginCest.php
tests/Acceptance/_bootstrap.php
tests/Functional.suite.yml
tests/Functional/ContactFormCest.php
tests/Functional/LoginFormCest.php
tests/Functional/_bootstrap.php
tests/Support/.gitignore
tests/Support/MailerBootstrap.php
tests/Support/bin/yii
tests/Support/bin/yii.bat
tests/Support/data/.gitkeep
tests/Support/envs/php-builtin.yml
tests/Support/output/.gitignore
tests/Unit.suite.yml
tests/Unit/LoginTest.php
tests/Unit/LogoutTest.php
tests/Unit/Models/ContactFormTest.php
tests/Unit/Models/LoginFormTest.php
tests/Unit/Models/UserTest.php
tests/Unit/_bootstrap.php
tests/Unit/widgets/AlertTest.php
tests/_bootstrap.php
views/layouts/_footer.php
views/layouts/_head.php
views/layouts/_header.php
views/layouts/main.php
views/site/about.php
views/site/contact.php
views/site/error.php
views/site/index.php
views/site/login.php
web/.htaccess
web/assets/.gitignore
web/css/site.css
web/favicon.ico
web/images/yii3_full_black_for_light.svg
web/images/yii3_full_for_dark.svg
web/images/yii3_full_for_light.svg
web/images/yii3_full_white_for_dark.svg
web/images/yii_logo_dark.svg
web/images/yii_logo_light.svg
web/index-test.php
web/index.php
web/js/color-mode.js
web/robots.txt
widgets/Alert.php
yii
yii.bat
```
