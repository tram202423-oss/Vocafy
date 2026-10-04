<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:key="ielts-map-picker-{{ $getStatePath() }}"
        wire:ignore
        x-data="{
            questionsPath: @js($questionsPath),
            imagePath: @js($imagePath),
            activeKey: null,
            get questions() {
                return $wire.$get(this.questionsPath) ?? {};
            },
            get items() {
                return Object.entries(this.questions ?? {})
                    .filter(([, question]) => question && typeof question === 'object')
                    .map(([key, question]) => ({ key, ...question }))
                    .sort((a, b) => Number(a.question_number || 0) - Number(b.question_number || 0));
            },
            get selectedKey() {
                if (this.activeKey !== null && this.questions?.[this.activeKey]) return this.activeKey;
                return this.items.find(question => !this.hasPosition(question))?.key ?? this.items[0]?.key ?? null;
            },
            get selectedQuestion() {
                return this.selectedKey === null ? null : this.questions[this.selectedKey];
            },
            hasPosition(question) {
                return question?.drop_x !== null && question?.drop_x !== ''
                    && question?.drop_y !== null && question?.drop_y !== ''
                    && Number.isFinite(Number(question.drop_x)) && Number.isFinite(Number(question.drop_y))
                    && Number(question.drop_x) >= 0 && Number(question.drop_x) <= 100
                    && Number(question.drop_y) >= 0 && Number(question.drop_y) <= 100;
            },
            label(question) {
                return `Câu ${question?.question_number || '?'}`;
            },
            updatePosition(key, x, y) {
                if (!this.questions[key]) return;
                // Keep the repeater's other fields intact and update form state before Save.
                $wire.$set(`${this.questionsPath}.${key}.drop_x`, x, false);
                $wire.$set(`${this.questionsPath}.${key}.drop_y`, y, false);
            },
            place(event) {
                const key = this.selectedKey;
                if (key === null) return;
                const rect = event.currentTarget.getBoundingClientRect();
                const x = Math.max(0, Math.min(100, Number((((event.clientX - rect.left) / rect.width) * 100).toFixed(2))));
                const y = Math.max(0, Math.min(100, Number((((event.clientY - rect.top) / rect.height) * 100).toFixed(2))));
                this.updatePosition(key, x, y);
                this.activeKey = this.items.find(question => question.key !== key && !this.hasPosition(question))?.key ?? key;
            },
            clear(key) {
                this.updatePosition(key, null, null);
                this.activeKey = key;
            },
        }"
        style="display: grid; gap: 0.75rem;"
    >
        <p style="font-size: 0.875rem; color: #64748b;">Chọn một câu trong danh sách rồi bấm vị trí trên ảnh. Sau mỗi lần đặt, hệ thống tự chọn câu tiếp theo chưa có vị trí.</p>

        <p x-show="items.length === 0" x-cloak style="font-size: 0.875rem; color: #b45309;">Thêm câu hỏi hoặc dùng Tạo dãy câu hỏi trước khi đặt vị trí.</p>

        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <template x-for="question in items" :key="question.key">
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <button
                        type="button"
                        @click="activeKey = question.key"
                        :aria-pressed="selectedKey === question.key"
                        :style="`padding: 0.35rem 0.6rem; border-radius: 0.5rem; border: 1px solid ${selectedKey === question.key ? '#2563eb' : '#cbd5e1'}; background: ${selectedKey === question.key ? '#eff6ff' : 'white'}; color: ${selectedKey === question.key ? '#1d4ed8' : '#334155'}; cursor: pointer; font-size: 0.875rem;`"
                    >
                        <span x-text="label(question)"></span>
                        <span x-text="hasPosition(question) ? '✓' : '· Chưa đặt'"></span>
                    </button>
                    <button
                        type="button"
                        x-show="hasPosition(question)"
                        @click="clear(question.key)"
                        :aria-label="`Xóa vị trí ${label(question)}`"
                        title="Xóa vị trí"
                        style="color: #dc2626; padding: 0.25rem; font-size: 0.875rem;"
                    >×</button>
                </div>
            </template>
        </div>

        <p x-show="selectedQuestion" x-cloak style="font-size: 0.875rem; font-weight: 600; color: #1d4ed8;" x-text="selectedQuestion ? `Đang đặt: ${label(selectedQuestion)}` : ''"></p>

        <div style="max-width: 48rem; overflow: auto; border: 1px solid #cbd5e1; border-radius: 0.75rem; background: white; padding: 0.5rem;">
            <div style="position: relative; width: fit-content; max-width: 100%;">
                <button
                    type="button"
                    @click="place($event)"
                    :disabled="selectedKey === null"
                    :aria-label="selectedQuestion ? `Đặt vị trí cho ${label(selectedQuestion)} trên ảnh` : 'Thêm câu hỏi trước khi đặt vị trí'"
                    style="display: block; max-width: 100%; width: fit-content; padding: 0; border: 0; line-height: 0; cursor: crosshair;"
                >
                    <img :src="$wire.$get(imagePath)" alt="Ảnh bản đồ hoặc sơ đồ để đặt vị trí các câu hỏi" style="display: block; max-width: 100%; height: auto;">
                </button>
                <template x-for="question in items.filter(item => hasPosition(item))" :key="question.key">
                    <button
                        type="button"
                        @click="activeKey = question.key"
                        :aria-label="`Chọn lại vị trí ${label(question)}`"
                        :title="`${label(question)}: ${question.drop_x}% · ${question.drop_y}%`"
                        x-text="label(question)"
                        :style="`position: absolute; left: ${question.drop_x}%; top: ${question.drop_y}%; transform: translate(-50%, -50%); padding: 0.4rem 0.55rem; border-radius: 0.5rem; border: 2px solid ${selectedKey === question.key ? '#1d4ed8' : '#64748b'}; background: white; color: ${selectedKey === question.key ? '#1d4ed8' : '#334155'}; box-shadow: 0 2px 8px #0003; font-size: 0.75rem; font-weight: 700; line-height: 1; white-space: nowrap; cursor: pointer;`"
                    ></button>
                </template>
            </div>
        </div>

    </div>
</x-dynamic-component>
