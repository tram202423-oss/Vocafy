@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50/70 py-8 px-4 sm:px-6 lg:px-8" x-data="writingAiApp()">
    <div class="max-w-7xl mx-auto space-y-6">

        {{-- Header Section --}}
        <div class="bg-gradient-to-r from-blue-700 via-indigo-700 to-purple-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-indigo-500/10 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -top-10 w-48 h-48 bg-blue-400/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-xs font-semibold text-blue-100 uppercase tracking-wider">
                        <svg class="w-4 h-4 text-amber-300" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Luyện viết & Chấm điểm thông minh AI
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-extrabold tracking-tight">
                        Vocafy Writing AI Evaluator
                    </h1>
                    <p class="text-blue-100 text-sm sm:text-base leading-relaxed">
                        Tự nhập đề bài và bài làm của bạn để nhận đánh giá chi tiết theo tiêu chuẩn chuẩn quốc tế: chấm điểm Band score, phân tích ngữ pháp, gợi ý từ vựng nâng cao và bài mẫu tối ưu.
                    </p>
                </div>

                {{-- Action Switcher when viewing results --}}
                <template x-if="stage === 'result'">
                    <div class="flex flex-wrap items-center gap-3">
                        <button @click="stage = 'input'" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-medium text-sm rounded-xl border border-white/20 transition-all flex items-center gap-2 backdrop-blur-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Chỉnh sửa bài làm
                        </button>
                        <button @click="resetForm()" class="px-4 py-2.5 bg-white text-blue-700 hover:bg-blue-50 font-semibold text-sm rounded-xl transition-all shadow-lg shadow-black/10 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo bài viết mới
                        </button>
                    </div>
                </template>
            </div>
        </div>

        {{-- MAIN INPUT STAGE --}}
        <div x-show="stage === 'input'" x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                {{-- Left Column / Form Settings & Presets (5 cols) --}}
                <div class="lg:col-span-5 space-y-6">
                    
                    {{-- Card 1: Type & Settings --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-2.5 font-bold text-slate-800">
                                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm">1</div>
                                <span>Cấu hình & Loại đề bài</span>
                            </div>
                            <span class="text-xs px-2.5 py-1 bg-slate-100 text-slate-600 font-medium rounded-full">Tùy chọn</span>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-2.5">
                                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider">Chọn kỳ thi & Dạng bài</label>
                                <span class="text-[11px] text-blue-600 font-medium" x-text="examSummary"></span>
                            </div>

                            {{-- Exam Category Tabs --}}
                            <div class="grid grid-cols-2 gap-2 mb-3">
                                <button type="button" @click="selectExamCategory('ielts_academic')"
                                        :class="examCategory === 'ielts_academic' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20' : 'bg-slate-100 text-slate-700 font-medium hover:bg-slate-200'"
                                        class="p-2.5 rounded-xl text-xs transition-all text-center flex flex-col items-center justify-center">
                                    <span>IELTS Academic</span>
                                    <span class="text-[10px] opacity-80 font-normal">2 tasks • 60 phút</span>
                                </button>
                                <button type="button" @click="selectExamCategory('ielts_general')"
                                        :class="examCategory === 'ielts_general' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20' : 'bg-slate-100 text-slate-700 font-medium hover:bg-slate-200'"
                                        class="p-2.5 rounded-xl text-xs transition-all text-center flex flex-col items-center justify-center">
                                    <span>IELTS General</span>
                                    <span class="text-[10px] opacity-80 font-normal">2 tasks • 60 phút</span>
                                </button>
                                <button type="button" @click="selectExamCategory('toeic')"
                                        :class="examCategory === 'toeic' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20' : 'bg-slate-100 text-slate-700 font-medium hover:bg-slate-200'"
                                        class="p-2.5 rounded-xl text-xs transition-all text-center flex flex-col items-center justify-center">
                                    <span>TOEIC Writing</span>
                                    <span class="text-[10px] opacity-80 font-normal">Email & Essay • ~60 phút</span>
                                </button>
                                <button type="button" @click="selectExamCategory('toefl')"
                                        :class="examCategory === 'toefl' ? 'bg-blue-600 text-white font-bold shadow-md shadow-blue-500/20' : 'bg-slate-100 text-slate-700 font-medium hover:bg-slate-200'"
                                        class="p-2.5 rounded-xl text-xs transition-all text-center flex flex-col items-center justify-center">
                                    <span>TOEFL iBT</span>
                                    <span class="text-[10px] opacity-80 font-normal">2 tasks • ~30 phút</span>
                                </button>
                            </div>

                            {{-- Task Options Grid per Exam Category --}}
                            <div class="pt-1">
                                {{-- IELTS Academic Tasks --}}
                                <template x-if="examCategory === 'ielts_academic'">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <button type="button" @click="essayType = 'ielts_academic_task1'"
                                                :class="essayType === 'ielts_academic_task1' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Task 1: Biểu đồ</span>
                                                <span class="text-[11px] opacity-75">Mô tả dữ liệu / sơ đồ</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 150 từ • 20 phút</span>
                                        </button>
                                        <button type="button" @click="essayType = 'ielts_academic_task2'"
                                                :class="essayType === 'ielts_academic_task2' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Task 2: Essay</span>
                                                <span class="text-[11px] opacity-75">Bài luận tranh luận</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 250 từ • 40 phút</span>
                                        </button>
                                    </div>
                                </template>

                                {{-- IELTS General Tasks --}}
                                <template x-if="examCategory === 'ielts_general'">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <button type="button" @click="essayType = 'ielts_general_task1'"
                                                :class="essayType === 'ielts_general_task1' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Task 1: Viết thư</span>
                                                <span class="text-[11px] opacity-75">Letter trang trọng / cá nhân</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 150 từ • 20 phút</span>
                                        </button>
                                        <button type="button" @click="essayType = 'ielts_general_task2'"
                                                :class="essayType === 'ielts_general_task2' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Task 2: General Essay</span>
                                                <span class="text-[11px] opacity-75">Bài luận quan điểm</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 250 từ • 40 phút</span>
                                        </button>
                                    </div>
                                </template>

                                {{-- TOEIC Writing Tasks --}}
                                <template x-if="examCategory === 'toeic'">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <button type="button" @click="essayType = 'toeic_email'"
                                                :class="essayType === 'toeic_email' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Trả lời Email</span>
                                                <span class="text-[11px] opacity-75">Phản hồi yêu cầu công việc</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">~100 từ • 10-15 phút</span>
                                        </button>
                                        <button type="button" @click="essayType = 'toeic_essay'"
                                                :class="essayType === 'toeic_essay' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Opinion Essay</span>
                                                <span class="text-[11px] opacity-75">Bài luận quan điểm cá nhân</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 300 từ • 30 phút</span>
                                        </button>
                                    </div>
                                </template>

                                {{-- TOEFL iBT Tasks --}}
                                <template x-if="examCategory === 'toefl'">
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <button type="button" @click="essayType = 'toefl_integrated'"
                                                :class="essayType === 'toefl_integrated' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Integrated Task</span>
                                                <span class="text-[11px] opacity-75">Tổng hợp bài đọc & nghe</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">150-225 từ • 20 phút</span>
                                        </button>
                                        <button type="button" @click="essayType = 'toefl_academic_discussion'"
                                                :class="essayType === 'toefl_academic_discussion' ? 'bg-blue-50 border-blue-600 text-blue-800 ring-2 ring-blue-500/20 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'"
                                                class="p-3 text-left border rounded-xl transition-all text-xs flex flex-col justify-between gap-1">
                                            <div>
                                                <span class="font-bold block text-xs sm:text-sm">Academic Discussion</span>
                                                <span class="text-[11px] opacity-75">Thảo luận học thuật online</span>
                                            </div>
                                            <span class="text-[10px] font-bold text-blue-600">≥ 100 từ • 10 phút</span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Preset sample prompts selector --}}
                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hoặc chọn đề mẫu nhanh</label>
                                <span class="text-[11px] text-blue-600 cursor-pointer hover:underline" @click="loadRandomPreset()">Đổi đề khác</span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="(preset, idx) in presets" :key="idx">
                                    <button type="button" 
                                            @click="applyPreset(preset)" 
                                            class="w-full text-left p-2.5 bg-slate-50 hover:bg-blue-50/60 border border-slate-200/80 hover:border-blue-300 rounded-xl transition-all text-xs text-slate-700 group flex items-start justify-between gap-2">
                                        <div class="line-clamp-2">
                                            <span class="font-semibold text-blue-600" x-text="preset.tag + ':'"></span>
                                            <span x-text="preset.title"></span>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 group-hover:text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right Column / Topic Prompt & Student Essay Submission (7 cols) --}}
                <div class="lg:col-span-7 space-y-6">
                    
                    {{-- Card 2: Topic Prompt Text Input --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <label for="prompt-input" class="flex items-center gap-2 font-bold text-slate-800 text-sm">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">2</div>
                                <span>Nhập Đề bài / Yêu cầu bài viết</span>
                            </label>
                            <button type="button" @click="topic = ''; removeImage();" x-show="topic.length > 0 || imagePreview !== null" class="text-xs text-slate-400 hover:text-red-500 transition-colors">
                                Xóa đề & ảnh
                            </button>
                        </div>
                        <div>
                            <textarea id="prompt-input" 
                                      x-model="topic" 
                                      rows="4"
                                      placeholder="Ví dụ: Some people believe that technology has made the world a better place, while others argue it has caused more harm than good. Discuss both views and give your opinion."
                                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none resize-y placeholder:text-slate-400"></textarea>
                        </div>

                        {{-- Image Upload Area for Charts / Diagrams --}}
                        <div class="pt-1">
                            <input type="file" id="chart-image-input" @change="handleImageUpload($event)" accept="image/*" class="hidden">
                            
                            <template x-if="!imagePreview">
                                <label for="chart-image-input" class="flex items-center justify-center gap-2 p-3 border-2 border-dashed border-slate-200 hover:border-blue-400 rounded-xl cursor-pointer bg-slate-50/60 hover:bg-blue-50/40 transition-all text-slate-600 text-xs font-medium group">
                                    <svg class="w-5 h-5 text-slate-400 group-hover:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span>Đính kèm ảnh biểu đồ / sơ đồ đề bài (Tùy chọn - PNG, JPG)</span>
                                </label>
                            </template>

                            <template x-if="imagePreview">
                                <div class="relative group inline-block rounded-xl overflow-hidden border border-slate-200 shadow-sm max-h-52 bg-slate-50 p-2">
                                    <img :src="imagePreview" alt="Biểu đồ đề bài" class="max-h-48 rounded-lg object-contain" />
                                    <div class="absolute inset-0 bg-slate-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded-xl">
                                        <button type="button" @click="removeImage()" class="px-3 py-1.5 bg-red-600 text-white font-medium rounded-lg text-xs hover:bg-red-700 transition-colors flex items-center gap-1 shadow">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Xóa ảnh
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-400">
                            <span x-text="topic.length + ' ký tự'"></span>
                            <span x-show="imagePreview !== null" class="text-blue-600 font-semibold flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Đã đính kèm ảnh
                            </span>
                        </div>
                    </div>

                    {{-- Card 3: Student Essay Submission --}}
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                        <div class="space-y-4">
                            {{-- Header --}}
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-2.5 font-bold text-slate-800">
                                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm">3</div>
                                    <div>
                                        <span class="block">Bài làm của bạn (Submission)</span>
                                        <span class="text-xs font-normal text-slate-500">Soạn thảo hoặc dán bài viết của bạn tại đây</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" 
                                            @click="insertSampleEssay()" 
                                            class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 font-medium rounded-lg text-xs transition-colors flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        Dán bài mẫu test
                                    </button>
                                    <button type="button" 
                                            @click="essay = ''" 
                                            x-show="essay.length > 0"
                                            class="px-2.5 py-1.5 text-slate-400 hover:text-red-500 text-xs transition-colors">
                                        Xóa bài
                                    </button>
                                </div>
                            </div>

                            {{-- Real-time Word & Character Counter Stats Bar --}}
                            <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-100 text-xs text-slate-600">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        <span>Số từ: <strong class="text-slate-800 font-bold text-sm" x-text="wordCount">0</strong></span>
                                    </div>
                                    <div class="h-3.5 w-px bg-slate-200"></div>
                                    <span>Ký tự: <strong class="text-slate-700" x-text="essay.length">0</strong></span>
                                    <div class="h-3.5 w-px bg-slate-200"></div>
                                    <span>Thời gian đọc: <strong class="text-slate-700" x-text="readingTime + ' phút'"></strong></span>
                                </div>
                                
                                {{-- Target Indicator --}}
                                <div class="hidden sm:flex items-center gap-1.5">
                                    <span class="text-slate-400">Khuyến nghị:</span>
                                    <span class="font-semibold text-slate-700" x-text="recommendedWords"></span>
                                </div>
                            </div>

                            {{-- Essay Textarea --}}
                            <div class="relative">
                                <textarea id="essay-input"
                                          x-model="essay" 
                                          rows="12"
                                          placeholder="Viết bài làm của bạn ở đây... (Hoặc click nút 'Dán bài mẫu test' ở trên để thử nghiệm nhanh)"
                                          class="w-full px-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-xl text-sm sm:text-base text-slate-800 leading-relaxed focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition-all outline-none resize-y placeholder:text-slate-400 font-mono"></textarea>
                            </div>
                        </div>

                        {{-- Submit Button Footer --}}
                        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <p class="text-xs text-slate-500">
                                💡 Chấm điểm theo chuẩn IELTS / TOEIC / TOEFL với mô hình phân tích ngôn ngữ nâng cao.
                            </p>

                            <button type="button" 
                                    @click="startAnalysis()" 
                                    :disabled="!isValidSubmission"
                                    :class="isValidSubmission ? 'bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white shadow-lg shadow-indigo-500/25 active:scale-98 cursor-pointer' : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                                    class="w-full sm:w-auto px-7 py-3.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                <span>Nộp bài & Chấm điểm AI</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        {{-- ANALYZING / LOADING ANIMATION STAGE --}}
        <div x-show="stage === 'analyzing'" x-cloak x-transition:enter="transition ease-out duration-300" class="bg-white rounded-3xl p-8 sm:p-14 shadow-xl border border-slate-100 text-center max-w-3xl mx-auto space-y-8 my-10">
            <div class="relative w-28 h-28 mx-auto">
                {{-- Glowing aura --}}
                <div class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 animate-ping opacity-20"></div>
                <div class="relative w-full h-full rounded-full bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <svg class="w-12 h-12 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>

            <div class="space-y-3">
                <h3 class="text-2xl font-bold text-slate-800" x-text="loadingSteps[currentStep].title"></h3>
                <p class="text-slate-500 text-sm max-w-md mx-auto" x-text="loadingSteps[currentStep].desc"></p>
            </div>

            {{-- Loading steps indicators --}}
            <div class="max-w-md mx-auto space-y-3 text-left">
                <template x-for="(step, idx) in loadingSteps" :key="idx">
                    <div class="flex items-center gap-3 p-3 rounded-xl transition-all"
                         :class="idx === currentStep ? 'bg-blue-50/80 border border-blue-200 text-blue-800' : (idx < currentStep ? 'bg-emerald-50/60 border border-emerald-100 text-emerald-700' : 'opacity-40 bg-slate-50')">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                             :class="idx === currentStep ? 'bg-blue-600 text-white' : (idx < currentStep ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500')">
                            <span x-show="idx < currentStep">✓</span>
                            <span x-show="idx >= currentStep" x-text="idx + 1"></span>
                        </div>
                        <span class="text-xs font-medium" x-text="step.title"></span>
                    </div>
                </template>
            </div>

            {{-- Progress Bar --}}
            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden max-w-md mx-auto">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-full transition-all duration-500 rounded-full" :style="'width: ' + progressPercent + '%'"></div>
            </div>
        </div>

        {{-- RESULT / EVALUATION REPORT STAGE --}}
        <div x-show="stage === 'result'" x-cloak x-transition:enter="transition ease-out duration-300 transform opacity-0 translate-y-2" class="space-y-6">
            
            {{-- Top Result Summary Banner --}}
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                
                {{-- Overall Score Badge (Left 4 cols) --}}
                <div class="lg:col-span-4 bg-gradient-to-br from-slate-900 to-indigo-950 text-white rounded-2xl p-6 text-center space-y-4 relative overflow-hidden shadow-xl">
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500/10 rounded-full blur-2xl"></div>
                    <span class="text-xs font-semibold uppercase tracking-widest text-indigo-300">Điểm tổng quan (Overall Score)</span>
                    
                    <div class="flex items-center justify-center gap-2">
                        <span class="text-6xl font-black text-amber-400" x-text="result.overallScore">7.5</span>
                        <span class="text-slate-400 text-2xl font-bold" x-text="'/ ' + (result.maxScore || 9.0)"></span>
                    </div>

                    <div class="inline-block px-3 py-1 bg-amber-400/20 border border-amber-400/30 text-amber-300 text-xs font-semibold rounded-full" x-text="result.bandLevel">
                        Good User (C1 Advanced)
                    </div>

                    <div class="pt-3 border-t border-white/10 grid grid-cols-2 gap-2 text-xs text-slate-300">
                        <div>
                            <span class="block text-slate-400">Số từ</span>
                            <strong class="text-white font-bold" x-text="wordCount"></strong>
                        </div>
                        <div>
                            <span class="block text-slate-400">Độ phức tạp</span>
                            <strong class="text-white font-bold" x-text="result.complexity">B2 - C1</strong>
                        </div>
                    </div>
                </div>

                {{-- Criteria Breakdown Grid (Right 8 cols) --}}
                <div class="lg:col-span-8 space-y-4">
                    <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Chi tiết các tiêu chuẩn chấm điểm</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="(crit, idx) in result.criteria" :key="idx">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-700" x-text="crit.name"></span>
                                    <span class="text-sm font-black text-blue-600" x-text="crit.score + ' / ' + (crit.maxScore || result.maxScore || 9.0)"></span>
                                </div>
                                <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                                    <div class="bg-blue-600 h-full rounded-full transition-all duration-1000" :style="'width: ' + Math.min(100, (crit.score / (crit.maxScore || result.maxScore || 9.0) * 100)) + '%'"></div>
                                </div>
                                <p class="text-[11px] text-slate-500 leading-snug" x-text="crit.comment"></p>
                            </div>
                        </template>
                    </div>
                </div>

            </div>

            {{-- Main Tabs Navigation --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-2 flex items-center gap-2 overflow-x-auto">
                <button @click="activeTab = 'analysis'" 
                        :class="activeTab === 'analysis' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Tổng quan & Nhận xét
                </button>
                
                <button @click="activeTab = 'corrections'" 
                        :class="activeTab === 'corrections' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Sửa lỗi chi tiết (<span x-text="result.corrections.length"></span>)
                </button>

                <button @click="activeTab = 'sample'" 
                        :class="activeTab === 'sample' ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20' : 'text-slate-600 hover:bg-slate-100'"
                        class="px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all whitespace-nowrap flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Bài viết mẫu nâng cấp (High Band)
                </button>
            </div>

            {{-- TAB 1: General Strengths & Improvements --}}
            <div x-show="activeTab === 'analysis'" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                {{-- Strengths --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-emerald-100 space-y-4">
                    <div class="flex items-center gap-2.5 font-bold text-emerald-800 border-b border-emerald-50 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h4 class="text-base">Điểm mạnh bài viết (Strengths)</h4>
                    </div>

                    <ul class="space-y-3">
                        <template x-for="(item, idx) in result.strengths" :key="idx">
                            <li class="flex items-start gap-3 text-xs sm:text-sm text-slate-700">
                                <span class="text-emerald-500 font-bold mt-0.5">✓</span>
                                <span x-text="item"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Areas for Improvement --}}
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-amber-100 space-y-4">
                    <div class="flex items-center gap-2.5 font-bold text-amber-800 border-b border-amber-50 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h4 class="text-base">Gợi ý cải thiện (Areas for Growth)</h4>
                    </div>

                    <ul class="space-y-3">
                        <template x-for="(item, idx) in result.improvements" :key="idx">
                            <li class="flex items-start gap-3 text-xs sm:text-sm text-slate-700">
                                <span class="text-amber-500 font-bold mt-0.5">→</span>
                                <span x-text="item"></span>
                            </li>
                        </template>
                    </ul>
                </div>

            </div>

            {{-- TAB 2: Detailed Line-by-line Corrections --}}
            <div x-show="activeTab === 'corrections'" class="space-y-4">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200/80 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h4 class="font-bold text-slate-800 text-base">Chi tiết lỗi & Gợi ý từ vựng trong bài</h4>
                        <span class="text-xs text-slate-500">Tìm thấy <strong class="text-blue-600" x-text="result.corrections.length"></strong> điểm cần lưu ý</span>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(item, idx) in result.corrections" :key="idx">
                            <div class="p-4 sm:p-5 rounded-2xl border transition-all space-y-3"
                                 :class="{
                                    'bg-red-50/40 border-red-200': item.type === 'grammar',
                                    'bg-amber-50/40 border-amber-200': item.type === 'vocab',
                                    'bg-blue-50/40 border-blue-200': item.type === 'style'
                                 }">
                                
                                <div class="flex items-center justify-between">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider"
                                          :class="{
                                            'bg-red-100 text-red-700': item.type === 'grammar',
                                            'bg-amber-100 text-amber-700': item.type === 'vocab',
                                            'bg-blue-100 text-blue-700': item.type === 'style'
                                          }"
                                          x-text="item.badge">
                                    </span>
                                    <span class="text-xs text-slate-400" x-text="'Vị trí: Đoạn ' + item.paragraph"></span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs sm:text-sm">
                                    <div class="p-3 bg-white/80 rounded-xl border border-slate-200/60 space-y-1">
                                        <span class="text-[11px] font-semibold text-red-500 uppercase block">Câu gốc của bạn:</span>
                                        <p class="text-slate-800 line-through decoration-red-400" x-text="item.original"></p>
                                    </div>
                                    <div class="p-3 bg-white/80 rounded-xl border border-emerald-200 space-y-1">
                                        <span class="text-[11px] font-semibold text-emerald-600 uppercase block">Gợi ý sửa đổi:</span>
                                        <p class="text-emerald-800 font-medium" x-text="item.suggestion"></p>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-600 italic bg-white/50 p-2.5 rounded-lg border border-slate-100" x-text="'💡 Giải thích: ' + item.reason"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- TAB 3: Model Essay --}}
            <div x-show="activeTab === 'sample'" class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200/80 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
                    <div>
                        <h4 class="font-bold text-slate-800 text-lg">Bài viết mẫu nâng cấp tiêu chuẩn cao</h4>
                        <p class="text-xs text-slate-500">Phiên bản đã được tối ưu cấu trúc, vốn từ vựng học thuật & câu ghép phức tạp</p>
                    </div>
                    <button type="button" 
                            @click="copySampleEssay()" 
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-xs rounded-xl transition-colors flex items-center gap-1.5 self-start">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 01-2-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="copied ? 'Đã sao chép!' : 'Sao chép bài viết'"></span>
                    </button>
                </div>

                <div class="bg-slate-50/70 rounded-2xl p-6 border border-slate-200/80 leading-relaxed font-serif text-slate-800 text-sm sm:text-base whitespace-pre-line space-y-4" x-text="result.sampleEssay">
                </div>
            </div>

        </div>

    </div>
</div>

@endsection