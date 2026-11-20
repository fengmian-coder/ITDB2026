# CUW 库拉索队资料验收

核对日期为 2026-11-20，本地 `feature/b-match` 基于 `150535d`。

- 固定 FIFA PDF 第 14 页共 26 人，球衣号 1 至 26 各一次。门将、后卫、中场、前锋人数依次为 3、8、7、8。代表球员身份和排列顺序与基础档案相符。
- 正式档案与四张照片署名按 key 追加，与提交片段一致。两份正式 JSON 通过重复键和原有条目检查。
- 四张照片均核对来源、许可、尺寸和 SHA-256。浏览器实测四张均可加载，名单通过键盘展开为 26 行，切换夜间模式时保持展开。球队档案回归测试通过 1356 项只读检查。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=66)。截图在 `runtime/cuw-profile-day.jpg`、`runtime/cuw-profile-roster.jpg` 和 `runtime/cuw-profile-dark.jpg`。
- 少见姓名的音译复核范围及各照片出处见 `sources.md`。
