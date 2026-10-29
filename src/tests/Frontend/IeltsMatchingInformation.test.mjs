import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';

const room = readFileSync(fileURLToPath(new URL('../../resources/views/ielts/room.blade.php', import.meta.url)), 'utf8');
const start = room.indexOf('                async chooseMatchingInformationAnswer(');
const end = room.indexOf('                async saveAnswer(', start);
assert.ok(start >= 0 && end > start, 'Matching Information handler must exist');
const method = room.slice(start, end).trim().replace(/,\s*$/, '');
const makeComponent = new Function(`return ({${method}})`);

test('an older failed save cannot roll back a newer selection', async () => {
    const pending = [];
    const component = {
        ...makeComponent(),
        answers: { 1: '' },
        matchingConfirmedAnswers: {},
        matchingOptionUnavailable: () => false,
        saveAnswer(questionId, key) {
            this.answers[questionId] = key;
            return new Promise(resolve => pending.push(resolve));
        },
    };

    const first = component.chooseMatchingInformationAnswer(1, 'A', [1], false);
    const second = component.chooseMatchingInformationAnswer(1, 'B', [1], false);
    pending[0](false);
    assert.equal(await first, false);
    assert.equal(component.answers[1], 'B');
    pending[1](true);
    assert.equal(await second, true);
    assert.equal(component.answers[1], 'B');
});

test('a failed current selection restores the previous answer', async () => {
    const component = {
        ...makeComponent(),
        answers: { 1: 'A' },
        matchingConfirmedAnswers: {},
        matchingOptionUnavailable: () => false,
        async saveAnswer(questionId, key) {
            this.answers[questionId] = key;
            return false;
        },
    };

    assert.equal(await component.chooseMatchingInformationAnswer(1, 'B', [1], false), false);
    assert.equal(component.answers[1], 'A');
});

test('two failed saves restore the last server-confirmed answer', async () => {
    const pending = [];
    const component = {
        ...makeComponent(),
        answers: { 1: '' },
        matchingConfirmedAnswers: {},
        matchingOptionUnavailable: () => false,
        saveAnswer(questionId, key) {
            this.answers[questionId] = key;
            return new Promise(resolve => pending.push(resolve));
        },
    };

    const first = component.chooseMatchingInformationAnswer(1, 'A', [1], false);
    const second = component.chooseMatchingInformationAnswer(1, 'B', [1], false);
    pending[0](false);
    await first;
    assert.equal(component.answers[1], 'B');
    pending[1](false);
    await second;
    assert.equal(component.answers[1], '');
});

test('a later failed save restores an earlier confirmed selection', async () => {
    const pending = [];
    const component = {
        ...makeComponent(),
        answers: { 1: '' },
        matchingConfirmedAnswers: {},
        matchingOptionUnavailable: () => false,
        saveAnswer(questionId, key) {
            this.answers[questionId] = key;
            return new Promise(resolve => pending.push(resolve));
        },
    };

    const first = component.chooseMatchingInformationAnswer(1, 'A', [1], false);
    const second = component.chooseMatchingInformationAnswer(1, 'B', [1], false);
    pending[0](true);
    await first;
    pending[1](false);
    await second;
    assert.equal(component.answers[1], 'A');
});
