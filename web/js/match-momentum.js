/** WorldCup Journey · B 精选比赛比分走势，全部动态文本按纯文本展示。 */
(() => {
    'use strict';
    const root = document.getElementById('match-momentum-chart');
    if (!root) return;
    const {points} = JSON.parse(document.getElementById('match-momentum-data').textContent);
    const slider = document.getElementById('momentum-position');
    const detail = document.getElementById('momentum-detail');
    const nodes = document.getElementById('momentum-nodes');
    let chart;
    let selected = 0;
    function select(index) {
        selected = index;
        slider.value = index;
        const point = points[index];
        slider.setAttribute('aria-valuetext', `${point.label}，${point.home}比${point.away}`);
        detail.replaceChildren();
        const title = document.createElement('strong');
        title.textContent = `${point.label} · ${point.home}–${point.away} · ${point.change}`;
        const description = document.createElement('p');
        description.className = 'small mb-0 mt-2';
        description.textContent = [point.player, point.description].filter(Boolean).join(' · ');
        detail.append(title, description);
        nodes.querySelectorAll('button').forEach(button => {
            const active = Number(button.dataset.point) === index;
            button.classList.toggle('btn-success', active);
            button.classList.toggle('btn-outline-secondary', !active);
            button.setAttribute('aria-pressed', String(active));
        });
        if (chart) render();
    }
    function render() {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        const color = dark ? '#e1eadf' : '#294a37';
        chart.setOption({animation: !matchMedia('(prefers-reduced-motion: reduce)').matches,
            grid: {left: 20, right: 20, top: 40, bottom: 45, containLabel: true},
            tooltip: {trigger: 'axis', renderMode: 'richText', confine: true,
                formatter: items => {
                    const point = points[items[0].dataIndex];
                    return `${point.label}\n${point.home}–${point.away}\n${point.player}`;
                }},
            xAxis: {type: 'category', data: points.map(point => point.label), boundaryGap: false,
                axisLabel: {color, hideOverlap: true}},
            yAxis: {type: 'value', name: '主队净胜球', minInterval: 1, min: Math.min(-1, ...points.map(p => p.home - p.away)), max: Math.max(1, ...points.map(p => p.home - p.away)),
                nameTextStyle: {color}, axisLabel: {color}, splitLine: {lineStyle: {color: dark ? '#46534c' : '#e1e8e1'}}},
            series: [{type: 'line', step: 'end', symbolSize: 9, lineStyle: {color: dark ? '#87cca2' : '#2e7550', width: 3},
                data: points.map((p, i) => ({value: p.home - p.away, symbolSize: i === selected ? 15 : 9, itemStyle: {color: i === selected ? '#b3832e' : '#2e7550'}})),
                markLine: {symbol: 'none', label: {show: false}, data: [{yAxis: 0}], lineStyle: {color: '#b3832e', type: 'dashed'}}}],
            aria: {enabled: true, label: {description: '比分走势，纵轴为主队净胜球，完整比分可通过下方关键时刻按钮查看。'}},
        }, {notMerge: true});
    }
    slider.addEventListener('input', () => select(Number(slider.value)));
    nodes.addEventListener('click', event => {
        const button = event.target.closest('[data-point]');
        if (button) select(Number(button.dataset.point));
    });
    if (window.echarts) {
        chart = echarts.init(root, null, {renderer: 'svg'});
        chart.on('click', event => { if (Number.isInteger(event.dataIndex)) select(event.dataIndex); });
        new ResizeObserver(() => chart.resize()).observe(root);
        new MutationObserver(render).observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
    } else root.textContent = '图表暂不可用，可使用下方按钮回看比分。';
    select(0);
})();
