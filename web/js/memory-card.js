/* WorldCup Journey | A | Browser-only memory card preview and PNG export | 2026-11-10 */
(() => {
    'use strict';
    const teamSelect = document.getElementById('memory-team');
    const matchSelect = document.getElementById('memory-match');
    const selectionForm = document.getElementById('memory-selection');
    if (!teamSelect || !matchSelect || !selectionForm) return;
    const status = document.getElementById('memory-status');
    const download = document.getElementById('memory-download');
    const thought = document.getElementById('memory-thought');
    const photoInput = document.getElementById('memory-photo');
    const removePhoto = document.getElementById('memory-remove-photo');
    let canvas, ctx, data;
    let photo = null;
    let teamFlag = null;
    let photoVersion = 0;
    let ready = false;
    let previewVersion = 0;
    let request = null;
    let loadedSelection = {team: teamSelect.value, match: matchSelect.value};
    document.getElementById('memory-confirm').hidden = true;
    document.getElementById('memory-selection-hint').textContent = '选择后自动更新预览；切换球队或比赛会保留当前感想和照片。';

    async function preparePreview() {
        const version = ++previewVersion;
        ready = false;
        download.disabled = true;
        canvas = document.getElementById('memory-canvas');
        const dataElement = document.getElementById('memory-data');
        if (!canvas || !dataElement) return;
        ctx = canvas.getContext('2d');
        if (!ctx) {
            status.textContent = '此浏览器暂不支持纪念卡绘制，请尝试更新浏览器';
            return;
        }
        data = JSON.parse(dataElement.textContent);
        teamFlag = null;
        const flagReady = (async () => {
            if (!data.flag_url) return null;
            try {
                const image = new Image();
                image.src = data.flag_url;
                await image.decode();
                return image;
            } catch { return null; }
        })();
        const [, flag] = await Promise.all([document.fonts?.ready ?? Promise.resolve(), flagReady]);
        if (version !== previewVersion) return;
        teamFlag = flag;
        ready = true;
        draw();
        download.disabled = false;
    }

    async function updateSelection(event) {
        event?.preventDefault();
        request?.abort();
        const current = new AbortController();
        request = current;
        const url = new URL(selectionForm.action, location.href);
        url.search = new URLSearchParams(new FormData(selectionForm)).toString();
        matchSelect.disabled = true;
        download.disabled = true;
        ready = false;
        ++previewVersion;
        status.textContent = '正在更新比赛与预览…';
        try {
            const response = await fetch(url, {signal: current.signal});
            if (!response.ok) throw new Error('selection failed');
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const nextTeam = html.getElementById('memory-team');
            const nextMatch = html.getElementById('memory-match');
            const nextPreview = html.querySelector('.memory-preview');
            if (!nextTeam || !nextMatch || !nextPreview) throw new Error('invalid editor response');
            if (request !== current) return;
            const nextMatchId = nextMatch.value;
            teamSelect.value = nextTeam.value;
            matchSelect.replaceChildren(...nextMatch.childNodes);
            matchSelect.value = nextMatchId;
            matchSelect.disabled = nextMatch.disabled;
            document.querySelector('.memory-preview').replaceChildren(...nextPreview.childNodes);
            loadedSelection = {team: teamSelect.value, match: matchSelect.value};
            history.replaceState(null, '', url.pathname + url.search);
            await preparePreview();
            if (request === current) status.textContent = dataElementPresent() ? '预览已更新，当前感想和照片已保留' : '请选择一场比赛，当前感想和照片已保留';
        } catch (error) {
            if (error.name === 'AbortError' || request !== current) return;
            teamSelect.value = loadedSelection.team;
            matchSelect.value = loadedSelection.match;
            matchSelect.disabled = matchSelect.options.length <= 1;
            await preparePreview();
            if (request === current) status.textContent = '暂时无法更新，已保留原选择和编辑内容，请重新选择后重试';
        }
    }
    function dataElementPresent() { return document.getElementById('memory-data') !== null; }
    teamSelect.addEventListener('change', () => {
        // Never carry a match from the previous team into the new request.
        matchSelect.value = '';
        updateSelection();
    });
    matchSelect.addEventListener('change', updateSelection);
    selectionForm.addEventListener('submit', updateSelection);
    const font = '"Microsoft YaHei", "PingFang SC", system-ui, sans-serif';
    function line(text, x, y, size, color = '#173c32', weight = 400) {
        ctx.fillStyle = color;
        ctx.font = `${weight} ${size}px ${font}`;
        ctx.fillText(String(text), x, y);
    }
    function wrap(text, x, y, maxWidth, size, lineHeight, maxLines) {
        ctx.font = `400 ${size}px ${font}`;
        ctx.fillStyle = '#4d665a';
        let current = '';
        let lines = 0;
        for (const char of Array.from(text)) {
            if (char === '\n' || ctx.measureText(current + char).width > maxWidth) {
                if (lines === maxLines - 1) {
                    ctx.fillText(current + '…', x, y);
                    return;
                }
                ctx.fillText(current, x, y);
                y += lineHeight;
                lines++;
                current = char === '\n' ? '' : char;
            } else current += char;
        }
        if (current) ctx.fillText(current, x, y);
    }
    function draw() {
        ctx.clearRect(0, 0, 1200, 800);
        ctx.fillStyle = '#f4f7ef'; ctx.fillRect(0, 0, 1200, 800);
        ctx.fillStyle = '#175b46'; ctx.fillRect(0, 0, 1200, 175);
        line('我的世界杯记忆', 64, 89, 42, '#ffffff', 700);
        line('WORLD CUP JOURNEY · 2026', 66, 137, 21, '#d6e7d7');
        if (teamFlag) ctx.drawImage(teamFlag, 1040, 53, 96, 72);
        line(`球队：${data.team_name}`, 64, 243, 31, '#175b46', 700);
        line(data.date, 66, 293, 22, '#607367');
        line(data.stage, 66, 331, 22, '#607367');
        const textWidth = photo ? 680 : 1068;
        ctx.font = `700 40px ${font}`;
        const matchup = `${data.home_name}  ${data.score}  ${data.away_name}`;
        let scoreSize = 40;
        while (ctx.measureText(matchup).width > textWidth && scoreSize > 22) {
            scoreSize--;
            ctx.font = `700 ${scoreSize}px ${font}`;
        }
        line(matchup, 64, 413, scoreSize, '#173c32', 700);
        if (data.penalties) line(data.penalties, 66, 455, 22, '#607367');
        if (photo) {
            ctx.save();
            ctx.beginPath(); ctx.rect(830, 230, 305, 340); ctx.clip();
            const ratio = Math.max(305 / photo.width, 340 / photo.height);
            const width = photo.width * ratio, height = photo.height * ratio;
            ctx.drawImage(photo, 830 + (305 - width) / 2, 230 + (340 - height) / 2, width, height);
            ctx.restore();
        }
        if (data.story) wrap(data.story, 66, 502, textWidth, 22, 34, 2);
        const message = Array.from(thought.value).slice(0, 100).join('').trim();
        wrap(`我的感想：${message}`, 66, 610, 1068, 26, 40, 3);
        line('WorldCup Journey', 66, 752, 22, '#175b46', 700);
        line('2026', 1060, 752, 20, '#607367');
    }
    thought.addEventListener('input', () => {
        document.getElementById('memory-thought-count').textContent = `${Array.from(thought.value).length} / 100 字`;
        if (ready) draw();
    });
    photoInput.addEventListener('change', async (event) => {
        const version = ++photoVersion;
        const file = event.target.files[0];
        photo = null;
        removePhoto.hidden = true;
        if (!file) { if (ready) draw(); return; }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            status.textContent = '请选择不超过 5 MB 的 JPG、PNG 或 WebP 图片';
            event.target.value = ''; if (ready) draw(); return;
        }
        const url = URL.createObjectURL(file);
        try {
            const image = new Image();
            image.src = url;
            await image.decode();
            if (version !== photoVersion) return;
            photo = image;
            removePhoto.hidden = false;
            status.textContent = '照片已加入预览';
        } catch (error) {
            status.textContent = '这张图片无法读取，请换一张';
        } finally {
            URL.revokeObjectURL(url);
            if (ready && version === photoVersion) draw();
        }
    });
    removePhoto.addEventListener('click', () => {
        ++photoVersion;
        photo = null;
        photoInput.value = '';
        removePhoto.hidden = true;
        status.textContent = '照片已移除';
        if (ready) draw();
    });
    preparePreview();
    download.addEventListener('click', () => {
        if (!ready) return;
        const filename = `WorldCup-Journey-${data.team_id}-${data.match_id}.png`;
        canvas.toBlob((blob) => {
            if (!blob) { status.textContent = '导出失败，请稍后重试'; return; }
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.append(link); link.click(); link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 30000);
            status.textContent = '纪念卡已生成，请查看浏览器下载记录';
        }, 'image/png');
    });
})();
