# 佛得角、南非缺照：扩大检索续记

核查日期：2026-11-19。用户确认照片可以来自不同年份、俱乐部、训练或采访，不要求统一摄影系列。本轮据此扩大搜索，保留原教练及三位代表球员身份。

## 最新决定：用户指定图片，已完成两队接入

用户随后提供百度百科布比斯塔图片与附件、日落俱乐部莫科纳人物页，并明确要求“直接用吧，我们这个小组作业也不需要发行、盈利之类的”。现已依此补齐 `cpv-coach` 与 `rsa-mokoena`，正式接入两队。两个原人物身份保持不变，未改选球员或主教练。

教练原始 PNG 附件保留百度水印，完整画面显示；莫科纳采用官网正面照原 JPG。两张均记录 `rights_status: unverified`、`license_url: null` 及实际来源，页面显示“版权保留 · 未取得开放许可”。拍摄日期未知，2026-11-19 仅标为收录日期。用户的课程作业使用决定没有被写成权利人授权或开放许可。

两队各 26 人、4 张本地图，页面与技术回归完成，最新整体验收为 780 项只读检查。具体来源及哈希见 [CPV sources](CPV/sources.md)／[RSA sources](RSA/sources.md)。下方为用户作出此决定前的检索历史，旧“尚未接入”及后续选人建议不再表示当前待办。

## 补查阶段结论（历史）

尚未新增可验收照片。CPV 仍缺教练 Bubista，RSA 仍缺中场 Teboho Mokoena（1997 年出生）。两队仍为三图贡献草稿，没有追加到正式增强 JSON。已接入的六队继续使用现有资料。

本轮没有变更基础身份、数据接口、公共视图、CSS、测试或数据库，也没有把“不同风格可用”解释成取消来源和许可核查。

## 可复查的候选与决定

| 人物／渠道 | 本轮核实结果 | 处理 |
| --- | --- | --- |
| Bubista／[Commons 文件](https://commons.wikimedia.org/wiki/File:Bubista_2026.jpg) | 文件页标 CC BY-SA 4.0；原站 [Polites News 的专门许可页](https://polites.news/license.html) 第“Что не охватывается лицензией”节明确排除照片、视频和文章封面，要求另获权利人许可。原站文字使用 CC BY 4.0，不能用于证明此图的 CC BY-SA 4.0。 | 继续排除；其他网站转载同图及相同 CC 标签不能解决这一冲突。 |
| Bubista／[VOA 2022 年非洲杯图集，第 8 张](https://www.voaportugues.com/a/campeonato-africano-das-na%C3%A7%C3%B5es-can-2021-15o-dia/6415060/p1.html) | 图片说明明确为 Bubista 与 Aliou Cissé，日期为 2022-01-25。当前页面未提供足以确认该照片属于 VOA 自摄、可复用的逐图权利信息；读取的预览图没有作者或版权 EXIF。 | 保留为身份明确、许可待核的候选；不能仅因 VOA 刊登便标为公共领域。未入库。 |
| Teboho Mokoena／[日落俱乐部官方人物页](https://sundownsfc.co.za/player/teboho-mokoena/) | 明确标注中场、1997-01-24 出生，有本人照片；页脚为俱乐部保留全部权利。本轮未找到允许复制该图进仓库的许可声明。 | 可作为身份参照及请求授权的候选；未下载入正式图片目录。 |
| Teboho Mokoena／[Investec 官方人物页](https://www.investec.com/en_mu/sponsorships/teboho-mokoena.html) | 本人采访和照片可作为身份参照；本轮未核实到照片的开放复用许可。 | 待授权候选，未入库。 |
| Teboho Mokoena／[Commons 的 Teboho.webp](https://commons.wikimedia.org/wiki/File:Teboho.webp) | 既有核查记录：文件页许可与内嵌 BackpagePix 版权及 Sports editorial use only 冲突。 | 维持原排除决定，不因放宽照片风格而重新采用。 |

## 补充检索范围与限制

- Commons 检索覆盖 Bubista、Pedro Brito、Teboho Mokoena、Mokoena＋Sundowns 等名字与球队组合。未确认新的合格目标照片；同名 Aaron Mokoena 及其他同名人物不作替代。
- Flickr 使用可复用许可筛选，读取搜索页实际返回的照片模型；检索 Bubista、Pedro Brito Cabo Verde、Cape Verde coach、Teboho Mokoena、Mamelodi Sundowns、South Africa Rwanda football。精确人物查询本轮为空；宽查询有结果，但说明对应其他教练、官员或较早的俱乐部活动，未核实到目标人物照片。这只是本轮检索结果，不表示所有图库都没有素材。
- Openverse API 本轮返回 HTTP 403，因此该渠道记作“无法完成查询”，不能记作“没有照片”。
- 搜索返回的 Getty、NurPhoto、Imago 等付费图库候选未购买、未下载入库；没有向摄影师、媒体或俱乐部发送消息。
- 本机忽略目录保留检索依据：`runtime/cpv-rsa-wider-photo-search.json`、`runtime/cpv-rsa-flickr-followup.json`、`runtime/polites-bubista-current.html`、`runtime/voa-bubista-gallery-source.html`。这些不是正式素材，也不应提交源网页大包。

## 可执行的后续路径

1. 优先为原人物补充许可明确的照片，或取得上述候选图的复制与网站展示授权。收到证据后再保存本地图、补齐 credits、按 key 接入并运行现有验收。
2. 南非可另行审议改选本届名单中的中场 Themba Zwane：[已有 CC BY 4.0 比赛照候选](https://commons.wikimedia.org/wiki/File:Red_Card_M%C3%A9xico_vs_South_Africa_2026_(cropped).jpg)。该图包含裁判和红牌，人物较小，需先确认展示效果。改选会改变原“各位置出场数最多”规则及基础身份，需要用户明确选择后统一处理身份来源、增强映射和实际运行档案；本轮未改选。
3. 佛得角缺的是主教练照片，改代表球员不能补上该缺项；不更换既定世界杯教练来迁就图片。

正式接入仍要求 26 人名单、原人物身份与四张本地图的来源、许可、哈希及页面检查一致。不得把本记录标成两队已验收。
