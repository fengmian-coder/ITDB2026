# SEN 塞内加尔队资料验收

核对日期为 2026-11-20，本地分支 `feature/b-match` 基于 `150535d`。

- 固定 FIFA PDF 校验值符合规范。第 39 页共 26 人，号码 1 至 26 各一次。门将 3 人、后卫 8 人、中场 7 人、前锋 8 人。三张球员卡沿用基础档案的身份和顺序。
- 球队档案和四张照片署名均按 key 追加到正式 JSON，与本队提交片段一致，原有数据保留。
- 四张图片通过尺寸、SHA-256 和浏览器加载检查。帕佩·蒂奥使用 2025 年采访截帧，具体授权和少见中文译名见 `sources.md`。
- 名单默认折叠，按 Enter 展开后浏览器显示 26 行。日夜模式均已验证。球队档案集成测试通过 1020 项只读检查。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=82)。本地截图保存在 `runtime/sen-profile-day.jpg`、`runtime/sen-profile-roster.jpg` 和 `runtime/sen-profile-dark.jpg`。
