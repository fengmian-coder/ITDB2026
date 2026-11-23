# WorldCup Journey

**2026 世界杯数据探索与记忆平台**

WorldCup Journey 是互联网数据库开发课程的团队项目。用户可以从一支球队出发，查询赛程、回看晋级路线和比赛关键时刻，再将观赛感想制作成纪念卡或发布到球迷留言区。管理员可以在后台维护赛事、球队和课程资料，并审核球迷留言。

项目数据覆盖 **48 支球队、16 座场馆、104 场比赛**，并收录 **8 场精选比赛故事**。赛事数据及核对依据见 [数据来源](docs/data-sources.md) 和 [CSV 导入说明](data/import/README.md)。

## 功能概览

| 功能 | 可以做什么 |
| --- | --- |
| 球队与晋级历程 | 查阅球队档案、代表球员和本届表现，比较两队的胜平负与进失球，沿比赛时间线和场馆地图查看球队历程 |
| 赛程与精选比赛 | 按阶段、参赛球队及精选状态筛选比赛，查看场馆、比分和赛果。精选比赛提供故事和事件时间轴，进球记录完整时可回看比分走势 |
| 数据总览 | 通过晋级流向、进球排名、攻防坐标和比赛日历探索赛事，点选球队或日期进入对应内容 |
| 纪念卡与球迷留言 | 选择球队和比赛，在浏览器内生成并导出 PNG 纪念卡。球迷留言可关联比赛，审核通过后公开 |
| 关于我们与作业下载 | 查看四位成员的分工，在「团队作业」区获取团队成果，点开成员卡片下载该成员的个人作业 |
| 管理后台 | 管理赛事、场馆、比赛、球队、故事和课程资料，审核留言并发布管理员回复 |

## 快速启动

正式交付采用 **Windows 本地一键启动**。首次启动需要联网安装 Composer 依赖，并连接已运行的 MySQL 服务。

macOS 用户可按课程部署文档从终端安装依赖、配置 MySQL、执行数据还原并启动网站。

| 环境 | 要求 |
| --- | --- |
| Windows | Windows 11，PowerShell 5.1 或更新版本 |
| PHP | 8.2.x，启用 `pdo_mysql`、`mbstring`、`openssl`、`intl`、`curl`、`zip`、`gd`、`fileinfo` |
| Composer | 2.x，应用依赖版本以 `composer.lock` 为准 |
| MySQL | 8.0.16 或更高的 8.0 版本。账号需具备对项目库的建表和读写权限，若需创建项目库，还需 `CREATE` 权限 |

### 1. 获取代码并启动

在 PowerShell 中运行以下命令。若已下载仓库，从项目根目录直接运行第三行即可。

```powershell
git clone --branch main https://github.com/fengmian-coder/ITDB2026.git
Set-Location ITDB2026
.\scripts\start.ps1
```

首次运行按提示填写 MySQL 主机、端口、项目库名、账号和密码。默认连接地址为 `127.0.0.1:3306`，默认项目库名为 `worldcup_journey`。建议为项目使用独立数据库。

脚本会校验 PHP 与 Composer，安装锁定的依赖，准备本地配置和数据库，导入赛事数据并初始化团队、登记课程资料。已有完整的本地配置会被复用。

### 2. 打开网站

打开终端 `Ready at` 后显示的地址，通常为 `http://127.0.0.1:8080/`。默认端口无法使用时，脚本会在 `8080` 至 `8100` 内寻找可用端口。保持启动终端开启，按 `Ctrl+C` 可以停止本地服务。

- **浏览前台**　从「球队」开始查看球队档案和比赛历程，或从「数据总览」进入比赛日历。
- **下载课程作业**　打开「关于我们」，团队成果在「团队作业」区，四位成员的个人作业各自归入对应的成员页面。
- **登录后台**　点击「管理员登录」。首次初始化时，若管理员表为空，脚本会创建 `wcj_admin`，并在终端显示一次随机密码。已有管理员时继续使用原账号。

### 可选启动参数

