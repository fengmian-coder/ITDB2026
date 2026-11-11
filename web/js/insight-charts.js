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
        ['ranking', 'compare', 'stages'].forEach((name) => status(name, '图表资源加载失败，请查看下方数据表。', true));
        byId('insight-refresh').disabled = true;
        return;
    }
    const charts = {};
    ['ranking', 'compare', 'stages'].forEach((name) => {
        charts[name] = echarts.init(byId(`${name}-chart`), null, {renderer: 'svg'});
    });
    const state = {ranking: config.ranking, stages: config.stages, comparison: config.comparison};
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
            byId(`compare-${side}-rate`).textContent = team?.win_rate == null ? '暂无' : `${(team.win_rate * 100).toFixed(1)}%`;
        });
        const option = base();
        option.xAxis = categoryAxis(rows.map(label));
        Object.assign(option.xAxis.axisLabel, {width: 140, overflow: 'truncate'});
        option.yAxis = valueAxis('场次');
        option.series = [['wins', '胜'], ['draws', '平'], ['losses', '负']].map(([key, name]) => ({
            name, type: 'bar', data: rows.map((team) => team[key]), barMaxWidth: 30,
            itemStyle: {borderRadius: [5, 5, 0, 0]},
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
        await Promise.allSettled([
            load('ranking', config.rankingUrl, (data) => { state.ranking = data; renderRanking(); }),
            load('stages', config.stagesUrl, (data) => { state.stages = data; renderStages(); }),
            refreshCompare(),
        ]);
        button.disabled = false;
    });
    renderRanking();
    renderCompare();
    renderStages();
    const resize = () => Object.values(charts).forEach((chart) => chart.resize());
    if (window.ResizeObserver) {
        new ResizeObserver(resize).observe(root);
    } else {
        window.addEventListener('resize', resize);
    }
    new MutationObserver(() => { renderRanking(); renderCompare(); renderStages(); })
        .observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
})();
