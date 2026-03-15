let currentStatus = 'changed';
let currentFixture = null;
let currentView = 'rendered';

const fixtureList = document.getElementById('fixture-list');
const toolbar = document.getElementById('toolbar');
const fixtureName = document.getElementById('fixture-name');
const comparison = document.getElementById('comparison');
const acceptBtn = document.getElementById('accept-btn');

const grouped = {
    changed: window.fixtures.filter(f => f.status === 'changed'),
    unchanged: window.fixtures.filter(f => f.status === 'unchanged'),
    skipped: window.fixtures.filter(f => f.status === 'skipped'),
    new: window.fixtures.filter(f => f.status === 'new')
};

function renderFixtureList() {
    const items = grouped[currentStatus] || [];
    fixtureList.innerHTML = items.map(f => {
        const lang = f.config?.language || '';
        const badges = lang ? `<div class="meta"><span class="badge lang">${escapeHtml(lang)}</span></div>` : '';

        return `<div class="fixture-item${currentFixture?.filename === f.filename ? ' active' : ''}" data-filename="${f.filename}">
            <span class="filename">${escapeHtml(f.filename.replace('.txt', ''))}</span>${badges}
        </div>`;
    }).join('') || '<div class="empty-state"><span>No fixtures in this category</span></div>';
}

