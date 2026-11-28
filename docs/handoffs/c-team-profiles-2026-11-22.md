# C 的 12 支球队资料交接（2026-11-22）

## 范围与基线

- 依据：《WorldCup_Journey_48支球队页面完善_分工交接与统一实现规范_2026-11-19》。
- C 负责球队：CZE、MAR、NZL、JPN、BEL、KSA、FRA、BIH、HAI、AUS、SWE、SUI，共 12 队。
- FIFA 名单：`runtime/SquadLists-English.pdf`，48 页，SHA-256 `02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`，版本 `2026-07-19 22:34 UTC · Version 1`。
- 未修改 `data/team-profiles-2026.json`、正式 `data/team-profile-features-2026.json`、正式 `web/images/team-profiles/credits.json`、模板、CSS、数据库或测试。

## 已完成

- 12 队各 26 人完整名单，号码 1—26、位置 GK/DF/MF/FW、累计出场/进球均来自同一 PDF 页码。
- 12 队各 3 项球队概况、教练经历、中英文姓名与俱乐部字段。
- 每队交付目录：

  ```text
  docs/team-profile-contributions/CODE/
    feature.json
    credits.json
    sources.md
    acceptance.md
  ```

- 共 45 张本地人物照片，位于 `web/images/team-profiles/`；每张含来源页、实际下载地址、作者、日期、许可、尺寸和 SHA-256。
- 中文译名：以中文维基百科《2026年国际足协世界杯参赛球员名单》逐名比对，并用百度百科/懂球帝等中文体育资料抽查，修订 60 处姓名、14 处俱乐部译名；`sources.md` 记录了本队修订数，剩余人工音译项仍建议按中文体育媒体复核。

## 逐队状态

| 球队 | 名单 | 照片 | 附录校验 | 备注 |
|---|---|---:|---|---|
| MAR 摩洛哥 | 26 | 4/4 | PASS | |
| NZL 新西兰 | 26 | 4/4 | PASS | |
| JPN 日本 | 26 | 4/4 | PASS | |
| BEL 比利时 | 26 | 4/4 | PASS | |
| FRA 法国 | 26 | 4/4 | PASS | 教练照为 2011 年合影 |
| BIH 波黑 | 26 | 4/4 | PASS | |
| AUS 澳大利亚 | 26 | 4/4 | PASS | |
| SWE 瑞典 | 26 | 4/4 | PASS | |
| SUI 瑞士 | 26 | 4/4 | PASS | 教练照作者字段来源页未提供 |
| CZE 捷克 | 26 | 4/4 | PASS | 教练照为 2015 年比尔森胜利夺冠庆典；EXIF 日期错误，已按文件页记录并注明 |
| KSA 沙特阿拉伯 | 26 | 3/4 | 缺项（保留） | 缺主教练 Georgios Donis；见下节 |
| HAI 海地 | 26 | 2/4 | 缺项（保留） | 缺主教练 Sébastien Migné、Ricardo Adé；见下节 |

说明：KSA/HAI 的附录失败点均为 `Missing coach_photo`，不是名单或双语字段错误；两队名单与 26 人结构已通过独立结构校验。CZE 已补齐教练照并通过附录。

### 明确保留的缺项

以下 3 张照片在本次交付中**明确保留为缺项**，不是遗漏，也不以他人照片或版权不明图片顶替：

| 球队 | 缺失项 | 已核查范围 | 结论 |
|---|---|---|---|
| KSA | 主教练 Georgios Donis | Wikidata P18、Commons 姓名变体、Al-Hilal/Saudi Arabia coaches 分类、2026 世界杯沙特照片 | 无可用 Commons 文件 |
| HAI | 主教练 Sébastien Migné | Wikidata P18、Haiti/Kenya coaches 分类、Getty/AFP | Getty/AFP 为版权保留，非 CC；Commons 无文件 |
| HAI | Ricardo Adé | Wikidata P18、Haiti players 分类、LDU Quito 与海地历史照片 | 他 2016 年才为海地首秀，现有 Commons 照片早于其国家队生涯；无匹配文件 |

处理约定：

- KSA、HAI 分别按 3/4、2/4 交付；附录校验会停在 `Missing coach_photo`，这是**预期结果**。
- 集成人可先合并 10 队完整片段；KSA/HAI 的数据片段可一并合并，但在照片补齐前不得把这队标记为“通过”。
- 如后续找到 Commons 文件，按现有 credits 格式补 key，再跑附录；如团队决定接受非 Commons 的 CC 素材或已授权官方照片，须先由集成人调整附录的来源域名校验并记录许可证据。

## 验证记录

- 逐队结构校验：12/12 队 JSON 可解析、26 人、号码 1—26、双语字段非空、3 项概况、基础身份字段逐项一致；照片尺寸与 SHA-256 全部匹配。
- 临时把 12 队合并进正式 JSON/credits 后运行文档附录脚本：
  - **10 队 PASS**：CZE、MAR、NZL、JPN、BEL、FRA、BIH、AUS、SWE、SUI；
  - **2 队仅因缺照片未通过**：KSA、HAI。
- 验证后已恢复正式 JSON/credits 原状；当前正式文件仍是 24 队、96 张 credits，没有被本次片段污染。
- [历史页面截图](https://github.com/fengmian-coder/ITDB2026/tree/2d41d3e917c6c6190de9b018bda02ebc5928f092/docs/team-profile-contributions)：12 队各 3 张，1440px 全页，分别为 `previews/day.png`（概况＋人物卡片、名单折叠）、`previews/roster.png`（26 人名单＋来源区展开）、`previews/night.png`（夜间模式、名单与来源区展开）。KSA/HAI 为缺项状态截图，不作为附录通过证据；HAI #4 Ricardo Adé 无照片时代表球员卡片留白，已记录待集成人统一处理或补图。

## 未完成与下一步

1. 集成人按队合并 10 队完整片段；KSA/HAI 先合并数据，照片补到后再跑附录。
2. 继续补 KSA 教练 Georgios Donis、HAI 教练 Sébastien Migné、HAI Ricardo Adé 照；已检索 Giorgos/Georgios Donis、Γιώργος Δώνης、Sébastien/Sebastien Migné、Ricardo Adé/Ade 等姓名变体、Wikidata P18，以及 Haiti coaches/players、Kenya national team、LDU Quito 2015、Al-Hilal coaches 和 2026 世界杯相关 Commons 分类，均无可靠文件。Getty/AFP 有 Migné 的执教照，但为版权保留，不能作为 CC 素材入库；暂保留明确缺项，不用他人照片顶替。
3. 如团队接受非 Commons 的 CC 素材或已授权的官方照片，需由集成人先调整附录脚本的来源域名校验并记录许可证据；当前不擅自修改共享校验，也不把未授权图片放进仓库。
4. 中文译名已完成一轮逐名比对（60 处姓名、14 处俱乐部），并用百度百科/懂球帝等中文体育资料抽查；剩余人工音译项建议集成人再确认。
5. 页面截图已生成并放在各队 `previews/`：`day.png`、`roster.png`、`night.png`，共 36 张。KSA/HAI 为缺项状态截图；HAI #4 无照片时卡片留白，待补图或集成人统一处理缺图占位。
6. 集成人更新 `$expectedCounts` 与正式 credits 总 README；跑完整回归。C 的 12 队完成后，配合 D 的 12 队，48 队才齐。
7. 本文件与贡献片段尚未 Git 提交/推送；提交范围应只含本队片段、45 张人物图片、各队 `previews/` 的 36 张页面截图、sources/acceptance 和本交接，不含正式 JSON/credits 或 `runtime`。
