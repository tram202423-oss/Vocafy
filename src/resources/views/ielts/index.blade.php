@extends('layouts.app')

@section('title', 'Hệ Thống Giả Lập Thi IELTS Chuẩn IDP / British Council')

@section('content')
<div class="bg-gradient-to-b from-blue-50/70 via-white to-slate-50 text-slate-900">

    {{-- Hero --}}
    <section class="py-16 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-xs font-medium mb-6">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                Giả lập thi IELTS trên máy tính
            </div>

            <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-slate-900 leading-tight max-w-4xl mx-auto">
                Luyện thi IELTS chuẩn
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500">IDP &amp; British Council</span>
            </h1>

            <p class="mt-6 text-base sm:text-lg text-slate-600 leading-relaxed max-w-2xl mx-auto">
                Giao diện thi sát thực tế: chia đôi màn hình, highlight và ghi chú, đồng hồ đếm ngược,
                chấm điểm Band Score ngay khi nộp bài.
            </p>

            <div class="mt-8 flex flex-wrap justify-center gap-3 text-sm text-slate-600">
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
                    <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    Đủ 40 câu, đúng format
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    Highlight và ghi chú trực tiếp
                </div>
                <div class="flex items-center gap-2 bg-white px-4 py-2 rounded-full border border-slate-200 shadow-sm">
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    Giải thích và trích dẫn chi tiết
                </div>
            </div>
        </div>
    </section>

    <div class="px-4 sm:px-6 lg:px-8 pb-20">
        <div class="max-w-7xl mx-auto">

            {{-- Lịch sử thi gần đây --}}
            @if($userSubmissions && $userSubmissions->count() > 0)
                <section class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-5 pb-4 border-b border-slate-100">
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span>📊</span> Lịch sử bài thi gần đây của bạn
                        </h2>
                        <span class="text-xs text-slate-500">{{ $userSubmissions->count() }} lượt thi gần nhất</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($userSubmissions as $sub)
                            @php
                                $skillStyles = [
                                    'reading'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'listening' => 'bg-blue-50 text-blue-700 border-blue-100',
                                    'writing'   => 'bg-amber-50 text-amber-700 border-amber-100',
                                    'speaking'  => 'bg-purple-50 text-purple-700 border-purple-100',
                                ];
                                $skillClass = $skillStyles[$sub->skill] ?? 'bg-slate-50 text-slate-700 border-slate-200';
                            @endphp
                            <div class="bg-slate-50/60 border border-slate-200 rounded-xl p-4 flex flex-col justify-between hover:border-blue-300 hover:bg-white transition-colors">
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-3">
                                        <span class="px-2 py-0.5 rounded-md border font-semibold uppercase {{ $skillClass }}">
                                            {{ $sub->skill }}
                                        </span>
                                        <span class="text-slate-500">{{ $sub->completed_at ? $sub->completed_at->format('d/m/Y H:i') : 'Đang làm' }}</span>
                                    </div>
                                    <h3 class="font-bold text-slate-900 text-sm line-clamp-1 mb-2">
                                        {{ $sub->test?->title ?? ($sub->section?->title ?? 'Bài thi IELTS') }}
                                    </h3>
                                    <div class="flex items-center gap-4 text-xs text-slate-600 mb-4">
                                        <div>Đúng: <strong class="text-emerald-600">{{ $sub->raw_score }}/{{ $sub->total_questions }}</strong></div>
                                        <div>Band: <strong class="text-blue-600 text-sm font-black">{{ number_format($sub->band_score, 1) }}</strong></div>
                                    </div>
                                </div>
                                <a href="{{ route('ielts.exam.result', $sub->id) }}"
                                   class="w-full text-center py-2 px-3 bg-white hover:bg-blue-50 border border-slate-200 hover:border-blue-300 rounded-lg text-xs font-semibold text-slate-700 hover:text-blue-700 transition-colors">
                                    Xem lại bài làm và giải thích &rarr;
                                </a>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Danh sách đề thi --}}
            <section class="mt-14">
                <div class="mb-8">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 flex items-center gap-2">
                        <span>📚</span> Danh sách đề thi IELTS Academic &amp; General
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">Chọn một đề để vào phòng thi mô phỏng</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($tests as $test)
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between shadow-sm hover:border-blue-300 hover:shadow-lg hover:shadow-blue-500/10 transition-all duration-300 group">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wide bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ $test->type->label() }}
                                    </span>
                                    <span class="text-xs text-slate-500 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $test->duration_minutes }} phút
                                    </span>
                                </div>

                                <h3 class="text-lg font-bold text-slate-900 group-hover:text-blue-600 transition-colors mb-2">
                                    {{ $test->title }}
                                </h3>

                                <p class="text-sm text-slate-500 line-clamp-2 mb-5">
                                    {{ $test->description ?: 'Bộ đề giả lập hoàn chỉnh với 40 câu hỏi, tự động tính giờ, highlight và giải thích chi tiết từng câu.' }}
                                </p>

                                <div class="space-y-2 mb-6">
                                    @foreach($test->sections as $sec)
                                        <div class="flex items-center justify-between text-xs bg-slate-50 px-3 py-2 rounded-lg border border-slate-100">
                                            <span class="flex items-center gap-2 text-slate-700 font-medium">
                                                @if($sec->skill->value === 'reading')
                                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Reading (Đọc)
                                                @elseif($sec->skill->value === 'listening')
                                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> Listening (Nghe)
                                                @elseif($sec->skill->value === 'writing')
                                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Writing (Viết)
                                                @else
                                                    <span class="w-2 h-2 rounded-full bg-purple-500"></span> Speaking (Nói)
                                                @endif
                                            </span>
                                            <span class="text-slate-500">{{ $sec->total_questions }} câu • {{ $sec->time_limit_minutes }} phút</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100">
                                <a href="{{ route('ielts.tests.show', $test->slug) }}"
                                   class="w-full flex items-center justify-center gap-2 py-3 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/25 active:scale-[0.98] transition-all">
                                    <span>Vào phòng thi</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-12 text-center text-slate-500 bg-white rounded-2xl border border-dashed border-slate-300">
                            <p>Chưa có bộ đề nào được xuất bản.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>
@endsection