/* WorldCup Journey | A/B | Memory team selection regression | 2026-11-11 */
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../web/js/memory-card.js'), 'utf8');
let assertions = 0;

// 球队切换监听需要在首次选择及已展示纪念卡的页面中均有效。
for (const hasCanvas of [false, true]) {
    const match = { value: '123' };
    let change;
    const team = {
        addEventListener(event, callback) {
            assert.equal(event, 'change');
            assertions++;
            change = callback;
        },
    };
    const elements = {
        'memory-team': team,
        'memory-match': match,
        'memory-canvas': hasCanvas ? {} : null,
        'memory-data': null,
    };
    vm.runInNewContext(source, {
        document: { getElementById: id => elements[id] ?? null },
    });
    assert.equal(match.value, '123');
    assertions++;
    change();
    assert.equal(match.value, '');
    assertions++;
}

console.log(`PASS ${assertions} team-selection assertions`);
