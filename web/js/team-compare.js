/* WorldCup Journey | A/C | Team comparison chart, uses existing service statistics | 2026-11-12 */
(() => {
    'use strict';
    const element = document.getElementById('compare-chart');
    if (!element) return;
    const status = document.getElementById('compare-chart-status');
    if (!window.echarts) {
        status.textContent = '图表暂时无法加载，可以继续查看下方对比数据';
        return;
    }
    const pair = JSON.parse(document.getElementById('team-compare-data').textContent);
    const chart = echarts.init(element, null, {renderer: 'svg'});
    const rows = [pair.left, pair.right];
    function render() {
        const dark = document.documentElement.dataset.bsTheme === 'dark';
        const text = dark ? '#e4ece7' : '#273e30';
        const line = dark ? '#46534c' : '#e2e8e3';
        const radius = Math.min(92, chart.getWidth() * 0.2);
        chart.setOption({
            color: dark ? ['#75c99c', '#e9bb65', '#8bb8ef'] : ['#2f7650', '#b17a19', '#4b76ab'],
            animationDuration: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 500,
            textStyle: {color: text, fontFamily: 'system-ui, sans-serif'},
            aria: {enabled: true},
            tooltip: {trigger: 'item', renderMode: 'richText', confine: true},
            legend: {bottom: 0, data: ['胜', '平', '负'], textStyle: {color: text}},
            title: rows.map((team, index) => ({
                text: team.win_rate == null ? '暂无' : `${(team.win_rate * 100).toFixed(1)}%`,
                subtext: '胜率', left: index ? '75%' : '25%', top: '35%', textAlign: 'center',
                textStyle: {color: text, fontSize: 22}, subtextStyle: {color: text},
            })),
            series: rows.map((team, index) => ({
                name: `${team.team_name} · ${team.fifa_code}`, type: 'pie',
                radius: [radius * 0.68, radius], center: [index ? '75%' : '25%', '45%'],
                label: {show: false}, stillShowZeroSum: false, emptyCircleStyle: {color: line},
                data: [['wins', '胜'], ['draws', '平'], ['losses', '负']].map(([key, name]) => ({name, value: team[key]})),
            })),
        }, {notMerge: true});
    }
    render();
    status.textContent = '场上平局计为平局，点球大战获胜在下方单列';
    const resize = () => { chart.resize(); render(); };
    if (window.ResizeObserver) new ResizeObserver(resize).observe(element);
    else window.addEventListener('resize', resize);
    new MutationObserver(render).observe(document.documentElement, {attributes: true, attributeFilter: ['data-bs-theme']});
})();
