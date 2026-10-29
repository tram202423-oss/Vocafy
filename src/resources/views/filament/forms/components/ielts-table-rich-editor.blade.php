<div
    x-data="{
        open: false,
        rows: 3,
        columns: 3,
        firstRowHeader: true,
        cells: [],
        toolbarObserver: null,
        init() {
            this.resizeCells();
            this.$nextTick(() => {
                this.installTableButton();
                this.toolbarObserver = new MutationObserver(() => this.installTableButton());
                this.toolbarObserver.observe(this.$el, { childList: true, subtree: true });
            });
        },
        destroy() {
            this.toolbarObserver?.disconnect();
        },
        installTableButton() {
            const toolbar = this.$el.querySelector('trix-toolbar .flex.gap-x-3');
            if (!toolbar || toolbar.querySelector('[data-ielts-insert-table]')) return;
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.ieltsInsertTable = 'true';
            button.textContent = '▦ Bảng';
            button.title = 'Tạo bảng trong đề bài';
            button.setAttribute('aria-label', 'Tạo bảng trong đề bài');
            button.style.cssText = 'flex:none;border:1px solid #cbd5e1;border-radius:6px;padding:4px 10px;font-size:13px;font-weight:600;background:transparent;cursor:pointer';
            button.addEventListener('click', () => { this.resizeCells(); this.open = true; });
            toolbar.appendChild(button);
        },
        resizeCells() {
            this.rows = Math.max(1, Math.min(12, Number(this.rows) || 1));
            this.columns = Math.max(1, Math.min(8, Number(this.columns) || 1));
            this.cells = Array.from({ length: this.rows }, (_, row) =>
                Array.from({ length: this.columns }, (_, column) => this.cells[row]?.[column] ?? '')
            );
        },
        escapeCell(value) {
            return String(value).replace(/&/g, '&amp;amp;').replace(/</g, '&amp;lt;')
                .replace(/>/g, '&amp;gt;').replace(/&quot;/g, '&amp;quot;').replace(/'/g, '&amp;#39;')
                .replace(/\n/g, '<br>');
        },
        tableHtml() {
            let html = '<table class=&quot;ielts-created-table&quot; style=&quot;border-collapse:collapse;width:100%&quot;><tbody>';
            for (let row = 0; row < this.rows; row++) {
                html += '<tr>';
                for (let column = 0; column < this.columns; column++) {
                    const tag = this.firstRowHeader && row === 0 ? 'th' : 'td';
                    const value = this.escapeCell(this.cells[row]?.[column] ?? '') || '&amp;nbsp;';
                    html += '<' + tag + ' style=&quot;border:1px solid #94a3b8;padding:8px;vertical-align:top&quot;>' + value + '</' + tag + '>';
                }
                html += '</tr>';
            }
            return html + '</tbody></table>';
        },
        insertTable() {
            const editor = this.$root.querySelector('trix-editor');
            if (!editor?.editor || !window.Trix) return;
            editor.editor.insertAttachment(new window.Trix.Attachment({
                content: this.tableHtml(),
                contentType: 'text/html'
            }));
            this.open = false;
        }
    }"
    x-on:keydown.escape.window="open = false"
>
    @include('filament-forms::components.rich-editor')

    <div x-show="open" x-cloak x-on:click.self="open = false"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4">
        <div role="dialog" aria-modal="true" aria-label="Tạo bảng cho đề bài"
             class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Tạo bảng</h3>
                <button type="button" x-on:click="open = false" aria-label="Đóng" class="rounded px-2 py-1 text-gray-600">✕</button>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Nhập nội dung từng ô. Dùng [blank_1], [blank_2]… để đặt ô trả lời trong bảng.</p>
            <div class="mt-4 flex flex-wrap items-end gap-4">
                <label class="text-sm font-medium">Số hàng
                    <input type="number" min="1" max="12" x-model.number="rows" x-on:change="resizeCells()"
                           class="mt-1 block w-24 rounded-lg border-gray-300 dark:bg-gray-800">
                </label>
                <label class="text-sm font-medium">Số cột
                    <input type="number" min="1" max="8" x-model.number="columns" x-on:change="resizeCells()"
                           class="mt-1 block w-24 rounded-lg border-gray-300 dark:bg-gray-800">
                </label>
                <label class="flex items-center gap-2 pb-2 text-sm">
                    <input type="checkbox" x-model="firstRowHeader" class="rounded border-gray-300"> Hàng đầu là tiêu đề
                </label>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full border-collapse">
                    <tbody>
                        <template x-for="row in rows" :key="row">
                            <tr>
                                <template x-for="column in columns" :key="column">
                                    <td class="border border-gray-300 p-1 dark:border-gray-600">
                                        <textarea x-model="cells[row - 1][column - 1]" rows="2"
                                                  :aria-label="'Hàng ' + row + ', cột ' + column"
                                                  class="block min-w-36 w-full rounded border-gray-300 text-sm dark:bg-gray-800"></textarea>
                                    </td>
                                </template>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="open = false"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Hủy</button>
                <button type="button" x-on:click="insertTable()"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Chèn bảng</button>
            </div>
        </div>
    </div>
</div>

