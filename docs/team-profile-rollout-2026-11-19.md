# 球队图文页面首三队扩展（2026-11-19）

按现有球队列表默认顺序，将乌兹别克斯坦（UZB）、乌拉圭（URU）、伊拉克（IRQ）做成克罗地亚样板的效果。每队包括三项球队概况、位置人数、主教练照片与简介、三位既有代表球员的图文卡，以及默认折叠的完整名单。球员与俱乐部使用“中文（原文）”，原文较小且颜色较浅。

## 名单与概况来源

名单沿用 [FIFA Squad Lists](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf) 的 2026-07-19 22:34 UTC / Version 1 快照，SHA-256 为 `02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。姓名、号码、位置、俱乐部、累计出场与进球均从同一名单表提取；不将后续转会或不同报道的名单混入此版本。

| 球队 | PDF 页 | 门将／后卫／中场／前锋 | 总人数 |
| --- | --- | --- | --- |
| 乌兹别克斯坦 | 48 | 3／10／10／3 | 26 |
| 乌拉圭 | 46 | 3／7／11／5 | 26 |
| 伊拉克 | 24 | 3／9／7／7 | 26 |

概况与教练补充资料：

- 乌兹别克斯坦：[FIFA 球队历史介绍](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/uzbekistan-team-profile-history)、[FIFA 集训名单介绍](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/uzbekistan-announce-training-squad)
- 乌拉圭：[FIFA 球队历史介绍](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/uruguay-team-profile-history)、[FIFA 队长介绍](https://www.fifa.com/es/tournaments/mens/worldcup/canadamexicousa2026/articles/historial-capitanes-uruguay-copa-mundial-de-la-fifa?searchOverlay=1)、[历届主教练](https://www.fifa.com/es/articles/entrenadores-seleccion-uruguay-historia-mundiales)
- 伊拉克：[FIFA 球队历史介绍](https://www.fifa.com/articles/iraq-team-profile-history)、[FIFA 阿诺德上任报道](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/graham-arnold-appointed-iraq-coach)

常用中文译名参照 [直播吧乌兹别克斯坦名单](https://news.zhibo8.com/zuqiu/2026-06-02/6a1e6903ed984native.htm)、[联合早报乌拉圭名单](https://www.zaobao.com.sg/news/sports/story20260601-9135422?ai-allowed=1)、[新浪体育伊拉克名单](https://sports.sina.com.cn/l/2026-06-02/doc-inhzzcpm4504238.shtml)。这些报道仅作译名参照；未收录的完整姓名按原文音译，不以报道中的位置或俱乐部替换原 FIFA 快照。

## 图片与维护

新增 12 张人物照片：每队主教练及原有三位代表球员各一张。来源均为 Wikimedia Commons 具有明确可复用许可的图片，保存 500px 预览版并本地加载，文件原样存储、CSS 裁切。部分使用往年资料照，页面逐张标记年份；不声称统一为 FIFA 官方定妆照。人物姓名、来源、作者、日期、许可、尺寸与 SHA-256 见 [图片清单](../web/images/team-profiles/credits.json) 和 [图片来源说明](../web/images/team-profiles/README.md)。

增强数据位于 `data/team-profile-features-2026.json`，基础档案和数据库保持原样。来源版本不匹配时回退原展示；后台修改教练或代表球员身份后，不继续使用原人物照片。对现有自定义简介与教练介绍保留原有优先规则。

目前共 4 队启用图文及完整名单，其余 44 队保持现有展示。已有机器同步代码与图片即可显示，无需运行初始化、导入或增量数据库命令。

## 验证方式

`php tests/integration/team-profile-feature.php` 为只读检查，覆盖 4 队完整名单、中文与原文、位置人数、照片身份与资源哈希、版本及人物变更回退，并请求全部 48 个球队页面。不在开发库写入临时记录。2026-11-19 实测通过 390 项检查。

浏览器已分别检查三队的桌面图文卡与名单展开：每队显示 26 行、位置分组正确、页面无横向溢出；伊拉克页另检查夜间模式。侯赛因改用同一张照片的较宽原图预览版，避免横向卡片截掉面部。页面预览保存在本机忽略目录 `runtime/previews/*-profile-20261119.jpg`。克罗地亚增强数据与原有照片元数据逐项比对保持一致，原始 V1.1、四人分工和基础档案均未修改。

后续状态：用户已提交并推送 `72a8ef6`，本次交接核实 PR #13 已合并至 GitHub main 的 `6c1238ef7f830b56981886c89278af982aa62711`。小组最终采用最初每人总计 12 队分工，A 余下 IRN／CPV／COD／CAN／GHA／RSA／QAT／ECU 八队，详见 [个人续接记录](handoffs/a-progress-2026-11-19.md)。
