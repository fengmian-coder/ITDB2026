/** WorldCup Journey · B/C 比赛日历，日期均由服务器转换为北京时间。 */
(() => {
    'use strict';
    const root = document.getElementById('match-calendar');
    if (!root) return;
    const config = JSON.parse(document.getElementById('insight-data').textContent);
    let days = config.calendar.days;
    let selected = '';
    let controller;
    const status = document.getElementById('calendar-status');
    const element = (tag, text, className = '') => {
        const node = document.createElement(tag);
        node.textContent = text;
        node.className = className;
        return node;
    };
    function selectDay(date) {
        selected = date;
        root.querySelectorAll('[data-date]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.date === date)));
        const panel = document.getElementById('calendar-details');
        panel.replaceChildren();
        const day = days.find(item => item.date === date);
        if (!day) {
            panel.append(element('p', '选择一个日期查看当天比赛。', 'text-body-secondary mb-0'));
            return;
        }
        panel.append(element('h3', date + ' · 北京时间', 'h5'));
        panel.append(element('p', `${day.match_count} 场比赛 · 已完赛 ${day.finished_match_count} 场 · 已完赛进球 ${day.total_goals ?? '暂无'}`, 'small text-body-secondary'));
        if (!day.matches.length) panel.append(element('p', '这一天没有比赛，休息一下，期待下一场。', 'mb-0'));
        const labels = {SCHEDULED: '未开始', IN_PROGRESS: '进行中', FINISHED: '已结束', POSTPONED: '已延期'};
        day.matches.forEach(match => {
            const link = element('a', '', 'calendar-match');
            const url = new URL(config.matchUrl, location.href);
            url.searchParams.set('id', match.id);
            link.href = url.href;
            link.append(element('span', `${match.time} · ${config.stageLabels[match.stage] || match.stage}`, 'small text-body-secondary'));
            const score = match.status === 'FINISHED' ? `${match.score.home} – ${match.score.away}` : 'VS';
            link.append(element('strong', `${match.home}  ${score}  ${match.away}`));
            link.append(element('span', labels[match.status] || match.status, 'small text-body-secondary'));
            panel.append(link);
        });
    }
    function render() {
        root.replaceChildren();
        const months = new Map();
        days.forEach(day => {
            const month = day.date.slice(0, 7);
            if (!months.has(month)) months.set(month, []);
            months.get(month).push(day);
        });
        months.forEach((entries, month) => {
            const block = element('div', '', 'calendar-month');
            block.append(element('h3', month, 'h6'));
            const body = element('div', '', 'calendar-month-body');
            const weekdays = element('div', '', 'calendar-weekdays');
            weekdays.setAttribute('aria-hidden', 'true');
            ['一', '二', '三', '四', '五', '六', '日'].forEach(day => weekdays.append(element('span', day)));
            const cells = element('div', '', 'calendar-cells');
            // UTC 只用于计算公历星期，避免浏览器本地时区改变已归日的日期。
            const offset = (new Date(entries[0].date + 'T00:00:00Z').getUTCDay() + 6) % 7;
            for (let i = 0; i < offset; i++) cells.append(element('span', '', 'calendar-padding'));
            entries.forEach(day => {
                const button = element('button', String(Number(day.date.slice(8))), `calendar-day calendar-level-${Math.min(4, day.match_count)}`);
                button.type = 'button';
                button.dataset.date = day.date;
                button.title = `${day.date}，${day.match_count} 场比赛，已完赛 ${day.finished_match_count} 场，进球 ${day.total_goals ?? '暂无'}`;
                button.setAttribute('aria-label', button.title);
                cells.append(button);
            });
            body.append(weekdays, cells);
            block.append(body);
            root.append(block);
        });
        if (!days.length) root.append(element('p', '暂无有效赛程。'));
        status.textContent = `共 ${days.reduce((sum, day) => sum + day.match_count, 0)} 场比赛 · 颜色越深，当天比赛越多。`;
        selectDay(selected);
    }
    root.addEventListener('click', event => {
        const button = event.target.closest('[data-date]');
        if (button) selectDay(button.dataset.date);
    });
    document.getElementById('insight-refresh').addEventListener('click', async () => {
        controller?.abort();
        const current = new AbortController();
        controller = current;
        status.textContent = '正在刷新日历…';
        try {
            const response = await fetch(config.calendarUrl, {signal: current.signal, headers: {Accept: 'application/json'}});
            const payload = await response.json();
            if (!response.ok || payload.success !== true) throw new Error('Calendar unavailable');
            if (current !== controller) return;
            days = payload.data.days;
            render();
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = '日历刷新失败，当前显示上次成功的数据。';
        }
    });
    render();
})();
