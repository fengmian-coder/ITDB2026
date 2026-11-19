/* Show a compact overview; every event and its existing anchor remain available. */
(() => {
    'use strict';
    const list = document.getElementById('match-events-list');
    if (!list) return;
    const events = Array.from(list.querySelectorAll('.wc-event'));
    const keyCount = events.filter(event => event.dataset.keyEvent === '1').length;
    const toggle = document.getElementById('match-events-toggle');
    const summary = document.getElementById('match-events-summary');
    if (keyCount === 0 || keyCount === events.length) return;
    let expanded = false;
    function render() {
        events.forEach(event => { event.hidden = !expanded && event.dataset.keyEvent !== '1'; });
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.textContent = expanded ? `只看关键事件（${keyCount}）` : `展开全部事件（${events.length}）`;
        summary.textContent = expanded ? `全部 ${events.length} 个事件，按比赛顺序展示。`
            : `先看 ${keyCount} 个进球、红牌或点球等关键事件，其余事件可展开查看。`;
    }
    function revealTarget() {
        const target = events.find(event => '#' + event.id === location.hash);
        if (!target || !target.hidden) return;
        expanded = true;
        render();
        target.scrollIntoView({block: 'start'});
    }
    toggle.classList.remove('d-none');
    toggle.addEventListener('click', () => { expanded = !expanded; render(); });
    window.addEventListener('hashchange', revealTarget);
    render();
    revealTarget();
})();
