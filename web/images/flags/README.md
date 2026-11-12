# 球队国旗素材

来源：[lipis/flag-icons](https://github.com/lipis/flag-icons/tree/v7.3.2/flags/4x3)，固定版本 7.3.2，npm 原始包 https://registry.npmjs.org/flag-icons/-/flag-icons-7.3.2.tgz 。按 MIT 许可使用，原始许可保存在本目录 LICENSE。

本目录只包含本届 48 支参赛球队对应的 SVG，文件名采用球队 FIFA 代码。`manifest.json` 记录原项目路径与每个文件的 SHA-256，原始 SVG 未修改。

FIFA 与素材的地区代码不是同一套编码，完整映射见 manifest。特别地：ENG → gb-eng、SCO → gb-sct、COD → cd、CUW → cw、CIV → ci、CPV → cv。

显示入口为 `components/TeamFlag.php`。后台已有且可用的站内自定义图片优先；未填写或图片不存在时按 FIFA 代码回退到本目录。无需更新数据库 flag_path，其他组员同步代码后即可显示。本地文件不依赖外部 CDN 或 emoji 字体。
