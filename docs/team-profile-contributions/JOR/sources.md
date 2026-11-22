# JOR 约旦：资料来源与核对口径

核对日期：2026-11-21。名单是项目固定版本资料，不是实时阵容；历史照片不用于判断本届球衣、号码或俱乐部。

## 名单与身份

- [FIFA 官方名单](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf#page=26)，2026-07-19 22:34 UTC · Version 1。
- PDF SHA-256：`02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。文件实测哈希与规范一致。
- 逐行提取号码、位置、姓名、俱乐部、国家队累计出场及进球；号码 1–26 各出现一次。原文的拼写和俱乐部保留，PLAYER NAME 栏仅调整英文大小写及姓名顺序。
- 基础档案、教练身份和三位代表球员顺序不改。代表球员姓名与基础档案逐字对齐，号码、位置、俱乐部及累计数据另行比对。
- 阵容位置计数：GK 3、DF 9、MF 8、FW 6。

## 中文显示名称

- [中文名单固定修订版](https://zh.wikipedia.org/w/index.php?oldid=95049982)，仅参考中文名称，不采用其球队名单、统计或俱乐部覆盖 FIFA PDF。
- 显示名称使用简体常见译法；地区译法差异统一为本项目用语（例如罗伯逊、麦克托米奈）。俱乐部优先复用当前已增强球队的同名译法，新增俱乐部手工映射。
- 原文姓名与俱乐部始终并列显示，中文为辅助标签；少见音译不表示得到足协官方中文命名认可。
- **待语言复核**：#18 Mohammad Abughoush 暂译“穆罕默德·阿布·古什”。中文参考名单该号码写为另一人物（穆罕默德·塔哈），因此不采纳，身份与数据严格采用固定 FIFA PDF。

## 概况与教练经历

- [FIFA 球队历史](https://www.fifa.com/en/articles/jordan-team-profile-history)。
- [FIFA 塞拉米世界杯经历与约旦首次参赛](https://www.fifa.com/en/articles/jordan-coach-jamal-sellami-interview-messi)。

三项概况：首次参赛：2026 年世界杯；上届成绩：2022 年未参赛；亚洲杯最佳：2023 届亚军。

教练经历：2024 年 6 月接手约旦，率队首次获得世界杯决赛圈资格。球员时期曾代表摩洛哥参加 1998 年世界杯。

## 四张真实照片

均取自 Commons 文件页，保存来源提供的 JPEG 原图或标准预览文件，不重新压缩、不修图、不生成肖像。页面裁切使用已有公共模板；教练采用已有 portrait 显示方式。

| 人物 | 文件 | 拍摄日期 | 作者 | 许可 |
| --- | --- | --- | --- | --- |
| [Jamal Sellami](https://commons.wikimedia.org/wiki/File:WAC_vs._FUS_%2822.08.12%29_-_1.jpg) | `jor-coach-feature.jpg` | 2012-08-22 | mustapha ennaimi | [CC BY 2.0](https://creativecommons.org/licenses/by/2.0) |
| [Ehsan Haddad](https://commons.wikimedia.org/wiki/File:Ihsan_Haddad.jpg) | `jor-player-23-feature.jpg` | 2024-02-06 | M.Sadegh Nikgostar | [CC BY 4.0](https://creativecommons.org/licenses/by/4.0) |
| [Rajaei Ayed](https://commons.wikimedia.org/wiki/File:Sepahan_and_Al-Hussein_Jordan_football_teams_meet_2_%282025%29_esfahanzibaonline.ir_%28cropped2%29.jpg) | `jor-player-14-feature.jpg` | 2025-11-25 | محمد کاظم کمالی | [CC BY 4.0](https://creativecommons.org/licenses/by/4.0) |
| [Mousa Altamari](https://commons.wikimedia.org/wiki/File:Musa_Al-Taamari_2023.jpg) | `jor-player-10-feature.jpg` | 2023-08-11 | MHSC TV - Montpellier Hérault Sport Club | [CC BY 3.0](https://creativecommons.org/licenses/by/3.0) |

日期采用 Commons `DateTimeOriginal` 元数据而非文件名猜测；元数据、下载地址、原图地址、尺寸和 SHA-256 完整记于 `credits.json`。照片年份均在页面标注；旧照不冒充本届摄影。

## 接入方式

`feature.json` 为按 FIFA 代码包裹的单队片段，`credits.json` 为四条照片 key。已按 key 追加到本地正式运行 JSON，便于直接验收；原有队伍键和署名保持不变。基础数据、模板、CSS 和数据库不改。
