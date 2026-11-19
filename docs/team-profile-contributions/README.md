# A 的剩余八队资料交付（2026-11-19）

范围沿用最终确认的原始分工：A 总计 12 队，已完成 UZB／URU／IRQ／CRO；本次只处理 IRN／CPV／COD／CAN／GHA／RSA／QAT／ECU。COL／TUR／EGY 不在 A 的任务内。

**最新状态：用户已授权正式接入六队。** IRN／COD／CAN／GHA／QAT／ECU 已按 key 合入正式增强 JSON 与图片 credits，本机网页现在直接显示新资料；连同原四队，共 10 队增强。CPV／RSA 仍为未接入的三图草稿。本目录保留逐队原始贡献片段，应用读取的是已合入的正式 JSON，仍不会自动扫描本目录。公共模板、CSS、基础档案及数据库未改。

| 球队 | 名单 | 人物照片 | 检查状态 | 交付 |
| --- | --- | --- | --- | --- |
| 伊朗 IRN | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](IRN/acceptance.md) |
| 佛得角 CPV | 26 人 | 3 张 | 草稿：缺教练合规照片 | [验收记录](CPV/acceptance.md) |
| 刚果民主共和国 COD | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](COD/acceptance.md) |
| 加拿大 CAN | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](CAN/acceptance.md) |
| 加纳 GHA | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](GHA/acceptance.md) |
| 南非 RSA | 26 人 | 3 张 | 草稿：缺莫科纳合规照片；PDF 俱乐部空白策略待确认 | [验收记录](RSA/acceptance.md) |
| 卡塔尔 QAT | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](QAT/acceptance.md) |
| 厄瓜多尔 ECU | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](ECU/acceptance.md) |

共 208 条名单记录、30 张本地照片。每队含 `feature.json`、`credits.json`、`sources.md`、`acceptance.md`，以及日间／名单展开／夜间截图。图片实体在 `web/images/team-profiles/`，由各队 credits 路径精确指向。

六队通过严格逐队脚本；正式接入后，`tests/integration/team-profile-feature.php` 已同步六队独立预期并通过 678 项只读检查，覆盖 48 队 HTTP 页面、原功能保护、身份回退、图片尺寸及哈希。图片总 README 同步追加了 24 项署名。CPV／RSA 缺图不算通过；人工音译范围及日期说明见各队 sources，未宣称全批最终验收完成。

## 集成人审核与接入

1. 先读每队 sources 和 acceptance，处理照片缺项、音译及源资料空白；逐张核实来源许可，不能仅凭 JSON 通过就放行。
2. 按队合并 `feature.json` 的 `CODE` 到正式 `data/team-profile-features-2026.json` 的 `teams`，保留所有原键；将本队 credits 按照片 key 追加到 `web/images/team-profiles/credits.json`。遇重名键应停止核实，不整文件覆盖。
3. 同步图片总 README 与正式测试的固定预期，预期来自 PDF 的独立核对：IRN 3/9/8/6、CPV 3/8/12/3、COD 3/8/8/7、CAN 3/9/7/7、GHA 3/9/6/8、RSA 3/10/5/8、QAT 3/9/5/9、ECU 3/8/8/7（GK/DF/MF/FW）。不要把尚缺图的队当作四图通过队。
4. 逐队校验、检查截图及页面，执行完整回归与 `git diff --check`。ESP 仍未增强，可继续作为当前负例；以后增强 ESP 时同步调整。

旧 `runtime/preview-a-eight.py` 使用正式接入前的四队快照，现已加入停止保护，不能再用它的 restore 覆盖当前正式资料。正式接入前备份保存在本机忽略目录 `runtime/a-six-formal-integration-backup-20261119/`，并非 Git 交付内容。

Git 同步、保留原交接 docs 的说明见 [本轮交接](../handoffs/a-eight-teams-2026-11-19.md)。助手未执行提交、推送、分支合并或创建 PR。
