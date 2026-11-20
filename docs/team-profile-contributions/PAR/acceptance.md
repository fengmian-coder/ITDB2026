# PAR 巴拉圭队资料验收

核对日期为 2026-11-20，本地 `feature/b-match` 基于 `150535d`。

- FIFA 固定名单第 34 页有 26 人，号码 1 至 26 各一次。门将 3 人、后卫 8 人、中场 9 人、前锋 6 人。三名代表球员的身份和顺序与基础档案相符。
- 正式档案和照片署名按 key 追加，与提交片段一致。此前各队条目保留。
- 四张世界杯照片均核对了来源、许可、尺寸和 SHA-256。浏览器中四张图都能加载，代表球员的面部在卡片内完整可见。
- 名单默认折叠，按 Enter 展开后为 26 行。日夜模式已验证。球队档案集成测试通过 1164 项只读检查。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=62)。截图为 `runtime/par-profile-full.jpg`、`runtime/par-profile-roster.jpg` 和 `runtime/par-profile-dark.jpg`。少见译名见 `sources.md`。
