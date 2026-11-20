# MEX 墨西哥队资料验收

核对日期为 2026-11-20，本地分支 `feature/b-match` 基于 `150535d`。

- 固定 FIFA PDF 校验值符合规范。第 28 页有 26 人，号码 1 至 26 各一次。门将 3 人、后卫 7 人、中场 8 人、前锋 8 人。代表球员身份和顺序与基础档案一致。
- 墨西哥队档案和四张照片署名已按 key 追加到正式 JSON。两份 JSON 均核对了重复键和原有条目。
- 四张图通过尺寸、SHA-256 和浏览器加载检查。代表球员和教练在统一卡片中清晰可辨。两张国家队视频截帧均已检查原视频的许可。
- 名单默认折叠，按 Enter 展开显示 26 行。日间及夜间浏览器预览均已核对。球队档案集成测试通过 1068 项只读检查。
- [本机预览](http://127.0.0.1:8082/index.php?r=national-team%2Fview&id=49)。截图为 `runtime/mex-profile-day.jpg`、`runtime/mex-profile-roster.jpg` 和 `runtime/mex-profile-dark.jpg`。少见姓名的人工音译见 `sources.md`。
