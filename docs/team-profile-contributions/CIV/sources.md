# CIV 科特迪瓦：资料来源与核对口径

核对日期：2026-11-21。名单是项目固定版本资料，不是实时阵容；历史照片不用于判断本届球衣、号码或俱乐部。

## 名单与身份

- [FIFA 官方名单](https://fdp.fifa.org/assetspublic/ce281/pdf/SquadLists-English.pdf#page=12)，2026-07-19 22:34 UTC · Version 1。
- PDF SHA-256：`02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94`。文件实测哈希与规范一致。
- 逐行提取号码、位置、姓名、俱乐部、国家队累计出场及进球；号码 1–26 各出现一次。原文的拼写和俱乐部保留，PLAYER NAME 栏仅调整英文大小写及姓名顺序。
- 基础档案、教练身份和三位代表球员顺序不改。代表球员姓名与基础档案逐字对齐，号码、位置、俱乐部及累计数据另行比对。
- 阵容位置计数：GK 3、DF 8、MF 6、FW 9。

## 中文显示名称

- [中文名单固定修订版](https://zh.wikipedia.org/w/index.php?oldid=95049982)，仅参考中文名称，不采用其球队名单、统计或俱乐部覆盖 FIFA PDF。
- 显示名称使用简体常见译法；地区译法差异统一为本项目用语（例如罗伯逊、麦克托米奈）。俱乐部优先复用当前已增强球队的同名译法，新增俱乐部手工映射。
- 原文姓名与俱乐部始终并列显示，中文为辅助标签；少见音译不表示得到足协官方中文命名认可。

## 概况与教练经历

- [FIFA 球队历史](https://www.fifa.com/en/articles/cote-divoire-qualify)。
- [CAF 法埃执教经历与非洲杯冠军](https://www.cafonline.com/fr/coupe-dafrique-des-nations/infos/a-la-rencontre-du-selectionneur-emerse-fae-cote-d-ivoire/)。

三项概况：首次参赛：2006 年德国世界杯；上届成绩：2022 年未参赛；洲际荣誉：2023 年非洲杯冠军。

教练经历：2024 年非洲杯期间接手球队，带领科特迪瓦夺得该届冠军，并率队获得 2026 年世界杯决赛圈资格。

## 四张真实照片

均取自 Commons 文件页，保存来源提供的 JPEG 原图或标准预览文件，不重新压缩、不修图、不生成肖像。页面裁切使用已有公共模板；教练采用已有 portrait 显示方式。

| 人物 | 文件 | 拍摄日期 | 作者 | 许可 |
| --- | --- | --- | --- | --- |
| [Émerse Faé](https://commons.wikimedia.org/wiki/File:Emerse_Fa%C3%A9.jpg) | `civ-coach-feature.jpg` | 2024-02-11 | SonoGrazy | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Ghislain Konan](https://commons.wikimedia.org/wiki/File:Ghislain_Konan_Cote_D%27Ivoire_v_Ecuador_14_June_2026-53.jpg) | `civ-player-3-feature.jpg` | 2026-06-14 | Bryan Berlin | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Franck Kessie](https://commons.wikimedia.org/wiki/File:Franck_Kessie_Cote_D%27Ivoire_v_Ecuador_14_June_2026-15_%28cropped%29.jpg) | `civ-player-8-feature.jpg` | 2026-06-14 | Bryan Berlin | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |
| [Nicolas Pepe](https://commons.wikimedia.org/wiki/File:Nicolas_Pepe_Cote_D%27Ivoire_v_Ecuador_14_June_2026-30.jpg) | `civ-player-19-feature.jpg` | 2026-06-14 | Bryan Berlin | [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0) |

日期采用 Commons `DateTimeOriginal` 元数据而非文件名猜测；元数据、下载地址、原图地址、尺寸和 SHA-256 完整记于 `credits.json`。照片年份均在页面标注；旧照不冒充本届摄影。

## 接入方式

`feature.json` 为按 FIFA 代码包裹的单队片段，`credits.json` 为四条照片 key。已按 key 追加到本地正式运行 JSON，便于直接验收；原有队伍键和署名保持不变。基础数据、模板、CSS 和数据库不改。
