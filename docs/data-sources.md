# 最终成绩资料核对记录

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
