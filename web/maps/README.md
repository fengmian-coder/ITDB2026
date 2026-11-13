# 球队旅程地图资源

底图取自 Natural Earth 5.1.2 的 1:110m 国家边界，仅保留加拿大、美国和墨西哥的几何及国家名称。原始数据为公共领域数据。

- 原始文件 https://github.com/nvkelso/natural-earth-vector/blob/v5.1.2/geojson/ne_110m_admin_0_countries.geojson
- 原始 Git blob 为 `1e6ab74c7042f97013be69ceec798be8e1aff27d`。
- 授权说明 https://www.naturalearthdata.com/about/terms-of-use/

场馆坐标保存在 `data/stadium-coordinates.json`，来自 OpenFootball 的 `2026/worldcup.stadiums.json`。文件记录原始坐标文本、来源场馆名称、来源 URL 和 Git blob。度分秒转换为十进制度，按当前项目已使用的场馆名称、城市及国家进行明确对应。

- 来源 https://github.com/openfootball/worldcup.json/blob/master/2026/worldcup.stadiums.json
- 授权 https://github.com/openfootball/worldcup.json#license
- 本次获取日期为 2026-11-13。

页面使用已安装的本地 ECharts 渲染。连线表达比赛地点的时间顺序，未来比赛使用虚线。缺少已核对坐标时保留时间线并中断地图连线。
