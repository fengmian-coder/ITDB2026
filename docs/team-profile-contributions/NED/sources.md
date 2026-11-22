# NED 荷兰：资料来源与核对口径

核对日期：2026-11-21。名单是项目固定版本资料，不是实时阵容；历史照片不用于判断本届球衣、号码或俱乐部。

## 名单与身份

- [FIFA 官方名单](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf#page=30)，2026-07-19 22:34 UTC · Version 1。
- PDF SHA-256：`02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。文件实测哈希与规范一致。
- 逐行提取号码、位置、姓名、俱乐部、国家队累计出场及进球；号码 1–26 各出现一次。原文的拼写和俱乐部保留，PLAYER NAME 栏仅调整英文大小写及姓名顺序。
- 基础档案、教练身份和三位代表球员顺序不改。代表球员姓名与基础档案逐字对齐，号码、位置、俱乐部及累计数据另行比对。
- 阵容位置计数：GK 3、DF 8、MF 8、FW 7。

## 中文显示名称

- [中文名单固定修订版](https://zh.wikipedia.org/w/index.php?oldid=95049982)，仅参考中文名称，不采用其球队名单、统计或俱乐部覆盖 FIFA PDF。
- 显示名称使用简体常见译法；地区译法差异统一为本项目用语（例如罗伯逊、麦克托米奈）。俱乐部优先复用当前已增强球队的同名译法，新增俱乐部手工映射。
- 原文姓名与俱乐部始终并列显示，中文为辅助标签；少见音译不表示得到足协官方中文命名认可。

## 概况与教练经历

- [FIFA 球队历史](https://www.fifa.com/en/tournaments/mens/worldcup/articles/netherlands-team-profile-history)。
- [FIFA 科曼此前任期与球员经历](https://www.fifa.com/en/articles/ronald-koeman-steps-down-netherlands)。

三项概况：世界杯最佳：1974、1978、2010 年亚军；上届成绩：2022 年八强；首次参赛：1934 年意大利世界杯。

教练经历：2023 年 1 月第二次执教荷兰，接替范加尔。此前曾于 2018 至 2020 年带队，球员时期是 1988 年欧洲杯冠军成员。

## 四张真实照片

均取自 Commons 文件页，保存来源提供的 JPEG 原图或标准预览文件，不重新压缩、不修图、不生成肖像。页面裁切使用已有公共模板；教练采用已有 portrait 显示方式。

| 人物 | 文件 | 拍摄日期 | 作者 | 许可 |
| --- | --- | --- | --- | --- |
| [Ronald Koeman](https://commons.wikimedia.org/wiki/File:%D0%9C%D0%B0%D1%82%D1%87_%C2%AB%D0%94%D0%B8%D0%BD%D0%B0%D0%BC%D0%BE%C2%BB_-_%C2%AB%D0%91%D0%B0%D1%80%D1%81%D0%B5%D0%BB%D0%BE%D0%BD%D0%B0%C2%BB_0-4._24_%D0%BD%D0%BE%D1%8F%D0%B1%D1%80%D1%8F_2020_%D0%B3%D0%BE%D0%B4%D0%B0_%E2%80%94_1166990_%28Ronald_Koeman%29.jpg) | `ned-coach-feature.jpg` | 2020-11-24 | Olga Shcherbytska | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Virgil Van Dijk](https://commons.wikimedia.org/wiki/File:20160604_AUT_NED_8876_%28cropped%29.jpg) | `ned-player-4-feature.jpg` | 2016-06-04 | Ailura | [CC BY-SA 3.0 at](https://creativecommons.org/licenses/by-sa/3.0/at/deed.en) |
| [Frenkie De Jong](https://commons.wikimedia.org/wiki/File:%D0%9C%D0%B0%D1%82%D1%87_%C2%AB%D0%94%D0%B8%D0%BD%D0%B0%D0%BC%D0%BE%C2%BB_-_%C2%AB%D0%91%D0%B0%D1%80%D1%81%D0%B5%D0%BB%D0%BE%D0%BD%D0%B0%C2%BB_0-1._2_%D0%BD%D0%BE%D1%8F%D0%B1%D1%80%D1%8F_2021_%D0%B3%D0%BE%D0%B4%D0%B0._II_%E2%80%94_1289671_%28cropped%29.jpg) | `ned-player-21-feature.jpg` | 2021-11-02 | Yuliia Perekopaiko | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Memphis Depay](https://commons.wikimedia.org/wiki/File:Memphis_Depay_2019.jpg) | `ned-player-10-feature.jpg` | 2019-10-02 | Derivative work: Joe Sins | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |

日期采用 Commons `DateTimeOriginal` 元数据而非文件名猜测；元数据、下载地址、原图地址、尺寸和 SHA-256 完整记于 `credits.json`。照片年份均在页面标注；旧照不冒充本届摄影。

## 接入方式

`feature.json` 为按 FIFA 代码包裹的单队片段，`credits.json` 为四条照片 key。已按 key 追加到本地正式运行 JSON，便于直接验收；原有队伍键和署名保持不变。基础数据、模板、CSS 和数据库不改。
