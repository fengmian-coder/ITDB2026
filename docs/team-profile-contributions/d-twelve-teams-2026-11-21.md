# D 的十二队页面补全交接（2026-11-21）

已完成 CIV／TUN／JOR／USA／SCO／ENG／NED／POR／ESP／ALG／ARG／KOR，共 312 名球员、48 张真实照片、36 项球队概况及 12 段教练经历。基线 `112af31`，工作分支 `feature/d-team-profiles`。

每队保留 `feature.json`、`credits.json`、`sources.md`、`acceptance.md` 和三张验收截图。已按 key 接入本地两份正式运行 JSON，并追加图片总目录、更新集成测试预期；页面直接复用既有增强服务和统一模板。原有 24 队及 96 条照片署名逐项比较保持不变。正式增强范围现在是 36 队，另外 12 队保留已有基础页面。

## 逐队交付

| 球队 | 人数 | 照片 | 位置计数 GK/DF/MF/FW | 页面及来源 |
| --- | --- | --- | --- | --- |
| 科特迪瓦 CIV | 26 | 4 | 3/8/6/9 | [验收](CIV/acceptance.md) · [来源](CIV/sources.md) |
| 突尼斯 TUN | 26 | 4 | 3/10/8/5 | [验收](TUN/acceptance.md) · [来源](TUN/sources.md) |
| 约旦 JOR | 26 | 4 | 3/9/8/6 | [验收](JOR/acceptance.md) · [来源](JOR/sources.md) |
| 美国 USA | 26 | 4 | 3/10/6/7 | [验收](USA/acceptance.md) · [来源](USA/sources.md) |
| 苏格兰 SCO | 26 | 4 | 3/10/6/7 | [验收](SCO/acceptance.md) · [来源](SCO/sources.md) |
| 英格兰 ENG | 26 | 4 | 3/9/7/7 | [验收](ENG/acceptance.md) · [来源](ENG/sources.md) |
| 荷兰 NED | 26 | 4 | 3/8/8/7 | [验收](NED/acceptance.md) · [来源](NED/sources.md) |
| 葡萄牙 POR | 26 | 4 | 3/9/6/8 | [验收](POR/acceptance.md) · [来源](POR/sources.md) |
| 西班牙 ESP | 26 | 4 | 3/8/7/8 | [验收](ESP/acceptance.md) · [来源](ESP/sources.md) |
| 阿尔及利亚 ALG | 26 | 4 | 3/9/7/7 | [验收](ALG/acceptance.md) · [来源](ALG/sources.md) |
| 阿根廷 ARG | 26 | 4 | 3/8/8/7 | [验收](ARG/acceptance.md) · [来源](ARG/sources.md) |
| 韩国 KOR | 26 | 4 | 3/9/11/3 | [验收](KOR/acceptance.md) · [来源](KOR/sources.md) |

## 检查结果与范围

- `php tests/integration/team-profile-feature.php http://127.0.0.1:18081`：1,932 项只读检查通过，包括 48 队 HTTP 页面、36 队增强、身份及名单版本变化时的回退、图片尺寸、哈希和署名。负例由本次已增强的 ESP 改为尚未增强的 JPN，保留回退覆盖。
- 固定 PDF 独立整行解析与逐列提取对照：312 人的位置、俱乐部、累计数字一致；连同片段、图片哈希及原数据保留共 724 项检查通过。所有新片段和正式 JSON 使用拒绝重复键的解析器检查。
- 12 队逐页浏览器检查：1440px 日间、完整名单键盘展开、390px 夜间、4 图加载、名单状态保持、来源链接、页面横向溢出检查通过；36 张实际页面截图随每队交付。
- `data/team-profiles-2026.json` 原字节保持不变；原 24 队增强内容与 96 条署名按键比较未变。未修改模板、CSS、数据库和既有分工文档。

## 特别核对说明

- 约旦 #18 身份采用 FIFA 的 Mohammad Abughoush，中文参考名单有错人冲突，未照抄；中文暂译待语言复核。韩国姓名参考汉字写法，避免凭发音猜字。
- 孙兴慜及马赫雷斯照片文件名年份与拍摄日期元数据不同，按元数据分别标 2024、2022；费兰·托雷斯文件名日期也不作为拍摄日期依据。均在逐队来源中说明。
- 荷兰、部分教练等采用历史照片，页面明确标年份；不把旧照片当本届摄影，不以球衣判断固定名单俱乐部。

## 本地接入与后续同步

当前改动在 `feature/d-team-profiles`，正式运行数据已接入本地并完成验收，本次按用户要求提交并推送该分支。若按贡献片段移交，集成人可将每队 feature 按代码、credits 按照片 key 追加，保留所有现有键；总目录与测试预期已在本地同步。

不要用整文件替换旧备份恢复其他成员资料。本次先备份正式文件到本机工作资料目录，再按 key 追加。原有 `scripts/start.ps1` 和两个 `.gitkeep` 的本地改动与本次无关，未覆盖。
