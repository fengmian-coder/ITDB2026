# A 剩余八队：本轮实现与续接

更新：2026-11-19（北京时间）。用户为 A 卢苑。此记录接续本日 `a-progress-2026-11-19.md`；原有五份未提交交接 docs 已原样保留，未覆盖。

## 最新续接：已获用户授权正式接入六队

用户随后明确要求“正式接入网页”。已将 IRN／COD／CAN／GHA／QAT／ECU 的增强对象与 24 项图片 credits 按 key 追加到正式文件，保留全部原四队数据；本机网页现在共有 10 队增强，可直接从球队列表查看。CPV／RSA 继续保留未接入草稿，照片缺项及其他审核说明不变。

正式 `tests/integration/team-profile-feature.php` 已追加六队独立核对的位置人数，原断言及 ESP 负例保留，实际通过 **678 项只读检查**。图片总 README 已追加 24 项署名。未改基础档案、公共模板、CSS 或数据库，未暂存、提交、推送、合并分支或创建 PR。

正式接入前文件备份在 `runtime/a-six-formal-integration-backup-20261119/`。旧预览脚本已加入停止保护，防止用四队旧快照覆盖当前正式数据。**下方“恢复原运行 JSON／当前仅四队”等描述是前一阶段的历史，当前以本节为准。**

用户准备提交正式接入时，还需明确加入这四个文件，再检查暂存差异：

```powershell
git add -- data/team-profile-features-2026.json web/images/team-profiles/credits.json web/images/team-profiles/README.md tests/integration/team-profile-feature.php
git diff --cached --name-only
git diff --cached --check
```

后续五份原交接 docs 仍按原样保留，本轮不会覆盖；此前的按队片段、照片、截图清单仍需随用户所选提交范围一并核对。

## 前一阶段交付与未完成项（历史）

全部八队已整理为 [按队贡献片段](../team-profile-contributions/README.md)：IRN、CPV、COD、CAN、GHA、RSA、QAT、ECU。每队 26 人，共 208 条记录，保留既有教练／三名代表球员身份、来源版本、累计数字；补中英显示、三项概况和教练经历，共保存 30 张核实来源的本地照片与 24 张预览截图。

- IRN／COD／CAN／GHA／QAT／ECU：逐队结构、四张图片及页面检查通过，交付供集成人审核；少见姓名的人工音译待复核范围见各队 sources。
- CPV：缺布比斯塔合规照片。候选 Commons 声明与原始网站只授权文字的说明冲突，已排除；当前是三图草稿，未验收。
- RSA：缺 Teboho Mokoena 合规照片。候选 EXIF 标有 BackpagePix 版权及编辑用途限制，已排除；没有用 Aaron Mokoena 等其他人顶替。本队是三图草稿，未验收。
- RSA #23 Jayden Adams 的固定 FIFA PDF 俱乐部栏原本为空，当前明确显示“FIFA 名单未列明”，未补造实际俱乐部。空值展示策略待集成人确认。
- CPV 现有近景照片还需复核留白；COD 教练照只知拍摄月份，date 使用可核实的上传日，已注明；这些不应被省略为“全部八队验收通过”。

按统一规范采用片段交付：**两份共享运行 JSON 已恢复本轮开始时的原字节，当前网站仍是原四队增强。新八队不会因片段存在而自动显示。** 本轮截图来自临时追加数据的真实页面。未修改公共服务、视图、CSS、基础档案、正式集成测试、图片总 README 或数据库；待集成人按 key 审核合入并维护正式总预期。

## Git、PR 与服务的本轮实查

- 当前分支 `feature/a`，HEAD `72a8ef6`；没有暂存、提交、推送、切分支或合并，也未创建 PR。
- 已 fetch origin；`origin/main` 为 `6c1238ef7f830b56981886c89278af982aa62711`，HEAD 是其祖先，文件树与 HEAD 完全一致，差别只有 #11／#12／#13 三个合并节点。
- GitHub API 本轮重新核实：PR #11 合并时间 `2026-11-18T13:30:46Z`，PR #13 为 `2026-11-19T02:19:24Z`；均已合并。
- 本地 main 仍为旧 `f6915e5`，未自动切换或更新。旧 PR 不会因后续提交自动重开。
- MySQL80：Running；`http://127.0.0.1:8080/`：HTTP 200。复用原实例，未重启或初始化数据库。

