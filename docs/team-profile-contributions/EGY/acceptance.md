# EGY 埃及队资料验收

核对日期为 2026-11-19，使用 `feature/b-match` 的本地基线 `a27779f`。

- 固定 FIFA PDF 的 SHA-256 与统一规范相符。第 17 页共 26 人，号码 1 至 26 各一次。门将 4 人、后卫 8 人、中场 7 人、前锋 7 人。代表球员身份和顺序与基础档案一致。
- 正式档案与照片清单通过重复键检查，按 key 追加，保留此前各队数据。
- 四张图均通过解码、尺寸与 SHA-256 核对。浏览器显示四张并复查了人物裁切，教练的低分辨率采访截帧按原比例展示。原视频许可和素材细节见 `sources.md`。
- 名单默认折叠，键盘展开后显示 26 行。日夜模式浏览器验收通过。集成测试通过 972 项只读检查，覆盖 48 支球队详情页。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=74)。截图保存在 `runtime/egy-profile-day.jpg`、`runtime/egy-profile-players.jpg`、`runtime/egy-profile-roster.jpg` 和 `runtime/egy-profile-dark.jpg`。
- 少见球员的人工中文音译及教练图片许可核对情况见 `sources.md`。
