/* Local browser regression. Requires Playwright and an installed Chromium browser.
 * Optional: WCJ_BROWSER_PATH, WCJ_TEST_BASE. No valid guestbook message is submitted.
 * Run: node tests/integration/public-experience.cjs
 */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const base = new URL(process.env.WCJ_TEST_BASE || 'http://127.0.0.1:8080');
assert(base.protocol === 'http:' && ['localhost','127.0.0.1'].includes(base.hostname), 'local preview only');
const url = (route, params = {}) => {
    const target = new URL('/index.php', base);
    target.search = new URLSearchParams({r: route, ...params});
    return target.href;
};
const output = path.join(__dirname, '../../runtime/public-experience-checks');
fs.mkdirSync(output, {recursive:true});
const edge = 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe';
const executablePath = process.env.WCJ_BROWSER_PATH || (fs.existsSync(edge) ? edge : undefined);
let checks = 0;
const check = (condition, label) => { assert(condition, label); checks++; };
(async () => {
    const browser = await chromium.launch({headless:true, executablePath});
    try {
        const context = await browser.newContext({viewport:{width:1440,height:1000}});
        const page = await context.newPage();
        page.setDefaultTimeout(10000);
        const errors = [];
        const requests = [];
        page.on('pageerror', error => errors.push(error.message));
        page.on('request', request => requests.push({method:request.method(), url:request.url(), data:request.postData()}));
        const noOverflow = () => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth);
        await page.goto(url('memory/card'), {waitUntil:'networkidle'});
        const thought = '切换比赛后保留的测试感想';
        await page.fill('#memory-thought', thought);
        await page.evaluate(() => { window.originalThoughtInput = document.getElementById('memory-thought'); });
        const teams = await page.locator('#memory-team option').evaluateAll(options => options.filter(o=>o.value).map(o=>o.value));
        check(teams.length >= 2, 'existing teams available');
        await page.selectOption('#memory-team', teams[0]);
        await page.waitForFunction(() => !document.querySelector('#memory-match').disabled);
        const matches = await page.locator('#memory-match option').evaluateAll(options => options.filter(o=>o.value).map(o=>o.value));
        check(matches.length >= 2, 'selected team has multiple existing matches');
        await page.selectOption('#memory-match', matches[0]);
        await page.waitForFunction(() => !document.querySelector('#memory-download').disabled);
        check(await page.locator('#memory-thought').inputValue() === thought, 'text entered before selection is kept');
        await page.setInputFiles('#memory-photo', path.join(__dirname,'../../web/images/memory-example-ferran.png'));
        await page.waitForFunction(() => !document.querySelector('#memory-remove-photo').hidden);
        const photoPixels = () => page.locator('#memory-canvas').evaluate(canvas => Array.from(canvas.getContext('2d').getImageData(900,260,20,20).data));
        const pixels = await photoPixels();
        await page.selectOption('#memory-match', matches[1]);
        await page.waitForFunction(id => !document.querySelector('#memory-download').disabled && new URL(location.href).searchParams.get('match_id') === id, matches[1]);
        check(await page.locator('#memory-thought').inputValue() === thought, 'changing match keeps text');
        assert.deepEqual(await photoPixels(), pixels, 'changing match keeps the photo pixels'); checks++;
        check(await page.evaluate(() => window.originalThoughtInput === document.getElementById('memory-thought')), 'editor is not replaced by a page navigation');
        check(await page.locator('#memory-thought-count').textContent() === `${Array.from(thought).length} / 100 字`, 'character count');
        const downloadPromise = page.waitForEvent('download');
        await page.click('#memory-download');
        const download = await downloadPromise;
        const exportPath = path.join(output,'memory-export.png');
        await download.saveAs(exportPath);
        const png = fs.readFileSync(exportPath);
        check(png.subarray(1,4).toString() === 'PNG' && png.readUInt32BE(16) === 1200 && png.readUInt32BE(20) === 800, 'PNG export dimensions');

        // A failed update restores the old selection without discarding edits.
        const memoryRequest = target => target.searchParams.get('r') === 'memory/card';
        await page.route(memoryRequest, route => route.abort());
        await page.selectOption('#memory-match', matches[0]);
        await page.waitForFunction(() => document.querySelector('#memory-status').textContent.includes('暂时无法更新'));
        check(await page.locator('#memory-match').inputValue() === matches[1] && await page.locator('#memory-thought').inputValue() === thought, 'failure restores selection and text');
        assert.deepEqual(await photoPixels(), pixels, 'failure keeps photo'); checks++;
        await page.unroute(memoryRequest);

        // A slower superseded request must not overwrite the latest team.
        await page.route(memoryRequest, async route => {
            if (new URL(route.request().url()).searchParams.get('team_id') === teams[1]) await new Promise(resolve=>setTimeout(resolve,250));
            await route.continue().catch(()=>{});
        });
        await page.selectOption('#memory-team', teams[1]);
        await page.selectOption('#memory-team', teams[0]);
        await page.waitForFunction(id => !document.querySelector('#memory-match').disabled && new URL(location.href).searchParams.get('team_id') === id, teams[0]);
        await page.waitForTimeout(350);
        check(await page.locator('#memory-team').inputValue() === teams[0], 'latest team wins rapid switching');
        check(await page.locator('#memory-match').inputValue() === '', 'a previous team match is never carried over');
        check(await page.locator('#memory-thought').inputValue() === thought && await page.locator('#memory-photo').evaluate(input=>input.files.length===1), 'rapid switching retains local draft and file');
        await page.unroute(memoryRequest);
        await page.selectOption('#memory-match', matches[0]);
        await page.waitForFunction(() => !document.querySelector('#memory-download').disabled);
        assert.deepEqual(await photoPixels(), pixels, 'switching teams and back retains displayed photo'); checks++;
        check(requests.every(request=>request.method==='GET' && !request.url.includes(encodeURIComponent(thought)) && !request.data), 'memory selections never upload draft text or photos');
        await page.click('#memory-remove-photo');
        check(await page.locator('#memory-photo').evaluate(input=>input.files.length===0), 'photo can be removed');
        await page.evaluate(()=>scrollTo(0,0));
        await page.screenshot({path:path.join(output,'memory-desktop.png'),fullPage:true});

        await page.goto(url('guestbook/index'), {waitUntil:'networkidle'});
        const choices = await page.locator('#related-match-id option').evaluateAll(options=>options.filter(o=>o.value).map(o=>({id:o.value,date:o.dataset.date,label:o.textContent})));
        check(choices.length === 104 && choices.every(o=>o.date && o.label.includes(' — ')), 'all 104 matches use dated opponent labels, including the second directory page');
        await page.goto(url('guestbook/index',{match_id:choices[0].id}), {waitUntil:'networkidle'});
        check(await page.locator('#related-match-id').inputValue() === choices[0].id && (await page.locator('#guestbook-match-summary').innerText()).includes(choices[0].label), 'detail link preselects and describes the actual match');
        await page.fill('#guestbook-match-date',choices[0].date);
        await page.locator('#guestbook-match-date').dispatchEvent('change');
        check(await page.locator('#related-match-id option').evaluateAll(options=>options.every(o=>!o.value||o.dataset.date===document.querySelector('#guestbook-match-date').value)), 'date narrows match choices');
        await page.fill('#guestbook-match-date','2026-01-01');
        await page.locator('#guestbook-match-date').dispatchEvent('change');
        check(await page.locator('#related-match-id').inputValue()==='' && (await page.locator('#guestbook-match-summary').innerText()).includes('暂无比赛'), 'empty date has a clear unassociated state');
        await page.click('#guestbook-all-dates');
        check(await page.locator('#related-match-id option').count()===105, 'all dates can be restored');
        await page.selectOption('#related-match-id', choices[0].id);
        const csrfParam = await page.locator('meta[name="csrf-param"]').getAttribute('content');
        const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const invalid = await context.request.post(url('guestbook/create'), {form:{[csrfParam]:csrf,nickname:'',content:'',related_match_id:choices[0].id}});
        check(invalid.status()===422 && (await invalid.text()).includes(choices[0].label), 'invalid empty message is rejected and retains match choices');
        await page.screenshot({path:path.join(output,'guestbook-desktop.png'),fullPage:true});

        await page.goto(base.href,{waitUntil:'networkidle'});
        const featuredUrl = await page.locator('main a').evaluateAll(links=>links.find(a=>new URL(a.href).searchParams.get('r')==='wc-match/view')?.href);
        check(Boolean(featuredUrl), 'an existing featured story is available');
        await page.goto(featuredUrl,{waitUntil:'networkidle'});
        const total = await page.locator('.wc-event').count();
        const initial = await page.locator('.wc-event:visible').count();
        check(initial>0 && initial<total, 'long timeline starts with key events');
        check(await page.locator('#match-events-list').innerText().then(t=>t.includes('进球')&&!/\b(GOAL|H1|H2|YELLOW)\b/.test(t)), 'known event and period labels are Chinese');
        const widths = await page.evaluate(()=>({story:document.querySelector('[aria-labelledby="match-story-title"]').getBoundingClientRect().width,timeline:document.querySelector('[aria-labelledby="match-events-title"]').getBoundingClientRect().width}));
        check(Math.abs(widths.story-widths.timeline)<2, 'story and timeline use full-width consecutive sections');
        const hiddenId = await page.locator('.wc-event[hidden]').first().getAttribute('id');
        const compactHeight = await page.evaluate(()=>document.documentElement.scrollHeight);
        await page.click('#match-events-toggle');
        check(await page.locator('.wc-event:visible').count()===total, 'all events can be expanded');
        check(await page.evaluate(()=>document.documentElement.scrollHeight)>compactHeight, 'overview reduces page length');
        await page.click('#match-events-toggle');
        await page.goto(featuredUrl+'#'+hiddenId,{waitUntil:'networkidle'});
        check(await page.locator('#'+hiddenId).isVisible() && await page.locator('#match-events-toggle').getAttribute('aria-expanded')==='true', 'deep links reveal events hidden by the overview');
        await page.click('#match-events-toggle');
        const firstActions = page.locator('nav[aria-label="比赛互动"]').first();
        check(await firstActions.locator('a').count()>=2, 'existing memory and guestbook actions are available above the story');
        await page.evaluate(()=>scrollTo(0,0));
        await page.screenshot({path:path.join(output,'match-desktop.png'),fullPage:true});
        for (const route of [url('memory/card',{team_id:teams[0],match_id:matches[0]}),url('guestbook/index',{match_id:choices[0].id}),featuredUrl]) {
            await page.goto(route,{waitUntil:'networkidle'});
            await page.setViewportSize({width:390,height:844});
            check(await noOverflow(),'mobile page has no horizontal overflow '+new URL(route).searchParams.get('r'));
        }
        const plain = await browser.newContext({javaScriptEnabled:false});
        const plainPage = await plain.newPage();
        await plainPage.goto(featuredUrl);
        check(await plainPage.locator('.wc-event:visible').count()===total, 'without JavaScript the full timeline is readable');
        await plainPage.goto(url('guestbook/index',{match_id:choices[0].id}));
        check(await plainPage.locator('#related-match-id option').count()===105 && await plainPage.locator('#related-match-id').inputValue()===choices[0].id, 'without JavaScript named match selection and preselection work');
        await plain.close();
        check(errors.length===0,'no client script errors: '+errors.join('; '));
        console.log(`PASS ${checks} local public-experience browser checks`);
    } finally { await browser.close(); }
})().catch(error=>{console.error(error);process.exitCode=1;});
