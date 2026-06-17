<?php

namespace Shift\OpenApi;

final class OpenApiLivePage
{
    public function render(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShiftPHP OpenAPI</title>
    <style>
        :root,
        [data-theme="light"] {
            color-scheme: light;
            --bg: #f7f9fc;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #657386;
            --line: #d9e1ea;
            --accent: #d43c2f;
            --code-bg: #101827;
            --code-text: #f8fafc;
            --get: #0f7b6c;
            --post: #1f63b5;
            --put: #9a6700;
            --patch: #7c3aed;
            --delete: #b42318;
        }

        [data-theme="dark"] {
            color-scheme: dark;
            --bg: #0f1419;
            --panel: #151d26;
            --text: #e7edf4;
            --muted: #a9b5c2;
            --line: #2b3745;
            --accent: #f26a5b;
            --code-bg: #070b10;
            --code-text: #f4f7fb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        header {
            padding: 2rem min(6vw, 4rem);
            border-bottom: 1px solid var(--line);
            background: var(--panel);
        }

        .topbar {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        h1 {
            margin: 0 0 0.35rem;
            font-size: 2rem;
            letter-spacing: 0;
        }

        header p {
            margin: 0;
            color: var(--muted);
        }

        button,
        .download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.25rem;
            padding: 0.4rem 0.65rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            color: var(--text);
            cursor: pointer;
            font: inherit;
            text-decoration: none;
        }

        button:hover,
        .download:hover {
            border-color: var(--accent);
        }

        main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 1.25rem 1rem 4rem;
        }

        .toolbar {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(9rem, 14rem) auto;
            gap: 0.75rem;
            position: sticky;
            top: 0;
            z-index: 1;
            padding: 0.75rem 0;
            background: var(--bg);
        }

        input,
        select {
            width: 100%;
            min-height: 2.4rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            color: var(--text);
            font: inherit;
        }

        .meta {
            margin: 0 0 1rem;
            color: var(--muted);
        }

        .operation {
            margin: 0 0 1rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            overflow: hidden;
        }

        .operation[hidden] {
            display: none;
        }

        .operation summary {
            display: grid;
            grid-template-columns: 4.6rem minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            cursor: pointer;
        }

