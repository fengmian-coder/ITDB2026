/** WorldCup Journey · C 动态统计，由 B 协助实现。依赖本地 ECharts 6.1.0。 */
(() => {
    'use strict';
    const root = document.getElementById('insight-page');
    if (!root) return;
    const config = JSON.parse(document.getElementById('insight-data').textContent);
    const byId = (id) => document.getElementById(id);
    const status = (name, text, error = false) => {
        const element = byId(`${name}-status`);
        element.textContent = text;
        element.classList.toggle('text-danger', error);
        element.classList.toggle('text-body-secondary', !error);
    };
    if (!window.echarts) {
        ['ranking', 'compare', 'stages', 'flow', 'scatter'].forEach((name) => status(name, '图表资源加载失败，请查看数据表。', true));
        byId('insight-refresh').disabled = true;
        return;
    }
    const charts = {};
    ['ranking', 'compare', 'stages', 'flow', 'scatter'].forEach((name) => {
        charts[name] = echarts.init(byId(`${name}-chart`), null, {renderer: 'svg'});
    });
    const state = {ranking: config.ranking, stages: config.stages, comparison: config.comparison,
        flow: config.flow, selectedTeam: ''};
    const controllers = {};
    const label = (team) => `${team.team_name} · ${team.fifa_code}`;
    const hasFinished = (teams) => teams.some((team) => team.matches_played > 0);
    const stageLabel = (stage) => config.stageLabels[stage.stage_code] || stage.stage_code;
    const palette = () => {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        return {text: dark ? '#e4ece7' : '#273e30', line: dark ? '#46534c' : '#e2e8e3',
            colors: dark ? ['#75c99c', '#e9bb65', '#8bb8ef'] : ['#2f7650', '#b17a19', '#4b76ab']};
    };
    const base = () => {
        const theme = palette();
        return {
            color: theme.colors, textStyle: {color: theme.text, fontFamily: 'system-ui, sans-serif'},
            animationDuration: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 650,
            animationDurationUpdate: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 350,
            animationEasing: 'cubicOut',
            aria: {enabled: true},
            tooltip: {trigger: 'axis', renderMode: 'richText', confine: true, axisPointer: {type: 'shadow'}},
            legend: {bottom: 0, textStyle: {color: theme.text}},
            grid: {top: 35, bottom: 55, left: 12, right: 16, containLabel: true},
        };
    };
    const categoryAxis = (data) => ({type: 'category', data,
        axisLabel: {color: palette().text}, axisLine: {lineStyle: {color: palette().line}}});
    const valueAxis = (name) => ({type: 'value', name, minInterval: 1,
        axisLabel: {color: palette().text}, nameTextStyle: {color: palette().text},
        splitLine: {lineStyle: {color: palette().line, type: 'dashed'}}});
    // 表格和提示全部使用 textContent，数据库中的球队名称不会作为 HTML 执行。
    const table = (name, rows, empty, columns) => {
        const body = byId(`${name}-table`);
        body.replaceChildren();
        const values = rows.length ? rows : [[empty]];
        values.forEach((row) => {
            const tr = document.createElement('tr');
            row.forEach((value) => {
                const cell = document.createElement('td');
                cell.textContent = value === null ? '暂无' : String(value);
                if (!rows.length) cell.colSpan = columns;
                tr.append(cell);
            });
            body.append(tr);
        });
    };
    function renderRanking() {
        const limit = byId('ranking-limit').value;
        const rows = limit === 'all' ? state.ranking : state.ranking.slice(0, Number(limit));
        const option = base();
        option.legend = {show: false};
        option.grid.right = 48;
        option.xAxis = valueAxis('进球');
        option.yAxis = categoryAxis(rows.map(label));
        option.yAxis.inverse = true;
        Object.assign(option.yAxis.axisLabel, {width: window.innerWidth < 576 ? 100 : 180, overflow: 'truncate'});
        option.series = [{name: '进球', type: 'bar', data: rows.map((team) => ({
            value: team.goals_for,
            itemStyle: {color: hasFinished(rows) && team.goals_for === rows[0].goals_for ? palette().colors[1] : palette().colors[0]},
        })), itemStyle: {borderRadius: [0, 6, 6, 0]}, showBackground: true,
            backgroundStyle: {color: palette().line + '55', borderRadius: 6},
            label: {show: true, position: 'right', color: palette().text}, barMaxWidth: 26}];
        if (rows.length > 15) {
            option.dataZoom = [{type: 'slider', yAxisIndex: 0, startValue: 0, endValue: 14,
                right: 0, width: 14, zoomLock: true, filterMode: 'empty'}];
            option.grid.right = 64;
        }
        charts.ranking.setOption(option, {notMerge: true});
        table('ranking', state.ranking.map((team) => [label(team), team.matches_played, team.goals_for, team.goals_against]), '暂无参赛球队数据。', 4);
        status('ranking', state.ranking.length === 0 ? '暂无参赛球队数据。'
            : hasFinished(state.ranking) ? `共 ${state.ranking.length} 支球队，按进球数降序排列。` : '暂无完赛记录，当前进球统计均为 0。');
    }
    function renderCompare() {
        const pair = state.comparison;
        const rows = pair ? [pair.left, pair.right] : [];
        ['left', 'right'].forEach((side) => {
            const team = pair?.[side];
            byId(`compare-${side}-code`).textContent = team?.fifa_code || '—';
            byId(`compare-${side}-name`).textContent = team?.team_name || '选择球队';
        });
        const option = base();
        option.tooltip = {trigger: 'item', renderMode: 'richText', confine: true};
        option.legend.data = ['胜', '平', '负'];
        const radius = Math.min(90, charts.compare.getWidth() * 0.21);
        option.series = rows.map((team, index) => ({
            name: label(team), type: 'pie', radius: [radius * 0.68, radius],
            center: [index === 0 ? '25%' : '75%', '44%'],
            label: {show: false}, emphasis: {scaleSize: 4},
            stillShowZeroSum: false, emptyCircleStyle: {color: palette().line},
            data: [['wins', '胜'], ['draws', '平'], ['losses', '负']].map(([key, name]) => ({name, value: team[key]})),
        }));
        option.title = rows.map((team, index) => ({
            text: team.win_rate == null ? '暂无' : `${(team.win_rate * 100).toFixed(1)}%`,
            subtext: '胜率', left: index === 0 ? '25%' : '75%', top: '34%', textAlign: 'center',
            textStyle: {color: palette().text, fontSize: window.innerWidth < 576 ? 18 : 22},
            subtextStyle: {color: palette().text, fontSize: 12},
        }));
        charts.compare.setOption(option, {notMerge: true});
        table('compare', rows.map((team) => [label(team), team.wins, team.draws, team.losses, team.penalty_wins]), '至少需要两支参赛球队。', 5);
        status('compare', !pair ? '至少需要两支参赛球队。' : hasFinished(rows) ? '场上平局计为平局，点球大战获胜单列。' : '所选球队暂无完赛记录。');
        const link = new URL(byId('insight-pk-link').href);
        if (pair) {
            link.searchParams.set('left_id', pair.left.team_id);
            link.searchParams.set('right_id', pair.right.team_id);
        } else {
            link.searchParams.delete('left_id');
            link.searchParams.delete('right_id');
        }
        byId('insight-pk-link').href = link.href;
    }
    function renderStages() {
        const option = base();
        option.xAxis = categoryAxis(state.stages.map(stageLabel));
        option.xAxis.axisLabel.rotate = window.innerWidth < 576 ? 30 : 0;
        option.yAxis = [valueAxis('场次'), valueAxis('进球')];
        option.series = [
            {name: '有效场次', type: 'bar', data: state.stages.map((stage) => stage.match_count), barMaxWidth: 26, itemStyle: {borderRadius: [4, 4, 0, 0]}},
            {name: '已完赛', type: 'bar', data: state.stages.map((stage) => stage.finished_match_count), barMaxWidth: 26, itemStyle: {borderRadius: [4, 4, 0, 0]}},
            // null 表示该阶段尚无完赛数据，保留折线缺口，不能转换为 0。
            {name: '进球', type: 'line', yAxisIndex: 1, connectNulls: false, symbolSize: 9, lineStyle: {width: 3},
                data: state.stages.map((stage) => stage.total_goals)},
        ];
        charts.stages.setOption(option, {notMerge: true});
        table('stages', state.stages.map((stage) => [stageLabel(stage), stage.match_count, stage.finished_match_count, stage.total_goals]), '暂无有效赛程数据。', 4);
        status('stages', !state.stages.length ? '暂无有效赛程数据。'
            : state.stages.every((stage) => stage.total_goals === null) ? '暂无完赛记录，进球数据为“暂无”。' : '取消比赛已排除，进球数不含点球大战。');
    }
    function renderFlow() {
        const flow = state.flow;
        const selected = flow.teams.find((team) => String(team.team_id) === state.selectedTeam);
        const labels = Object.fromEntries(flow.nodes.map((node) => [node.name, node.label]));
        const names = new Map(state.ranking.map((team) => [String(team.team_id), label(team)]));
        const option = base();
        option.tooltip = {trigger: 'item', renderMode: 'richText', confine: true,
            formatter: (item) => item.dataType === 'edge'
                ? `${labels[item.data.source]} → ${labels[item.data.target]}\n${item.data.value} 支球队`
                : labels[item.name]};
        option.legend = {show: false};
        option.aria = {enabled: true, label: {description: `球队晋级流向，共 ${flow.teams.length} 支参赛球队。完整记录见下方数据表。`}};
        option.series = [{type: 'sankey', left: 12, right: 108, top: 20, bottom: 22,
            nodeWidth: 12, nodeGap: 18, draggable: false, layoutIterations: 32,
            label: {color: palette().text, formatter: (item) => labels[item.name], fontSize: 12},
            data: flow.nodes.map((node) => ({name: node.name, depth: node.depth,
                value: flow.teams.filter((team) => team.path.includes(node.name)).length,
                itemStyle: {color: node.name.startsWith('result:') ? palette().colors[1] : palette().colors[0],
                    opacity: !selected || selected.path.includes(node.name) ? 1 : 0.2}})),
            links: flow.links.map((link) => ({...link,
                lineStyle: {color: selected && link.team_ids.some((id) => String(id) === state.selectedTeam)
                    ? palette().colors[1] : palette().colors[0],
                opacity: !selected ? 0.25 : link.team_ids.some((id) => String(id) === state.selectedTeam) ? 0.85 : 0.05}})),
            emphasis: {focus: 'adjacency'}, lineStyle: {curveness: 0.5},
        }];
        charts.flow.setOption(option, {notMerge: true});
        byId('flow-route').textContent = selected
            ? `${names.get(state.selectedTeam)} · ${selected.path.map((node) => labels[node]).join(' → ')}`
            : '选择球队查看路线，点击攻防坐标中的球队也可联动追踪。';
        table('flow', flow.teams.map((team) => [names.get(String(team.team_id)) || team.team_id,
            team.path.map((node) => labels[node]).join(' → ')]), '暂无参赛球队数据。', 2);
        status('flow', flow.teams.length ? `${flow.teams.length} 支参赛队 · 阶段只读取已完赛记录，最终成绩读取参赛资料。` : '暂无参赛球队数据。');
    }
    function renderScatter() {
        const teams = state.ranking.filter((team) => team.goals_per_match !== null && team.goals_against_per_match !== null);
        const selected = state.ranking.find((team) => String(team.team_id) === state.selectedTeam);
        const option = base();
        option.legend = {show: false};
        option.grid = {top: 42, bottom: 45, left: 15, right: 38, containLabel: true};
        option.xAxis = {...valueAxis('场均进球'), minInterval: 0.5, nameLocation: 'middle', nameGap: 30};
        option.yAxis = {...valueAxis('场均失球'), minInterval: 0.5};
        option.tooltip = {trigger: 'item', renderMode: 'richText', confine: true,
            formatter: (item) => `${item.data.name}\n场均进球 ${item.value[0].toFixed(2)}\n场均失球 ${item.value[1].toFixed(2)}`};
        option.series = [{type: 'scatter', symbolSize: 16,
            data: teams.map((team) => ({name: label(team), team_id: String(team.team_id),
                value: [team.goals_per_match, team.goals_against_per_match],
                symbolSize: String(team.team_id) === state.selectedTeam ? 24 : 16,
                label: {show: String(team.team_id) === state.selectedTeam, formatter: team.fifa_code,
                    position: 'top', color: palette().text},
                itemStyle: {color: String(team.team_id) === state.selectedTeam ? palette().colors[1] : palette().colors[0],
                    opacity: !selected || String(team.team_id) === state.selectedTeam ? 0.85 : 0.35,
                    borderWidth: 2, borderColor: palette().line}}))}];
        charts.scatter.setOption(option, {notMerge: true});
        status('scatter', teams.length ? `${teams.length} 支球队有完赛记录 · 每个点代表一支球队，相同数据可能重合。` : '暂无完赛记录，场均数据暂不可用。');
        byId('scatter-team-name').textContent = selected ? label(selected) : '选择图中的球队';
        byId('scatter-team-stats').textContent = selected
            ? `${selected.matches_played} 场完赛 · ${selected.goals_for} 进球 · ${selected.goals_against} 失球 · 场均进球 ${selected.goals_per_match?.toFixed(2) ?? '暂无'} · 场均失球 ${selected.goals_against_per_match?.toFixed(2) ?? '暂无'}`
            : '也可以使用上方“追踪球队”选择器。';
        const link = byId('scatter-journey-link');
        link.hidden = !selected;
        if (selected) {
            const url = new URL(config.journeyUrl, location.href);
            url.searchParams.set('team_id', selected.team_id);
            link.href = url.href;
        }
    }
    function selectTeam(id) {
        state.selectedTeam = String(id);
        byId('flow-team').value = state.selectedTeam;
        renderFlow();
        renderScatter();
    }
    byId('flow-team').addEventListener('change', (event) => selectTeam(event.target.value));
    charts.scatter.on('click', (event) => selectTeam(event.data.team_id));
    // 每类请求独立取消，快速切换球队不会让旧响应覆盖新选择。
    async function load(name, url, apply) {
        controllers[name]?.abort();
        const controller = new AbortController();
        controllers[name] = controller;
        status(name, '正在刷新…');
        charts[name].showLoading('default', {text: '加载中'});
        try {
            const response = await fetch(url, {signal: controller.signal, headers: {Accept: 'application/json'}});
            const payload = await response.json();
            if (!response.ok || payload.success !== true) throw new Error('request failed');
            if (controllers[name] !== controller) return;
            apply(payload.data);
        } catch (error) {
            if (error.name !== 'AbortError' && controllers[name] === controller) {
                status(name, '刷新失败，显示上次成功数据。请稍后重试。', true);
            }
        } finally {
            if (controllers[name] === controller) charts[name].hideLoading();
        }
    }
    function refreshCompare() {
        const left = byId('insight-left').value;
        const right = byId('insight-right').value;
        if (!left || !right || left === right) {
            controllers.compare?.abort();
            delete controllers.compare;
            charts.compare.hideLoading();
            state.comparison = null;
            renderCompare();
            status('compare', left && right ? '请选择两支不同的球队。' : '至少需要两支参赛球队。', true);
            return Promise.resolve();
        }
        const url = new URL(config.compareUrl, location.href);
        url.searchParams.set('left_id', left);
        url.searchParams.set('right_id', right);
        return load('compare', url, (data) => { state.comparison = data; renderCompare(); });
    }
    byId('ranking-limit').addEventListener('change', renderRanking);
    ['insight-left', 'insight-right'].forEach((id) => byId(id).addEventListener('change', refreshCompare));
    byId('insight-refresh').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        await load('ranking', config.rankingUrl, (data) => {
            state.ranking = data;
            // 参赛队增删后同步三个选择器，避免继续请求已失去参赛资格的旧 ID。
            ['flow-team', 'insight-left', 'insight-right'].forEach((id) => {
                const select = byId(id);
                const previous = select.value;
                select.replaceChildren();
                if (id === 'flow-team') select.add(new Option('全部球队', ''));
                data.forEach((team) => select.add(new Option(label(team), String(team.team_id))));
                select.disabled = id !== 'flow-team' && data.length < 2;
                select.value = previous;
                if (id !== 'flow-team' && !select.value && data.length) {
                    select.selectedIndex = id === 'insight-right' && data.length > 1 ? 1 : 0;
                }
            });
            state.selectedTeam = byId('flow-team').value;
            renderRanking();
            renderScatter();
            renderFlow();
        });
        await Promise.allSettled([
            load('flow', config.flowUrl, (data) => { state.flow = data; renderFlow(); }),
            load('stages', config.stagesUrl, (data) => { state.stages = data; renderStages(); }),
            refreshCompare(),
        ]);
        button.disabled = false;
    });
    renderRanking();
    renderCompare();
    renderStages();
    renderFlow();
    renderScatter();
    const resize = () => {
        Object.values(charts).forEach((chart) => chart.resize());
        renderCompare();
    };
    if (window.ResizeObserver) {
        new ResizeObserver(resize).observe(root);
    } else {
        window.addEventListener('resize', resize);
    }
    new MutationObserver(() => { renderRanking(); renderCompare(); renderStages(); renderFlow(); renderScatter(); })
        .observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
})();