function escapeHtml(str) {
    return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function formatHtml(html) {
    if (!html) return '';

    let formatted = html
        .replace(/(<pre><code[^>]*>)/g, '$1\n')
        .replace(/(<\/div>)(<div class=['"]line)/g, '$1\n$2')
        .replace(/(<\/code><\/pre>)/g, '\n$1')
        .replace(/\n(<div class=['"]line['"])/g, '\n  $1');

    return formatted;
}

function parseTokens(html) {
    if (!html) return [];

    const tokens = [];
    const lineRegex = /<div class=['"]line['"][^>]*>([\s\S]*?)<\/div>/g;
    const tokenRegex = /<span[^>]*style="[^"]*color:\s*([^;"]+)[^"]*"[^>]*>([^<]*)<\/span>/g;

    let lineNum = 0;
    let lineMatch;

    while ((lineMatch = lineRegex.exec(html)) !== null) {
        lineNum++;
        const lineContent = lineMatch[1];
        let tokenMatch;

        while ((tokenMatch = tokenRegex.exec(lineContent)) !== null) {
            const color = tokenMatch[1].trim();
            const text = tokenMatch[2];
            if (text.trim()) {
                tokens.push({ line: lineNum, color, text });
            }
        }
    }

    return tokens;
}

function diffTokens(expectedHtml, actualHtml) {
    const expTokens = parseTokens(expectedHtml);
    const actTokens = parseTokens(actualHtml);

    const changes = [];
    let colorChanges = 0;
    let textChanges = 0;

    // Build maps for comparison
    const expByLine = new Map();
    const actByLine = new Map();

    expTokens.forEach(t => {
        if (!expByLine.has(t.line)) expByLine.set(t.line, []);
        expByLine.get(t.line).push(t);
    });

    actTokens.forEach(t => {
        if (!actByLine.has(t.line)) actByLine.set(t.line, []);
        actByLine.get(t.line).push(t);
    });

    // Compare each line
    const allLines = new Set([...expByLine.keys(), ...actByLine.keys()]);

    for (const line of allLines) {
        const exp = expByLine.get(line) || [];
        const act = actByLine.get(line) || [];

        // Simple comparison: check if colors changed for same text
        for (let i = 0; i < Math.max(exp.length, act.length); i++) {
            const e = exp[i];
            const a = act[i];

            if (e && a) {
                if (e.text === a.text && e.color !== a.color) {
                    colorChanges++;
                    if (changes.length < 5) {
                        changes.push({
                            type: 'color',
                            line,
                            from: e.color,
                            to: a.color,
                            text: e.text.substring(0, 20)
                        });
                    }
                } else if (e.text !== a.text) {
                    textChanges++;
                }
            } else if (e && !a) {
                textChanges++;
            } else if (!e && a) {
                textChanges++;
            }
        }
    }

    return { changes, colorChanges, textChanges };
}

function tokenize(str) {
    return str.split(/(\s+|[<>="'\/;:,.()\[\]{}])/g).filter(t => t !== '');
}

function lcs(a, b) {
    const m = a.length, n = b.length;
    const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));

    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            if (a[i-1] === b[j-1]) {
                dp[i][j] = dp[i-1][j-1] + 1;
            } else {
                dp[i][j] = Math.max(dp[i-1][j], dp[i][j-1]);
            }
        }
    }

    const result = [];
    let i = m, j = n;
    while (i > 0 || j > 0) {
        if (i > 0 && j > 0 && a[i-1] === b[j-1]) {
            result.unshift({ type: 'same', value: a[i-1] });
            i--; j--;
        } else if (j > 0 && (i === 0 || dp[i][j-1] >= dp[i-1][j])) {
            result.unshift({ type: 'add', value: b[j-1] });
            j--;
        } else {
            result.unshift({ type: 'del', value: a[i-1] });
            i--;
        }
    }
    return result;
}

function computeInlineDiff(expLine, actLine) {
    if (expLine === actLine) {
        return {
            expHtml: escapeHtml(expLine),
            actHtml: escapeHtml(actLine),
            hasDiff: false
        };
    }

    const expTokens = tokenize(expLine);
    const actTokens = tokenize(actLine);
    const diff = lcs(expTokens, actTokens);

    let expHtml = '';
    let actHtml = '';

    for (const d of diff) {
        const escaped = escapeHtml(d.value);
        if (d.type === 'same') {
            expHtml += escaped;
            actHtml += escaped;
        } else if (d.type === 'del') {
            expHtml += `<span class="diff-del">${escaped}</span>`;
        } else if (d.type === 'add') {
            actHtml += `<span class="diff-add">${escaped}</span>`;
        }
    }

    return { expHtml, actHtml, hasDiff: true };
}

function computeLineDiff(expected, actual) {
    const formattedExp = formatHtml(expected);
    const formattedAct = formatHtml(actual);

    const expLines = formattedExp.split('\n');
    const actLines = formattedAct.split('\n');
    const maxLines = Math.max(expLines.length, actLines.length);

    let expHtml = '';
    let actHtml = '';

    for (let i = 0; i < maxLines; i++) {
        const expLine = expLines[i] ?? '';
        const actLine = actLines[i] ?? '';
        const lineNum = `<span class="line-num">${i + 1}</span>`;

        if (i >= expLines.length) {
            actHtml += `<div class="diff-line added">${lineNum}<span class="diff-add">${escapeHtml(actLine) || ' '}</span></div>`;
        } else if (i >= actLines.length) {
            expHtml += `<div class="diff-line removed">${lineNum}<span class="diff-del">${escapeHtml(expLine) || ' '}</span></div>`;
        } else {
            const inline = computeInlineDiff(expLine, actLine);
            const lineClass = inline.hasDiff ? (expLine ? ' removed' : '') : ' same';
            const actLineClass = inline.hasDiff ? ' added' : ' same';

            expHtml += `<div class="diff-line${lineClass}">${lineNum}${inline.expHtml || ' '}</div>`;
            actHtml += `<div class="diff-line${actLineClass}">${lineNum}${inline.actHtml || ' '}</div>`;
        }
    }

    return { expHtml, actHtml };
}

function renderComparison() {
    if (!currentFixture) {
        comparison.innerHTML = '<div class="empty-state"><span>Select a fixture to compare</span></div>';
        toolbar.style.display = 'none';
        return;
    }

    toolbar.style.display = 'flex';
    fixtureName.textContent = currentFixture.filename;
    acceptBtn.disabled = currentFixture.status !== 'changed';

    const viewClass = currentView === 'rendered' ? 'rendered-view' : 'source-view';

    if (currentView === 'rendered') {
        comparison.innerHTML = `
            <div class="panels-container">
                <div class="panel ${viewClass}">
                    <div class="panel-header">Expected</div>
                    <div class="panel-content">${currentFixture.expected || '<div class="empty-state"><span>No expected output</span></div>'}</div>
                </div>
                <div class="panel ${viewClass}">
                    <div class="panel-header">Actual</div>
                    <div class="panel-content">${currentFixture.actual || '<div class="empty-state"><span>No actual output</span></div>'}</div>
                </div>
            </div>
        `;
    } else {
        const diff = computeLineDiff(currentFixture.expected, currentFixture.actual);
        const tokenDiff = diffTokens(currentFixture.expected, currentFixture.actual);

        let summaryHtml = '';
        if (tokenDiff.colorChanges > 0 || tokenDiff.textChanges > 0) {
            const parts = [];
            if (tokenDiff.colorChanges > 0) parts.push(`${tokenDiff.colorChanges} color`);
            if (tokenDiff.textChanges > 0) parts.push(`${tokenDiff.textChanges} token`);

            summaryHtml = `<div class="diff-summary">
                <span class="summary-label">Changes:</span> ${parts.join(', ')}
                ${tokenDiff.changes.slice(0, 3).map(c =>
                    c.type === 'color'
                        ? `<span class="summary-item"><span class="line-ref">L${c.line}</span> <span class="color-chip" style="background:${c.from}"></span>→<span class="color-chip" style="background:${c.to}"></span></span>`
                        : ''
                ).join('')}
            </div>`;
        }

        comparison.innerHTML = `
            ${summaryHtml}
            <div class="panels-container">
                <div class="panel ${viewClass}">
                    <div class="panel-header">Expected</div>
                    <div class="panel-content"><pre class="source-code">${diff.expHtml}</pre></div>
                </div>
                <div class="panel ${viewClass}">
                    <div class="panel-header">Actual</div>
                    <div class="panel-content"><pre class="source-code">${diff.actHtml}</pre></div>
                </div>
            </div>
        `;
    }
}

document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentStatus = tab.dataset.status;
        currentFixture = null;
        renderFixtureList();
        renderComparison();
    });
});

fixtureList.addEventListener('click', (e) => {
    const item = e.target.closest('.fixture-item');
    if (!item) return;

    const filename = item.dataset.filename;
    currentFixture = window.fixtures.find(f => f.filename === filename);
    renderFixtureList();
    renderComparison();
});

document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentView = btn.dataset.view;
        renderComparison();
    });
});

