# 数据来源与核对记录

本文件是项目数据源的总览；逐条 URL、访问日期、核验备注和复核人保存在 `data/import/sources.csv`，CSV 格式与字段映射细节见 `data/import/README.md`。

## 1. 数据源总览

| 数据集 | 主来源 | 版本／日期 | 校验信息 | 字段映射／说明 | 整理与复核 |
|---|---|---|---|---|---|
| 48 队、16 场馆、104 场比赛、104 条事件、8 篇故事、16 个标签、27 条故事标签关联 | B 模块工作簿 `2026世界杯_B模块真实赛事数据采集模板_8场精选加48队基础资料版_复核修正版.xlsx` | 2026-11-05 初版；2026-11-11 同步故事与最终成绩 | SHA-256 `733f8bb3d2c7511d833e17e38d0535bd34cd860cfadf154c798b9019e91004ea` | `data/import/README.md`；`sources.csv` SRC-001—SRC-058 | B 整理；C 负责导入映射；D 按 V1.1 的独立复核仍待完成 |
| 48 队最终成绩类别 | FIFA CompetitionSummary-English.pdf 第 7 页，FIFA 最终成绩榜交叉核对 | 2026-07-19 22:04 UTC，Version 34 | 官方页面与 PDF 双向核对 | `teams.csv.final_result_code`；见本文件第 2 节 | B 协助 C 核对，2026-11-11 |
| 48 队基础档案（教练、三位代表球员） | FIFA SquadLists-English.pdf | 2026-07-19 22:34 UTC，Version 1，48 页 | SHA-256 `02a0a9beb5d219e7cc0aedde19273e9a3bfe4818ecd600d37804b8801dd96d94` | `data/team-profiles-2026.json`；结构见 `docs/team-profiles-2026.md` | A 整理；C 复核字段增量与 C 的 12 队片段 |
| C 的 12 队完整名单与增强资料 | 同一 FIFA SquadLists PDF 的 CZE／MAR／NZL／JPN／BEL／KSA／FRA／BIH／HAI／AUS／SWE／SUI 页 | 同上 | 同上 | `docs/team-profile-contributions/{CODE}/feature.json`；每队 `sources.md` | C 提取；译名对照中文维基百科 2026 参赛球员名单，并用百度百科／懂球帝抽查 |
| 个人作业文件 | 各成员原始压缩包 | 2026-11 | 每个文件的 SHA-256 记录在 `data/*-documents-manifest.json` | `data/personal/`；通过 `project/register-bundle` 登记 | 各成员提供；A／C 按清单登记，D 检查是否齐全 |

## 2. 最终成绩资料核对记录

本次由 B 协助 C 核对，日期为 2026-11-11。对应 `data/import/sources.csv` 的 SRC-058，写入现有 `teams.csv` 的 `final_result_code` 列。

主要依据为 FIFA 官方赛事总结第 7 页的最终成绩榜与阶段信息，版本为 2026-07-19 22:04 UTC、Version 34。官方网页的最终成绩榜用于交叉核对。项目字段采用现有八种成绩类别。[FIFA 官方赛事总结](https://fdp.fifa.org/assetspublic/ce281/pdf/CompetitionSummary-English.pdf)，[FIFA 最终成绩榜](https://www.fifa.com/en/articles/final-tournament-standings)

| 成绩类别 | FIFA 代码 | 数量 |
| --- | --- | --- |
| CHAMPION | ESP | 1 |
| RUNNER_UP | ARG | 1 |
| THIRD | ENG | 1 |
| FOURTH | FRA | 1 |
| QF | NOR、BEL、MAR、SUI | 4 |
| R16 | MEX、COL、BRA、USA、POR、CAN、EGY、PAR | 8 |
| R32 | NED、GER、CIV、CRO、JPN、AUS、COD、GHA、ECU、RSA、SWE、AUT、BIH、ALG、SEN、CPV | 16 |
| GROUP | IRN、KOR、TUR、SCO、URU、KSA、CZE、NZL、QAT、CUW、PAN、JOR、HAI、UZB、TUN、IRQ | 16 |

映射覆盖 48 支球队。球队仍以已有 FIFA 代码解析为本届参赛关系。各类别取官方最终成绩及淘汰阶段，数据表沿用现有列。官方并列名次均归入对应阶段类别，例如摩洛哥与瑞士均为 QF，厄瓜多尔与南非均为 R32。

新库导入时写入这些类别。旧库仅补齐 NULL 成绩，已有相同值跳过，已有不同非空值会报告冲突并回滚整批业务导入。试导入同样回滚成绩更新。空源值继续保留数据库已有成绩。

D 按 V1.1 执行的独立复核继续单独记录。

## 3. 维护约定

- 新增或替换源文件时，先更新 `data/import/sources.csv` 或对应 `data/*-documents-manifest.json`，再更新本文件；字段映射变化同步 `data/import/README.md` 和数据字典。
- `sources.csv` 的 `reviewer` 为空表示尚未独立复核，不冒填；D 的独立复核单独记录。
- 本文件是来源索引，不替代逐条来源审计，也不表示所有图片或外部素材已取得版权许可。