## 未提交交接文档的保护

本轮开始时发现四个已修改文档及一个未跟踪文档：

```text
docs/a-local-user-guide.md
docs/handoffs/a-integration-2026-11-12.md
docs/handoffs/a-team-site.md
docs/team-profile-rollout-2026-11-19.md
docs/handoffs/a-progress-2026-11-19.md
```

已复制至忽略目录 `runtime/a-eight-teams-backup-20261119/docs/`，并保存 SHA-256 清单。收尾再次核对，五份原文档字节均未改变。两份共享 JSON 的原字节副本也在该快照下。runtime 不会随 Git 同步；这是本机备份，不是远程备份。

## 本轮验证结果

- 固定 PDF SHA-256 与约定一致；八队均对照对应页渲染图核对 26 行。
- 八队贡献 JSON 拒绝重复键、无 BOM，校验来源、身份、数字和中文字段；全部本地图片格式／尺寸／SHA-256 一致；俱乐部中文按同一原文键保持一致。
- 六队严格逐队脚本 PASS；CPV 因缺教练图、RSA 因缺代表球员图未通过，未删除或放宽四图断言。
- 临时仅接入上述六队、加原四队时，原集成套件的 runtime 副本通过 **678 项只读检查**。副本仅调整根路径、六队独立核对的位置人数及范围说明；保留原功能、回退及 48 队页面断言。
- 八队临时预览均有 26 个名单行、52 项原文；桌面 1440px 与小屏 390px 无横向溢出；实际操作名单／来源展开及昼夜切换；每队保存三张证据图。
- 收尾恢复两份运行 JSON 原字节后，正式 `tests/integration/team-profile-feature.php` 再次通过 **390 项只读检查**；`git diff --check` 通过。
- Git diff 确认原基础档案、两份正式 JSON、服务、视图、CSS 与正式测试未改。未修改最初 V1.1 或四人分工文件，未执行数据库写入／导入／清理流程。

## 由用户执行的主线同步

以下适用于本记录核实的状态：HEAD 仍为 `72a8ef6`、尚未产生新提交，`git diff HEAD origin/main` 无输出。此时可以快进到已合并主线，文件树相同，所以不会替换现有未提交 docs 或新增素材。**这些命令尚未执行。**

```powershell
cd 'D:\互联网数据库开发\团队作业\ITDB2026'
git status --short --branch
git diff --name-status HEAD origin/main
git merge --ff-only origin/main
git status --short --branch
```

若期间有新提交或再次 fetch 后差异改变，先重新分析；不要盲目沿用此快进命令。若先提交了本轮资料导致分叉，快进命令会安全拒绝，此时由用户决定正常合并主线，不 reset／force push。

之后由用户选择是否把原五份交接 docs 一起提交，或者单独整理。新增片段与图片可明确暂存，先审核 CPV／RSA 草稿是否纳入同一 PR：

```powershell
git add -- docs/team-profile-contributions docs/handoffs/a-eight-teams-2026-11-19.md
git add -- web/images/team-profiles/irn-*.jpg web/images/team-profiles/cpv-*.jpg web/images/team-profiles/cod-*.jpg web/images/team-profiles/can-*.jpg web/images/team-profiles/gha-*.jpg web/images/team-profiles/rsa-*.jpg web/images/team-profiles/qat-*.jpg web/images/team-profiles/ecu-*.jpg
git diff --cached --name-only
git diff --cached --stat
git diff --cached --check
```

这是候选暂存清单，不代表已执行或已通过团队资料审核。不要直接 `git add -A`。提交、推送、新 PR 与合并均由用户执行。正式接入需同步共享数据、总图片目录及正式测试预期；片段 PR 本身不能视为线上已启用。

## 下一次继续

先读本文件及贡献目录 README，保留当前未提交内容，不重做原四队或本轮已整理的 208 条名单。继续寻找 CPV 教练／RSA 莫科纳许可明确且能核实身份的照片，完成少见译名与 RSA 空值策略审核，再由指定集成人统一合入。不要把当前状态误记为“八队已经全部验收”。
