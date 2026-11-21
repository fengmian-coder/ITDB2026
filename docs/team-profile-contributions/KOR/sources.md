# KOR 韩国：资料来源与核对口径

核对日期：2026-11-21。名单是项目固定版本资料，不是实时阵容；历史照片不用于判断本届球衣、号码或俱乐部。

## 名单与身份

- [FIFA 官方名单](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf#page=27)，2026-07-19 22:34 UTC · Version 1。
- PDF SHA-256：`02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。文件实测哈希与规范一致。
- 逐行提取号码、位置、姓名、俱乐部、国家队累计出场及进球；号码 1–26 各出现一次。原文的拼写和俱乐部保留，PLAYER NAME 栏仅调整英文大小写及姓名顺序。
- 基础档案、教练身份和三位代表球员顺序不改。代表球员姓名与基础档案逐字对齐，号码、位置、俱乐部及累计数据另行比对。
- 阵容位置计数：GK 3、DF 9、MF 11、FW 3。

## 中文显示名称

- [中文名单固定修订版](https://zh.wikipedia.org/w/index.php?oldid=95049982)，仅参考中文名称，不采用其球队名单、统计或俱乐部覆盖 FIFA PDF。
- 显示名称使用简体常见译法；地区译法差异统一为本项目用语（例如罗伯逊、麦克托米奈）。俱乐部优先复用当前已增强球队的同名译法，新增俱乐部手工映射。
- 原文姓名与俱乐部始终并列显示，中文为辅助标签；少见音译不表示得到足协官方中文命名认可。
- 韩国姓名参考固定中文名单核对汉字：李韩汎、李期奕、金泰贤、李太锡、裴峻浩、严志成；避免仅凭韩文发音猜写汉字。

## 概况与教练经历

- [FIFA 球队历史](https://www.fifa.com/es/tournaments/mens/worldcup/canadamexicousa2026/articles/republica-de-corea-perfil-seleccion-historia)。
- [韩国足协 洪明甫2024年任命与球员经历](https://www.kfa.or.kr/layer_popup/popup_live.php?act=news_tv_detail&check_url=bGF5ZXI%3D&div_code=news&idx=26402&lang=EN)。

三项概况：世界杯最佳：2002 年第四名；上届成绩：2022 年十六强；首次参赛：1954 年瑞士世界杯。

教练经历：球员时期担任韩国队长并帮助球队取得 2002 年世界杯第四名。教练生涯曾带队参加 2014 年世界杯，2024 年再次执教国家队。

## 四张真实照片

均取自 Commons 文件页，保存来源提供的 JPEG 原图或标准预览文件，不重新压缩、不修图、不生成肖像。页面裁切使用已有公共模板；教练采用已有 portrait 显示方式。

| 人物 | 文件 | 拍摄日期 | 作者 | 许可 |
| --- | --- | --- | --- | --- |
| [Myungbo Hong](https://commons.wikimedia.org/wiki/File:Hong_Myung-Bo.jpg) | `kor-coach-feature.jpg` | 2013-01-04 | Republic of Korea | [CC BY-SA 2.0](https://creativecommons.org/licenses/by-sa/2.0) |
| [Minjae Kim](https://commons.wikimedia.org/wiki/File:FC_Red_Bull_Salzburg_gegen_Bayern_M%C3%BCnchen_%282025-01-06_Testspiel%29_26.jpg) | `kor-player-4-feature.jpg` | 2025-01-06 | Werner100359 | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Jaesung Lee](https://commons.wikimedia.org/wiki/File:Asian_Nations_Cup_-_Jordan_and_South_Korea_%2874%29_%28cropped%29_2.jpg) | `kor-player-10-feature.jpg` | 2024-02-06 | M.Sadegh Nikgostar | [CC BY 4.0](https://creativecommons.org/licenses/by/4.0) |
| [Heungmin Son](https://commons.wikimedia.org/wiki/File:BFA_2023_-2_Heung-Min_Son_%28cropped%29.jpg) | `kor-player-7-feature.jpg` | 2024-05-16 | Ujishadow | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |

日期采用 Commons `DateTimeOriginal` 元数据而非文件名猜测；元数据、下载地址、原图地址、尺寸和 SHA-256 完整记于 `credits.json`。照片年份均在页面标注；旧照不冒充本届摄影。

## 接入方式

`feature.json` 为按 FIFA 代码包裹的单队片段，`credits.json` 为四条照片 key。已按 key 追加到本地正式运行 JSON，便于直接验收；原有队伍键和署名保持不变。基础数据、模板、CSS 和数据库不改。

注意：孙兴慜照片文件名含 BFA2023，来源拍摄日期为 2024-05-16，因此页面标 2024，不使用文件名中的 2023。
