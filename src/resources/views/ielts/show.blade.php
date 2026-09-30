@extends('layouts.app')

@section('title', 'Chuẩn Bị Thi: ' . $test->title)

@section('content')
<div class="bg-gradient-to-b from-blue-50/70 via-white to-slate-50 min-h-screen py-10 px-4 sm:px-6 lg:px-8 text-slate-800 flex items-center justify-center">
    <div class="max-w-3xl w-full bg-white border border-slate-200 rounded-3xl p-6 sm:p-10 shadow-sm">
        {{-- Header --}}
        <div class="text-center border-b border-slate-100 pb-6 mb-6">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100 text-xs font-medium mb-3">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                IELTS Computer-Delivered Test
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900">
                {{ $test->title }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Phòng thi trực tuyến • Mô phỏng chuẩn hội đồng IDP / British Council
            </p>
        </div>

        {{-- Thông tin thí sinh dự thi --}}
        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 mb-6">
            <h2 class="text-xs font-bold text-slate-500 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                Thông tin thí sinh (Candidate Details)
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-slate-500 text-xs block">Họ và tên thí sinh:</span>
                    <strong class="text-slate-900">{{ Auth::check() ? Auth::user()->name : 'Candidate Guest' }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-xs block">Số báo danh (Candidate No):</span>
                    <strong class="text-blue-600 font-mono">IDP-{{ strtoupper(substr(md5(Auth::id() ?? session()->getId()), 0, 6)) }}</strong>
                </div>
                <div>
                    <span class="text-slate-500 text-xs block">Mã phòng thi:</span>
                    <strong class="text-emerald-600 font-mono">ROOM-01 (HN-EXAM)</strong>
                </div>
            </div>
        </div>

        {{-- Kiểm tra âm thanh tai nghe (Sound check) --}}
        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-200 mb-6" x-data="{ playing: false }">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <span>🎧</span> Kiểm tra tai nghe &amp; âm thanh
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Nhấp nút bên cạnh để nghe thử một đoạn âm thanh ngắn kiểm tra âm lượng tai nghe.</p>
                </div>
                <button type="button" @click="playing = !playing; $refs.audio.paused ? $refs.audio.play() : $refs.audio.pause()"
                    class="px-4 py-2 bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-700 rounded-xl text-xs font-semibold flex items-center gap-2 transition-colors border border-slate-200 hover:border-blue-300 flex-shrink-0">
                    <span x-show="!playing">🔊 Nghe thử</span>
                    <span x-show="playing" x-cloak class="text-blue-600">⏸ Dừng</span>
                </button>
                <audio x-ref="audio" @ended="playing = false" src="https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg" preload="none"></audio>
            </div>
        </div>

        {{-- Quy chế & Hướng dẫn thi chuẩn IDP/BC --}}
        <div class="space-y-3 mb-8 text-xs text-slate-600">
            <h2 class="text-sm font-bold text-slate-900">Quy chế &amp; hướng dẫn thi:</h2>
            <ul class="space-y-2 list-disc list-inside bg-slate-50 p-4 rounded-xl border border-slate-200 leading-relaxed">
                <li><strong class="text-slate-900">Thời gian làm bài:</strong> Bài thi gồm {{ $test->total_questions ?: $test->sections->sum('total_questions') }} câu hỏi, thời gian đếm ngược chính xác <span class="text-blue-600 font-bold">{{ $test->duration_minutes ?: $test->sections->sum('time_limit_minutes') }} phút</span>.</li>
                <li><strong class="text-slate-900">Thanh điều hướng câu hỏi:</strong> Bạn có thể dùng thanh số câu hỏi ở cuối màn hình hoặc các phím <em>Previous</em> và <em>Next</em> để di chuyển giữa các câu.</li>
                <li><strong class="text-slate-900">Đánh dấu Review:</strong> Nhấp nút <em>Review</em> để gắn cờ những câu bạn muốn kiểm tra lại trước khi nộp bài.</li>
                <li><strong class="text-slate-900">Tô sáng &amp; ghi chú (Highlight &amp; Notes):</strong> Bôi đen bất kỳ đoạn văn bản nào trên bài đọc để chọn <em>Highlight</em> hoặc <em>Note</em>.</li>
                <li><strong class="text-slate-900">Chế độ tập trung (Focus Mode):</strong> Không rời khỏi tab thi hoặc thoát toàn màn hình; hệ thống sẽ ghi nhận lịch sử chuyển tab.</li>
                <li><strong class="text-slate-900">Tự động nộp bài:</strong> Khi đồng hồ về 00:00, hệ thống sẽ tự động thu bài và lập tức tính điểm Band Score của bạn.</li>
            </ul>
        </div>

        {{-- Lựa chọn kỹ năng thi (Section Picker) --}}
        <form action="{{ route('ielts.tests.start', $test->slug) }}" method="POST" x-data="{ selectedSection: '{{ $test->sections->first()?->id }}' }">
            @csrf
            <div class="mb-6">
                <label class="text-sm font-bold text-slate-900 block mb-3">
                    Chọn phần thi / kỹ năng:
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    @foreach($test->sections as $sec)
                        <label class="p-3.5 rounded-2xl border-2 cursor-pointer flex flex-col justify-between transition-all"
                               :class="selectedSection == '{{ $sec->id }}' ? 'border-blue-500 bg-blue-50' : 'border-slate-200 bg-white hover:border-blue-300'">
                            <input type="radio" name="section_id" value="{{ $sec->id }}" x-model="selectedSection" class="sr-only">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    @if($sec->skill->value === 'listening') bg-blue-50 text-blue-700 border border-blue-100
                                    @elseif($sec->skill->value === 'reading') bg-emerald-50 text-emerald-700 border border-emerald-100
                                    @else bg-amber-50 text-amber-700 border border-amber-100 @endif">
                                    {{ $sec->skill->label() }}
                                </span>
                                <span class="text-xs text-slate-500">{{ $sec->time_limit_minutes }} phút</span>
                            </div>
                            <div class="font-bold text-sm text-slate-900 line-clamp-1 mb-1">{{ $sec->title }}</div>
                            <div class="text-[11px] text-slate-500">{{ $sec->total_questions }} câu hỏi • Chấm tự động</div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Nút bắt đầu thi --}}
            <button type="submit" class="w-full py-4 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-base rounded-2xl shadow-lg shadow-blue-600/25 active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Tôi đã sẵn sàng — bắt đầu bài thi</span>
            </button>
        </form>
    </div>
</div>
@endsection
