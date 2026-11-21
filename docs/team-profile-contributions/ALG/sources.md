# ALG 阿尔及利亚：资料来源与核对口径

核对日期：2026-11-21。名单是项目固定版本资料，不是实时阵容；历史照片不用于判断本届球衣、号码或俱乐部。

## 名单与身份

- [FIFA 官方名单](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf#page=1)，2026-07-19 22:34 UTC · Version 1。
- PDF SHA-256：`02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。文件实测哈希与规范一致。
- 逐行提取号码、位置、姓名、俱乐部、国家队累计出场及进球；号码 1–26 各出现一次。原文的拼写和俱乐部保留，PLAYER NAME 栏仅调整英文大小写及姓名顺序。
- 基础档案、教练身份和三位代表球员顺序不改。代表球员姓名与基础档案逐字对齐，号码、位置、俱乐部及累计数据另行比对。
- 阵容位置计数：GK 3、DF 9、MF 7、FW 7。

## 中文显示名称

- [中文名单固定修订版](https://zh.wikipedia.org/w/index.php?oldid=95049982)，仅参考中文名称，不采用其球队名单、统计或俱乐部覆盖 FIFA PDF。
- 显示名称使用简体常见译法；地区译法差异统一为本项目用语（例如罗伯逊、麦克托米奈）。俱乐部优先复用当前已增强球队的同名译法，新增俱乐部手工映射。
- 原文姓名与俱乐部始终并列显示，中文为辅助标签；少见音译不表示得到足协官方中文命名认可。

## 概况与教练经历

- [FIFA 球队历史](https://www.fifa.com/en/tournaments/mens/worldcup/canadamexicousa2026/articles/algeria-team-profile-history)。
- [CAF 佩特科维奇2024年上任](https://www.cafonline.com/news/algeria-extend-petkovic-contract-before-world-cup-opener-against-argentina/)。

三项概况：首次参赛：1982 年西班牙世界杯；上届成绩：2022 年未参赛；世界杯最佳：2014 年十六强。

教练经历：2024 年 2 月执教阿尔及利亚，此前率瑞士进入 2018 年世界杯十六强和 2020 年欧洲杯八强。

## 四张真实照片

均取自 Commons 文件页，保存来源提供的 JPEG 原图或标准预览文件，不重新压缩、不修图、不生成肖像。页面裁切使用已有公共模板；教练采用已有 portrait 显示方式。

| 人物 | 文件 | 拍摄日期 | 作者 | 许可 |
| --- | --- | --- | --- | --- |
| [Vladimir Petkovic](https://commons.wikimedia.org/wiki/File:Football_against_poverty_2014_-_Vladimir_Petkovi%C4%87.jpg) | `alg-coach-feature.jpg` | 2014-03-04 | Ludovic Péron | [CC BY-SA 3.0](https://creativecommons.org/licenses/by-sa/3.0) |
| [Aissa Mandi](https://commons.wikimedia.org/wiki/File:Mandi_asselosc_2425_%28cropped%29.jpg) | `alg-player-2-feature.jpg` | 2024-09-14 | Paté kroute | [CC0](https://creativecommons.org/publicdomain/zero/1.0/deed.en) |
| [Nabil Bentaleb](https://commons.wikimedia.org/wiki/File:RC_Lens_-_Lille_OSC_%2808-10-2023%29_19_%28cropped%29.jpg) | `alg-player-19-feature.jpg` | 2023-10-08 | Supporterhéninois | [CC0](https://creativecommons.org/publicdomain/zero/1.0/deed.en) |
| [Riyad Mahrez](https://commons.wikimedia.org/wiki/File:Mahrez_2021.jpg) | `alg-player-7-feature.jpg` | 2022-01-11 | Jeanpierrekepseu | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |

日期采用 Commons `DateTimeOriginal` 元数据而非文件名猜测；元数据、下载地址、原图地址、尺寸和 SHA-256 完整记于 `credits.json`。照片年份均在页面标注；旧照不冒充本届摄影。

## 接入方式

`feature.json` 为按 FIFA 代码包裹的单队片段，`credits.json` 为四条照片 key。已按 key 追加到本地正式运行 JSON，便于直接验收；原有队伍键和署名保持不变。基础数据、模板、CSS 和数据库不改。

注意：马赫雷斯照片文件名含 2021，来源拍摄日期为 2022-01-11，页面按 2022 标注。曼迪和本塔莱卜照片来源为 CC0，不冒充 CC BY 许可。
