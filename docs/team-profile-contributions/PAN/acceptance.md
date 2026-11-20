# PAN 巴拿马队资料验收

核对日期为 2026-11-20，本地 `feature/b-match` 基于 `150535d`。

- FIFA 固定名单第 33 页有 26 人，号码 1 至 26 各一次。门将 3 人、后卫 11 人、中场 8 人、前锋 4 人。代表球员的身份和顺序与基础档案相符。
- 正式档案与照片署名按 key 追加，与本队提交片段一致。此前各队条目保留。
- 四张照片均核对来源、许可、尺寸和 SHA-256，浏览器可正常加载。戴维斯的 2018 年照片与其余三张 2026 年照片分别标注。
- 名单默认折叠，键盘展开后显示 26 行。日夜模式均已验证。球队档案集成测试通过 1212 项只读检查。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=96)。本地截图为 `runtime/pan-profile-full.jpg`、`runtime/pan-profile-roster.jpg` 和 `runtime/pan-profile-dark.jpg`。少见姓名的音译见 `sources.md`。
