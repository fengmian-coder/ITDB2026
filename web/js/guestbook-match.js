/* Date and named-match selection; the submitted field remains related_match_id. */
(() => {
    'use strict';
    const select = document.getElementById('related-match-id');
    const date = document.getElementById('guestbook-match-date');
    if (!select || !date) return;
    const options = Array.from(select.options, option => option.cloneNode(true));
    const summary = document.getElementById('guestbook-match-summary');
    const clearDate = document.getElementById('guestbook-all-dates');
    function describe() {
        const emptyDate = select.options.length === 1 && Boolean(date.value);
        clearDate.hidden = !date.value;
        summary.classList.toggle('visually-hidden', !emptyDate);
        select.title = select.value ? select.selectedOptions[0].textContent : '';
        summary.textContent = select.value
            ? `已关联：${select.selectedOptions[0].textContent}（北京时间）`
            : (emptyDate ? '该日期暂无比赛' : '未关联比赛');
    }
    function filter() {
        const selected = select.value;
        select.replaceChildren(...options.filter(option => !option.value || !date.value || option.dataset.date === date.value)
            .map(option => option.cloneNode(true)));
        select.value = Array.from(select.options).some(option => option.value === selected) ? selected : '';
        describe();
    }
    document.getElementById('guestbook-date-filter').classList.remove('d-none');
    date.addEventListener('change', filter);
    clearDate.addEventListener('click', () => { date.value = ''; filter(); });
    select.addEventListener('change', describe);
    describe();
})();
