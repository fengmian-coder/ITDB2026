/** WorldCup Journey · B 赛程阶段与球队筛选联动。 */
(() => {
    'use strict';
    const data = document.getElementById('match-team-filter-data');
    if (!data) return;

    const {teams, teamIds, stageLabels, featuredCounts} = JSON.parse(data.textContent);
    const stage = document.getElementById('match-stage');
    const featured = document.getElementById('match-featured');
    const team = document.getElementById('match-team-id');
    const feedback = document.getElementById('match-stage-feedback');

    function updateFilters() {
        const selectedStage = stage.value;
        const onlyFeatured = featured.checked;
        const stages = Object.entries(stageLabels).filter(([code]) => !onlyFeatured || featuredCounts[code] > 0);
        const stageAllowed = stages.some(([code]) => code === selectedStage);
        stage.replaceChildren(
            new Option(stages.length ? '全部阶段' : '暂无精选阶段', ''),
            ...stages.map(([code, label]) => new Option(
                onlyFeatured ? `${label}（${featuredCounts[code]} 场）` : label, code
            ))
        );
        stage.value = stageAllowed ? selectedStage : '';
        if (selectedStage && !stageAllowed) {
            feedback.textContent = '原阶段暂无精选，已切换至全部阶段。';
        }

        const selected = team.value;
        const allowed = new Set(teamIds[onlyFeatured ? 'featured' : 'all'][stage.value] ?? []);
        const available = (stage.value || onlyFeatured)
            ? teams.filter(([id]) => allowed.has(id))
            : teams;

        team.replaceChildren(
            new Option(available.length ? '全部球队' : '暂无可选球队', ''),
            ...available.map(([id, name]) => new Option(name, id))
        );
        team.value = available.some(([id]) => id === selected) ? selected : '';
    }

    function filterChanged() {
        feedback.textContent = '';
        updateFilters();
    }

    stage.addEventListener('change', filterChanged);
    featured.addEventListener('change', filterChanged);
    window.addEventListener('pageshow', updateFilters);
    updateFilters();
})();
