<?php

namespace Shift\OpenApi;

final class OpenApiLivePage
{
    public function render(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ShiftPHP OpenAPI</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f7f9fc;
            --panel: #ffffff;
            --text: #17202a;
            --muted: #657386;
            --line: #d9e1ea;
            --accent: #d43c2f;
            --get: #0f7b6c;
            --post: #1f63b5;
            --put: #9a6700;
            --patch: #7c3aed;
            --delete: #b42318;
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

        h1 {
            margin: 0 0 0.35rem;
            font-size: 2rem;
            letter-spacing: 0;
        }

        header p {
            margin: 0;
            color: var(--muted);
        }

        main {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2rem 1rem 4rem;
        }

        .operation {
            margin: 0 0 1rem;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--panel);
            overflow: hidden;
        }

        .operation summary {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            cursor: pointer;
        }

        .method {
            min-width: 4.6rem;
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

        .path {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 0.95rem;
            overflow-wrap: anywhere;
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
            background: #101827;
            color: #f8fafc;
        }

        .empty {
            padding: 1rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <header>
        <h1 id="title">ShiftPHP OpenAPI</h1>
        <p id="subtitle">Loading OpenAPI document...</p>
    </header>
    <main id="app"></main>

    <script>
        const app = document.querySelector('#app');
        const title = document.querySelector('#title');
        const subtitle = document.querySelector('#subtitle');

        fetch('openapi.json')
            .then(response => response.json())
            .then(render)
            .catch(error => {
                subtitle.textContent = 'Unable to load openapi.json';
                app.innerHTML = `<pre>${escapeHtml(String(error))}</pre>`;
            });

        function render(document) {
            title.textContent = document.info?.title || 'ShiftPHP OpenAPI';
            subtitle.textContent = `OpenAPI ${document.openapi || ''} · ${document.info?.version || ''}`;

            const paths = document.paths || {};
            const operations = [];

            Object.keys(paths).sort().forEach(path => {
                Object.keys(paths[path]).sort().forEach(method => {
                    operations.push({ path, method, operation: paths[path][method] });
                });
            });

            if (operations.length === 0) {
                app.innerHTML = '<p class="empty">No operations found.</p>';
                return;
            }

            app.innerHTML = operations.map(renderOperation).join('');
        }

        function renderOperation(item) {
            const operation = item.operation;
            const parameters = operation.parameters || [];
            const responses = operation.responses || {};

            return `
                <details class="operation" open>
                    <summary>
                        <span class="method ${item.method}">${item.method}</span>
                        <span class="path">${escapeHtml(item.path)}</span>
                    </summary>
                    <div class="details">
                        <h2>Operation</h2>
                        <p><code>${escapeHtml(operation.operationId || '')}</code></p>
                        ${renderParameters(parameters)}
                        ${renderRequestBody(operation.requestBody)}
                        ${renderResponses(responses)}
                    </div>
                </details>
            `;
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
                    <td>${escapeHtml(parameter.schema?.type || 'string')}</td>
                </tr>
            `).join('');

            return `
                <h2>Parameters</h2>
                <table>
                    <thead><tr><th>Name</th><th>In</th><th>Required</th><th>Type</th></tr></thead>
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
