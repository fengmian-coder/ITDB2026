/* WorldCup Journey | A | Browser-only memory card preview and PNG export | 2026-11-10 */
(() => {
    'use strict';
    const teamSelect = document.getElementById('memory-team');
    if (teamSelect) {
        teamSelect.addEventListener('change', () => {
            document.getElementById('memory-match').value = '';
        });
    }
    const canvas = document.getElementById('memory-canvas');
    const dataElement = document.getElementById('memory-data');
    if (!canvas || !dataElement) return;
    const ctx = canvas.getContext('2d');
    const status = document.getElementById('memory-status');
    const download = document.getElementById('memory-download');
    if (!ctx) {
        status.textContent = '此浏览器暂不支持纪念卡绘制，请尝试更新浏览器';
        download.disabled = true;
        return;
    }
    const data = JSON.parse(dataElement.textContent);
    const thought = document.getElementById('memory-thought');
    let photo = null;
    let teamFlag = null;
    let photoVersion = 0;
    let ready = false;
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
    thought.addEventListener('input', () => { if (ready) draw(); });
    document.getElementById('memory-photo').addEventListener('change', async (event) => {
        const version = ++photoVersion;
        const file = event.target.files[0];
        photo = null;
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
            status.textContent = '照片已加入预览';
        } catch (error) {
            status.textContent = '这张图片无法读取，请换一张';
        } finally {
            URL.revokeObjectURL(url);
            if (ready && version === photoVersion) draw();
        }
    });
    download.disabled = true;
    const fontsReady = document.fonts ? document.fonts.ready : Promise.resolve();
    const flagReady = (async () => {
        if (!data.flag_url) return;
        try {
            const image = new Image();
            image.src = data.flag_url;
            await image.decode();
            teamFlag = image;
        } catch (error) { /* A missing flag must not prevent PNG export. */ }
    })();
    Promise.all([fontsReady, flagReady]).then(() => { ready = true; draw(); download.disabled = false; });
    download.addEventListener('click', () => {
        if (!ready) return;
        canvas.toBlob((blob) => {
            if (!blob) { status.textContent = '导出失败，请稍后重试'; return; }
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `WorldCup-Journey-${data.team_id}-${data.match_id}.png`;
            document.body.append(link); link.click(); link.remove();
            setTimeout(() => URL.revokeObjectURL(url), 30000);
            status.textContent = '纪念卡已生成，请查看浏览器下载记录';
        }, 'image/png');
    });
})();
