# A 的剩余八队资料交付（2026-11-19）

范围沿用最终确认的原始分工：A 总计 12 队，已完成 UZB／URU／IRQ／CRO；本次只处理 IRN／CPV／COD／CAN／GHA／RSA／QAT／ECU。COL／TUR／EGY 不在 A 的任务内。

**最新状态：剩余八队已全部接入网页。** IRN／CPV／COD／CAN／GHA／RSA／QAT／ECU 均已按 key 合入正式增强 JSON 与图片 credits；连同原四队，共 12 队增强。用户明确指定百度百科布比斯塔照及日落官网莫科纳照用于课程作业，两张如实记录“版权保留 · 未取得开放许可”，不冒充 CC 授权。本目录保留逐队贡献片段，应用读取正式 JSON，不会自动扫描本目录。基础身份、CSS、数据库未改；通用服务增加原始 PNG 支持，照片 partial 增加完整画面显示，来源区支持未知拍摄日期及无许可链接的真实说明。

| 球队 | 名单 | 人物照片 | 检查状态 | 交付 |
| --- | --- | --- | --- | --- |
| 伊朗 IRN | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](IRN/acceptance.md) |
| 佛得角 CPV | 26 人 | 4 张 | 技术与页面检查通过；教练照按用户指定用于课程作业 | [验收记录](CPV/acceptance.md) |
| 刚果民主共和国 COD | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](COD/acceptance.md) |
| 加拿大 CAN | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](CAN/acceptance.md) |
| 加纳 GHA | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](GHA/acceptance.md) |
| 南非 RSA | 26 人 | 4 张 | 技术与页面检查通过；莫科纳照按用户指定用于课程作业，PDF 空白保留明确提示 | [验收记录](RSA/acceptance.md) |
| 卡塔尔 QAT | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](QAT/acceptance.md) |
| 厄瓜多尔 ECU | 26 人 | 4 张 | 逐队结构／四图通过，译名待人工审核 | [验收记录](ECU/acceptance.md) |

本次八队共 208 条名单记录、32 张本地照片；连同原四队，正式运行清单共 48 张。每队含 `feature.json`、`credits.json`、`sources.md`、`acceptance.md`，以及日间／名单展开／夜间截图。图片实体在 `web/images/team-profiles/`，由各队 credits 路径精确指向。

正式 `tests/integration/team-profile-feature.php` 已同步八队独立预期并通过 **780 项只读检查**，覆盖 48 队 HTTP 页面、原功能保护、身份回退、图片尺寸与哈希及两张课程用图的真实说明。原 Commons 署名检查继续用于其他 46 张图片，两张例外按照片 key、人物、来源域名、固定哈希及明确使用说明核对。技术检查不代表取得权利人授权，也不替代人工译名审核。CPV／RSA 的六张页面证据已更新，1440px／390px 与日夜切换实测通过。图片总 README 已追加全部八队来源。

## 集成人审核与接入

1. 先读每队 sources 和 acceptance，处理照片缺项、音译及源资料空白；逐张核实来源许可，不能仅凭 JSON 通过就放行。
2. 按队合并 `feature.json` 的 `CODE` 到正式 `data/team-profile-features-2026.json` 的 `teams`，保留所有原键；将本队 credits 按照片 key 追加到 `web/images/team-profiles/credits.json`。遇重名键应停止核实，不整文件覆盖。
3. 图片总 README 与正式测试固定预期已同步，预期来自 PDF 的独立核对：IRN 3/9/8/6、CPV 3/8/12/3、COD 3/8/8/7、CAN 3/9/7/7、GHA 3/9/6/8、RSA 3/10/5/8、QAT 3/9/5/9、ECU 3/8/8/7（GK/DF/MF/FW）。两张课程用图的例外仅对应本轮用户指定图片，不能泛化为所有外站图片已获许可。
4. 逐队校验、检查截图及页面，执行完整回归与 `git diff --check`。ESP 仍未增强，可继续作为当前负例；以后增强 ESP 时同步调整。

旧 `runtime/preview-a-eight.py` 使用正式接入前的四队快照，现已加入停止保护，不能再用它的 restore 覆盖当前正式资料。正式接入前备份保存在本机忽略目录 `runtime/a-six-formal-integration-backup-20261119/`，并非 Git 交付内容。

Git 同步、保留原交接 docs 的说明见 [本轮交接](../handoffs/a-eight-teams-2026-11-19.md)。助手未执行提交、推送、分支合并或创建 PR。
