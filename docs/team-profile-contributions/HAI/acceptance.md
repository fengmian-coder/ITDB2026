# 海地（HAI）验收记录

- 状态：数据完成；照片缺项：coach、Ricardo Ade
- 名单：26 人，号码 1—26 各一次；GK/DF/MF/FW = 3/9/5/9
- 双语字段：26 人 `name_zh`、`club_zh` 均非空；已对照中文维基百科 2026 世界杯参赛球员名单修订 12 处，剩余译名待集成人复核
- 球队概况：3 项；教练经历：已填写；来源对象：已填写
- 基础身份：source_url、edition、summary、coach_name、coach_description 与基础档案逐项一致
- 照片：2/4；缺失：coach、Ricardo Ade
- 未完成：集成人完整回归；页面截图见下节
- 备注：未修改数据库、正式增强 JSON、正式 credits、模板、CSS 或测试

## 页面截图（本地 1440px）

- [`day.png`](https://github.com/fengmian-coder/ITDB2026/blob/2d41d3e917c6c6190de9b018bda02ebc5928f092/docs/team-profile-contributions/HAI/previews/day.png)：电脑概况＋教练/三位代表球员卡片，名单折叠，日间模式。
- [`roster.png`](https://github.com/fengmian-coder/ITDB2026/blob/2d41d3e917c6c6190de9b018bda02ebc5928f092/docs/team-profile-contributions/HAI/previews/roster.png)：26 人名单展开＋来源区展开，日间模式。
- [`night.png`](https://github.com/fengmian-coder/ITDB2026/blob/2d41d3e917c6c6190de9b018bda02ebc5928f092/docs/team-profile-contributions/HAI/previews/night.png)：同一页面夜间模式，名单与来源区保持展开。
- 预览地址（本机临时合并）：`http://127.0.0.1:8080/index.php?r=national-team/view&id=59`；过程截图可通过上方链接查看。
- 说明：教练照和 Ricardo Adé 照为明确缺项；#4 代表球员卡片无照片时出现留白，未改共享模板，待补图或集成人统一处理。截图只证明数据页可渲染，不作为通过证据。

## 复核提示

1. 中文译名已对照中文维基百科 2026 世界杯参赛球员名单逐名复核；仍建议按中文体育媒体/足协资料确认人工音译项。
2. 缺失照片需补来源可复核的 Wikimedia Commons 文件，不能用他人照片顶替；教练照和 Ricardo Adé 照已检索姓名变体、Commons 分类和 Wikidata P18，暂无可靠文件。
3. 本地预览时再把本队对象临时追加到正式 JSON；最终 PR 只提交本队片段、图片和本记录。