```powershell
# 指定空闲端口
.\scripts\start.ps1 -Port 8082

# PHP 或 Composer 未加入 PATH 时，替换为实际安装路径
.\scripts\start.ps1 -Php 'C:\path\to\php.exe' -Composer 'C:\path\to\composer.bat'

# 仅准备依赖、配置和数据库，然后退出
.\scripts\start.ps1 -PrepareOnly
```

如果已有数据库仍显示旧版成员分工，可在更新代码后从项目根目录执行 `php yii project/sync-member-profiles`，同步四位课程成员的分工文案。更多参数和恢复步骤见 [Windows 部署说明](docs/windows-deployment.md)。

## 技术架构与数据

- **应用**　采用 Yii 2.0 Advanced 多应用结构、PHP 8.2、Bootstrap 5 和 Apache ECharts。前台、后台和控制台分别启动各自的 Yii 应用，共享数据库配置、数据模型和业务服务。
- **数据库**　15 张业务表覆盖参赛关系、赛事与场馆、比赛、故事与标签、课程资料、管理员和留言。参赛关系表连接球队与赛事。比赛记录关联主客队和场馆，并为故事、事件和纪念卡提供数据。
- **统计**　比赛时间以 UTC 保存，前台赛程与比赛日历按北京时间显示。进球统计优先使用 120 分钟累计比分，否则采用 90 分钟比分。点球大战单独记录。
- **运行边界**　本机一键启动以 `web/` 为公开目录，并兼容原有前后台地址。数据库账号与 Cookie 密钥分别保存在 Git 忽略的 `config/db.local.php` 和 `config/params-local.php`。

| 路径 | 用途 |
| --- | --- |
| `frontend/`、`backend/` | 前台和后台各自的配置、控制器、视图及 Web 入口 |
| `console/`、`common/` | 控制台配置与命令、共用的 Yii 配置及链接生成 |
| `models/`、`services/`、`views/layouts/` | 共用数据模型、业务服务及页面布局 |
| `web/` | 本机兼容入口与共用静态资源 |
| `config/` | 应用配置与本地配置示例 |
| `data/install.sql`、`data/import/` | 数据库表结构、赛事导入数据与来源记录 |
| `data/team/`、`data/personal/` | 团队作业及个人作业 |
| `scripts/`、`tests/integration/`、`docs/` | 启动脚本、集成测试及项目文档 |

## 团队分工

| 成员 | 主要贡献 |
| --- | --- |
| 卢苑 | 首页与公共导航、团队与作业下载、纪念卡、球队档案及前台交互优化 |
| 胡乐妍 | Yii2 项目初始化、赛事查询与后台、数据导入、精选故事、晋级地图与比分走势、球队 PK 与动态图表、球队档案及 Windows 一键启动 |
| 袁颢心 | 数据库结构、ER 图、球队与参赛关系模型、球队列表及统计服务 |
| 张馨月 | 管理员认证与访问控制、留言审核与回复、系统集成及运行配置说明 |

## 开发文档

- [课程部署文档（Windows 与 macOS）](<docs/WorldCup Journey 开发小组_部署文档(2413275_2413272_2411539_2414029).md>)
- [Windows 一键部署与故障排查](docs/windows-deployment.md)
- [数据库 ER 图](docs/er-diagram.md)与[数据字典](docs/data-dictionary.md)
- [赛事数据来源](docs/data-sources.md)与[导入说明](data/import/README.md)

### 独立 Web 入口

前台和后台的独立入口分别为 `frontend/web/index.php` 和 `backend/web/index.php`。单独部署时，将两个站点的公开目录分别指向 `frontend/web/` 和 `backend/web/`，并在两边使用相同的环境配置：

| 环境变量 | 配置值 |
| --- | --- |
| `WCJ_FRONTEND_ENTRY` | 前台入口完整 URL，包含 `/index.php` |
| `WCJ_BACKEND_ENTRY` | 后台入口完整 URL，包含 `/index.php` |
| `WCJ_ASSET_URL` | 共用静态资源的 URL 前缀，例如两边都将 `/shared` 映射到项目的 `web/` 静态文件。地图等资源也从该前缀加载 |

日常课程演示直接采用前述一键启动流程，前后台和静态资源均通过终端显示的地址访问。

Yii2 模板许可见 [LICENSE.md](LICENSE.md)。
