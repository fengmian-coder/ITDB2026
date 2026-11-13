/** WorldCup Journey · B 旅程地图，使用本地边界与已核对坐标。 */
(() => {
    'use strict';
    const root = document.getElementById('journey-map');
    if (!root) return;
    const config = JSON.parse(document.getElementById('journey-map-data').textContent);
    const {stops, legs} = config.map;
    const status = document.getElementById('journey-map-status');
    const reset = document.getElementById('journey-map-reset');
    const detail = document.getElementById('journey-map-detail');
    const byId = new Map(stops.map(stop => [stop.id, stop]));
    const stageNames = {GROUP: '小组赛', R32: '32 强赛', R16: '16 强赛', QF: '四分之一决赛', SF: '半决赛', THIRD: '季军赛', FINAL: '决赛'};
    const statusNames = {SCHEDULED: '未开始', IN_PROGRESS: '进行中', POSTPONED: '已延期', FINISHED: '已结束'};
    let selected = stops[0]?.id;
    let chart;
    const text = (tag, content, className = '') => {
        const node = document.createElement(tag);
        node.textContent = content;
        node.className = className;
        return node;
    };
    function selectStop(id) {
        selected = id;
        const stop = byId.get(id);
        if (!stop) return;
        document.querySelectorAll('#journey-map-stops [data-stop]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.stop === id)));
        detail.replaceChildren(text('h3', stop.city), text('p', stop.name, 'small text-body-secondary'));
        stop.matches.forEach(match => {
            const link = text('a', '', 'journey-map-fixture');
            const url = new URL(config.matchUrl, location.href);
            url.searchParams.set('id', match.match_id);
            url.searchParams.set('team_id', config.team_id);
            link.href = url.href;
            const date = new Intl.DateTimeFormat('zh-CN', {timeZone: 'Asia/Shanghai', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23'}).format(new Date(match.kickoff_utc));
            link.append(text('span', `${date} 北京时间 · ${stageNames[match.stage_code]}`, 'small text-body-secondary'));
            const score = match.status === 'FINISHED' ? `${match.goals_for} – ${match.goals_against}` : 'VS';
            link.append(text('strong', `${config.team_name} ${score} ${match.opponent.name_zh || '球队 #' + match.opponent.team_id}`));
            if (match.penalties_for !== null) link.append(text('p', `点球 ${match.penalties_for} – ${match.penalties_against}`, 'small mb-1'));
            link.append(text('span', `${statusNames[match.status]} · 查看比赛 →`, 'small text-body-secondary'));
            detail.append(link);
        });
        if (chart) render(false);
    }
    function render(resetView) {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        const foreground = dark ? '#dceadd' : '#274333';
        const green = dark ? '#87cca2' : '#2e7550';
        const gold = dark ? '#f2cc79' : '#b3832e';
        const option = {
            animation: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            aria: {enabled: true, label: {description: `${config.team_name}的比赛旅程，共 ${stops.length} 个场馆，可用下方场馆按钮选择。`}},
            tooltip: {trigger: 'item', renderMode: 'richText', confine: true,
                formatter: item => item.data?.stopId ? `${byId.get(item.data.stopId).city}\n${byId.get(item.data.stopId).name}\n${byId.get(item.data.stopId).matches.length} 场比赛` : ''},
            geo: {map: 'wcj-hosts', roam: true, scaleLimit: {min: 0.8, max: 5},
                boundingCoords: [[-130, 55], [-60, 15]], left: 12, right: 12, top: 12, bottom: 12,
                itemStyle: {areaColor: dark ? '#2b4838' : '#d5e3d4', borderColor: dark ? '#718b75' : '#a6bca3', borderWidth: 1},
                emphasis: {disabled: true}, silent: true},
            series: [
                {type: 'lines', coordinateSystem: 'geo', silent: true, symbol: ['none', 'arrow'], symbolSize: 7,
                    lineStyle: {color: green, width: 2, curveness: 0.15, opacity: 0.7},
                    data: legs.map(leg => ({coords: [byId.get(leg.from).coordinate, byId.get(leg.to).coordinate],
                        lineStyle: {type: leg.completed ? 'solid' : 'dashed', opacity: leg.completed ? 0.7 : 0.4}}))},
                {type: 'scatter', coordinateSystem: 'geo', z: 5,
                    label: {show: true, position: 'right', color: foreground, fontSize: 11, formatter: '{b}'},
                    labelLayout: {hideOverlap: true},
                    data: stops.map((stop, index) => ({name: `${index + 1} ${stop.city}`, stopId: stop.id, value: stop.coordinate,
                        symbol: stop.matches.some(match => match.status === 'FINISHED') ? 'circle' : 'emptyCircle',
                        symbolSize: stop.id === selected ? 19 : 12,
                        itemStyle: {color: stop.id === selected ? gold : green, opacity: 1, borderColor: dark ? '#13251b' : '#fff', borderWidth: 2}}))},
            ],
        };
        // 普通场馆切换只更新样式，保留用户拖动和缩放后的视野。
        if (!resetView) {
            const geo = chart.getOption().geo?.[0];
            if (geo) Object.assign(option.geo, {center: geo.center, zoom: geo.zoom});
        }
        chart.setOption(option, {notMerge: true});
    }
    document.getElementById('journey-map-stops').addEventListener('click', event => {
        const button = event.target.closest('[data-stop]');
        if (button) selectStop(button.dataset.stop);
    });
    if (!stops.length) {
        status.textContent = '暂无可定位的比赛场馆，下方时间线可查看已有比赛。';
        detail.replaceChildren(text('p', '暂无场馆记录。'));
        reset.disabled = true;
        return;
    }
    selectStop(selected);
    (async () => {
        try {
            if (!window.echarts) throw new Error('Map renderer unavailable');
            const response = await fetch(config.geoUrl);
            if (!response.ok) throw new Error('Map unavailable');
            echarts.registerMap('wcj-hosts', await response.json());
            chart = echarts.init(root, null, {renderer: 'svg'});
            render(true);
            chart.on('click', event => { if (event.data?.stopId) selectStop(event.data.stopId); });
            reset.addEventListener('click', () => render(true));
            new ResizeObserver(() => chart.resize()).observe(root);
            new MutationObserver(() => render(false)).observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
            status.textContent = `${stops.length} 个比赛场馆 · 可拖动、缩放地图，或使用场馆按钮选择。`;
        } catch (error) {
            status.textContent = '地图暂时无法加载，可继续使用场馆按钮和下方时间线查看比赛。';
            reset.disabled = true;
        }
    })();
})();