        .method {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .get { background: var(--get); }
        .post { background: var(--post); }
        .put { background: var(--put); }
        .patch { background: var(--patch); }
        .delete { background: var(--delete); }

        .path,
        .operation-id {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            overflow-wrap: anywhere;
        }

        .tag {
            padding: 0.15rem 0.4rem;
            border-radius: 999px;
            background: var(--bg);
            color: var(--muted);
            font-size: 0.78rem;
        }

        .details {
            padding: 0 1rem 1rem;
            border-top: 1px solid var(--line);
        }

        h2 {
            margin: 1rem 0 0.5rem;
            font-size: 1rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.65rem;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            font-size: 0.78rem;
            text-transform: uppercase;
        }

        code, pre {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        pre {
            overflow-x: auto;
            padding: 1rem;
            border-radius: 8px;
            background: var(--code-bg);
            color: var(--code-text);
        }

        .empty {
            padding: 1rem;
            color: var(--muted);
        }

        @media (max-width: 720px) {
            .topbar,
            .toolbar,
            .operation summary {
                display: block;
            }

            .toolbar > * {
                margin-bottom: 0.75rem;
            }

            .method,
            .tag {
                display: inline-block;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="topbar">
            <div>
                <h1 id="title">ShiftPHP OpenAPI</h1>
                <p id="subtitle">Loading OpenAPI document...</p>
            </div>
            <button type="button" id="theme">Dark theme</button>
        </div>
    </header>
    <main>
        <div class="toolbar">
            <input id="search" type="search" placeholder="Search endpoints">
            <select id="tagFilter" aria-label="Filter by tag">
                <option value="">All tags</option>
            </select>
            <a class="download" href="openapi.json" download>Download JSON</a>
        </div>
        <p class="meta" id="meta"></p>
        <div id="app"></div>
    </main>

    <script>
        const app = document.querySelector('#app');
        const title = document.querySelector('#title');
        const subtitle = document.querySelector('#subtitle');
        const meta = document.querySelector('#meta');
        const search = document.querySelector('#search');
        const tagFilter = document.querySelector('#tagFilter');
        const theme = document.querySelector('#theme');
        let allOperations = [];

        theme.addEventListener('click', () => {
            const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
            document.documentElement.dataset.theme = next;
            theme.textContent = next === 'dark' ? 'Light theme' : 'Dark theme';
        });

        search.addEventListener('input', applyFilters);
        tagFilter.addEventListener('change', applyFilters);

        fetch('openapi.json')
            .then(response => response.json())
            .then(render)
            .catch(error => {
                subtitle.textContent = 'Unable to load openapi.json';
                app.innerHTML = `<pre>${escapeHtml(String(error))}</pre>`;
            });

        function render(document) {
            title.textContent = document.info?.title || 'ShiftPHP OpenAPI';
            subtitle.textContent = `OpenAPI ${document.openapi || ''} - ${document.info?.version || ''}`;

            const paths = document.paths || {};
            allOperations = [];

            Object.keys(paths).sort().forEach(path => {
                Object.keys(paths[path]).sort().forEach(method => {
                    allOperations.push({ path, method, operation: paths[path][method] });
                });
            });

            const tags = [...new Set(allOperations.flatMap(item => item.operation.tags || []))].sort();
            tagFilter.innerHTML = '<option value="">All tags</option>' + tags.map(tag => `<option value="${escapeHtml(tag)}">${escapeHtml(tag)}</option>`).join('');

            if (allOperations.length === 0) {
                app.innerHTML = '<p class="empty">No operations found.</p>';
                meta.textContent = '0 operations';
                return;
            }

            app.innerHTML = allOperations.map(renderOperation).join('');
            applyFilters();
        }

        function renderOperation(item) {
            const operation = item.operation;
            const parameters = operation.parameters || [];
            const responses = operation.responses || {};
            const tag = (operation.tags || [])[0] || 'default';
            const summary = operation.summary ? `<p>${escapeHtml(operation.summary)}</p>` : '';
            const description = operation.description ? `<p>${escapeHtml(operation.description)}</p>` : '';

            return `
                <details class="operation" open data-path="${escapeHtml(item.path)}" data-method="${escapeHtml(item.method)}" data-tag="${escapeHtml(tag)}" data-operation="${escapeHtml(operation.operationId || '')}">
                    <summary>
                        <span class="method ${item.method}">${item.method}</span>
                        <span class="path">${escapeHtml(item.path)}</span>
                        <span class="tag">${escapeHtml(tag)}</span>
                    </summary>
                    <div class="details">
                        <h2>Operation</h2>
                        <p class="operation-id"><code>${escapeHtml(operation.operationId || '')}</code></p>
                        ${summary}
                        ${description}
                        ${operation.deprecated ? '<p><strong>Deprecated</strong></p>' : ''}
                        ${renderSecurity(operation.security)}
                        ${renderParameters(parameters)}
                        ${renderRequestBody(operation.requestBody)}
                        ${renderResponses(responses)}
                    </div>
                </details>
            `;
        }

        function applyFilters() {
            const query = search.value.trim().toLowerCase();
            const tag = tagFilter.value;
            let visible = 0;

            document.querySelectorAll('.operation').forEach(operation => {
                const haystack = [
                    operation.dataset.path,
                    operation.dataset.method,
                    operation.dataset.operation,
                    operation.dataset.tag,
                ].join(' ').toLowerCase();
                const matchesSearch = query === '' || haystack.includes(query);
                const matchesTag = tag === '' || operation.dataset.tag === tag;
                const show = matchesSearch && matchesTag;
                operation.hidden = !show;
                visible += show ? 1 : 0;
            });

            meta.textContent = `${visible} of ${allOperations.length} operations`;
        }

        function renderSecurity(security) {
            if (!security || security.length === 0) {
                return '';
            }

            return `<h2>Security</h2><pre>${escapeHtml(JSON.stringify(security, null, 2))}</pre>`;
        }

        function renderParameters(parameters) {
            if (parameters.length === 0) {
                return '';
            }

            const rows = parameters.map(parameter => `
                <tr>
                    <td><code>${escapeHtml(parameter.name)}</code></td>
                    <td>${escapeHtml(parameter.in)}</td>
                    <td>${parameter.required ? 'yes' : 'no'}</td>
                    <td>${escapeHtml(schemaLabel(parameter.schema || {}))}</td>
                </tr>
            `).join('');

            return `
                <h2>Parameters</h2>
                <table>
                    <thead><tr><th>Name</th><th>In</th><th>Required</th><th>Schema</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function renderRequestBody(requestBody) {
            if (!requestBody) {
                return '';
            }

            return `
                <h2>Request Body</h2>
                <pre>${escapeHtml(JSON.stringify(requestBody.content?.['application/json']?.schema || {}, null, 2))}</pre>
            `;
        }

        function renderResponses(responses) {
            const rows = Object.keys(responses).sort().map(status => `
                <tr>
                    <td><code>${escapeHtml(status)}</code></td>
                    <td>${escapeHtml(responses[status].description || '')}</td>
                </tr>
            `).join('');

            return `
                <h2>Responses</h2>
                <table>
                    <thead><tr><th>Status</th><th>Description</th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        function schemaLabel(schema) {
            const type = schema.type || 'string';
            return schema.format ? `${type}:${schema.format}` : type;
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
    </script>
</body>
</html>
HTML;
    }
}