acceptBtn.addEventListener('click', async () => {
    if (!currentFixture || currentFixture.status !== 'changed') return;

    acceptBtn.disabled = true;
    acceptBtn.textContent = 'Accepting...';

    try {
        const res = await fetch(`?accept=${encodeURIComponent(currentFixture.filename)}`, {
            method: 'POST'
        });
        const data = await res.json();

        if (data.success) {
            const idx = window.fixtures.findIndex(f => f.filename === currentFixture.filename);
            if (idx !== -1) {
                window.fixtures[idx].status = 'unchanged';
                window.fixtures[idx].expected = window.fixtures[idx].actual;
            }

            grouped.changed = window.fixtures.filter(f => f.status === 'changed');
            grouped.unchanged = window.fixtures.filter(f => f.status === 'unchanged');

            document.querySelector('.tab.changed .count').textContent = grouped.changed.length;
            document.querySelector('.tab.unchanged .count').textContent = grouped.unchanged.length;

            document.querySelector('.stats .stat.changed').innerHTML = `<span class="dot"></span><span>${grouped.changed.length} Changed</span>`;
            document.querySelector('.stats .stat.unchanged').innerHTML = `<span class="dot"></span><span>${grouped.unchanged.length} Passed</span>`;

            if (currentStatus === 'changed') {
                currentFixture = grouped.changed[0] || null;
            } else {
                currentFixture = window.fixtures.find(f => f.filename === currentFixture.filename);
            }

            renderFixtureList();
            renderComparison();
        } else {
            alert('Failed to accept changes');
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }

    acceptBtn.textContent = 'Accept Changes';
});

renderFixtureList();

if (grouped.changed.length > 0) {
    currentFixture = grouped.changed[0];
    renderFixtureList();
    renderComparison();
}
