# IELTS Simulator — Rà soát và trạng thái sau sửa

**Ngày:** 02/10/2026. **Nguồn:** `/home/tram/Study/Vocafy` trong WSL.
**Branch:** `feature/IELTS-Simulator-System-Blueprint`. **HEAD:** `761e76d`.
Báo cáo đánh giá cả thay đổi chưa commit tại thời điểm rà soát, không chỉ code ở HEAD.

- [Tổng quan kỹ thuật](README.md)
- [Trạng thái dạng câu hỏi](QUESTION-TYPES.md)
- [Hướng dẫn admin](ADMIN-GUIDE.md)

## 1. Kết luận và phạm vi kiểm tra

Bản sửa ngày 02/10/2026 đã xử lý các lỗi ưu tiên trong luồng admin, bảo vệ lịch sử, lưu/nộp Speaking, autosave và chấm AI. Trạng thái hiện tại của từng hạng mục nằm ngay dưới tiêu đề IELTS-01…IELTS-16.

Các đoạn **Xác nhận / Bằng chứng / Cần làm / Hoàn thành khi** bên dưới giữ lại phát hiện và tiêu chí ban đầu để theo dõi. Chúng không có nghĩa là lỗi đó vẫn còn sau bản vá. Những tính năng chưa triển khai được ghi rõ ở dòng trạng thái.

### Triển khai và kiểm tra trong lượt sửa

- Migration `2026_10_02_000003_harden_ielts_submissions.php` đã chạy trên DB local: FK RESTRICT, phân công/người chấm/rubric/history, save_revision và word_limit_mode.
- Lỗi phát sinh khi xóa slug trên form chỉnh sửa bộ đề: DB từ chối giá trị NULL. Model đã tạo slug cho cả thao tác tạo và cập nhật; form hiển thị slug mới sau lưu. Đã xác nhận lưu hai trường hợp trong transaction và rollback dữ liệu thử.
- Đã build frontend bằng Vite và compile Blade; kiểm tra cú pháp PHP/JS. Không chạy PHPUnit hoặc dùng micro/Gemini thật trong lượt này.
- Đã thêm và khởi động `vocafy_ielts_queue` và `vocafy_scheduler`. AI dùng database queue riêng với retry_after 900 giây, job timeout 660 giây.
- Scheduler chỉ tự hoàn tất các lượt mới có `metadata.lifecycle_version = 2`; không tự chấm lại hàng loạt dữ liệu cũ.
- Không chạy seed, xóa bài thi/lượt thi hoặc tự cấp quyền chấm cho tài khoản hiện có.

### Giới hạn còn lại

Speaking từng Part/cue card/timing; editor bảng/flow-chart và đặt tọa độ bằng click; phiên full test; quản lý phiên bản/lưu giữ media; importer tổng quát và bộ kiểm thử hồi quy vẫn cần làm tiếp. Không đánh dấu các phần này hoàn tất chỉ vì các lỗi nền đã được sửa.

## 2. Phần đã có, cần giữ khi sửa

- Reading chia bài đọc/câu hỏi thành hai khung; nhiều group dùng chung Passage. Listening có trang câu hỏi chung và audio.
- Kéo thả theo `response_mode`: bank riêng từng group đặt dưới targets, once/repeat, xóa/thay/chuyển đáp án, pointer drag và cuộn khi kéo. Backend dùng chung validator ở autosave/submit; chuyển ô nguồn/đích trong một transaction.
- Multiple Choice chọn N đáp án có editor chung, N slot đánh số riêng, chấm không phụ thuộc thứ tự và không cộng điểm lặp.
- Completion có `[blank_N]` trong nội dung chung cho Reading/Listening; standard map có select/input tại X/Y.
- Snapshot cho lượt mới; owner/guest secret/admin guard; deadline phía server; chặn xem kết quả trước lúc hoàn tất.
- Audio Listening: upload/link trực tiếp/nhập Drive công khai; lưu tiến độ khoảng 10 giây; audio-end rút deadline còn **tối đa** 120 giây.
- Writing không còn tự gán band dự phòng khi bài trống/AI lỗi. Speaking có recorder, playback, thay/xóa file, đánh giá AI từ audio.
- `ai_band_score` chỉ tham khảo. `teacher_band_score` là điểm chính thức của Writing/Speaking, được đồng bộ sang `band_score`.

## 3. P1 — Ưu tiên trước đợt nghiệm thu tiếp theo

### IELTS-01 — Cột điểm làm lỗi trang danh sách kết quả

**Trạng thái sau sửa:** Đã sửa code: bỏ visibility phụ thuộc record; cột rỗng dùng placeholder, Speaking hiện dấu — ở raw score.

**Xác nhận:** lỗi tái hiện bằng component Filament thực trong container, không cần sửa DB.

**Bằng chứng:** [IeltsSubmissionResource.php](../../src/app/Filament/Resources/IeltsSubmissionResource.php), `table()`: hai `visible(fn (IeltsSubmission $record) ...)` ở cột `raw_score` và `ai_band_score`. Filament gọi visibility để dựng danh sách cột trước khi có record.

**Cần làm:** visibility cấp cột không phụ thuộc record; xử lý ô không áp dụng ở formatter/placeholder. Không chỉ thêm nullable rồi vô tình ẩn cột AI của toàn bảng.

**Hoàn thành khi:** bảng rỗng và bảng có đủ bốn skill đều mở được; Writing/Speaking chưa được giáo viên chấm hiển thị trạng thái chờ, AI vẫn ở cột tham khảo.

### IELTS-02 — Speaking có thể mất bản đang thu khi hết giờ hoặc tải lại

**Trạng thái sau sửa:** Đã sửa luồng lưu: bản dự phòng IndexedDB mỗi lần có dữ liệu, checkpoint server khoảng 5 giây, khôi phục/retry/download. Phiên thu bắt đầu trước deadline có thêm tối đa 120 giây để chuyển file; không được bắt đầu thu mới khi hết giờ. Cần nghiệm thu micro/browser và mạng thực tế.

**Xác nhận từ code:** recorder giữ chunks trong RAM và chỉ upload khi `stopSpeakingRecording()`. Khi timer về 0, `autoSubmitOnTimeUp()` gọi submit → dừng thu → upload; server từ chối upload sau deadline. Client chỉ submit khi trạng thái là `saved`. Nếu có bản cũ, bản mới thất bại có thể rơi về nộp bản cũ.

**Bằng chứng:** [room.blade.php](../../src/resources/views/ielts/room.blade.php), `startSpeakingRecording/stopSpeakingRecording/submitExam`; [controller](../../src/app/Http/Controllers/IeltsExamController.php), `uploadSpeakingRecording()`.

**Cần làm:** lưu từng đoạn/khôi phục bản đang thu; thiết kế bước hoàn tất bản ghi có giới hạn khi hết giờ, không mở lại thời gian trả lời. Có retry upload từ Blob đã thu. Sửa thông báo “tự lưu” để phản ánh đúng file đã được server xác nhận.

**Hoàn thành khi:** đang thu tới hết giờ, reload, mất mạng rồi kết nối lại, upload chậm hoặc thay bản cũ đều không âm thầm mất/đổi câu trả lời; có thông báo đúng trạng thái.

### IELTS-03 — Upload/xóa Speaking chưa đồng bộ với autosave và submit

**Trạng thái sau sửa:** Đã sửa code: session token + revision cho audio, row lock, staging file, dọn file sau commit, khóa thao tác UI, timeout và retry final upload idempotent.

**Xác nhận từ code:** upload/delete đọc submission và ghi lại toàn bộ `metadata` mà không khóa như save/submit. Điều kiện in-progress chỉ kiểm tra trước thao tác file; request đến trước submit có thể ghi sau khi submit đã hoàn tất. Delete xóa file trước khi cập nhật DB.

**Bằng chứng:** [controller](../../src/app/Http/Controllers/IeltsExamController.php), `uploadSpeakingRecording/deleteSpeakingRecording/submit`. Frontend không khóa bước xin quyền micro/delete; lỗi fetch khi upload có thể giữ `recordingState = uploading` nếu chưa có file cũ.

**Cần làm:** phối hợp khóa/version metadata, kiểm tra lại trạng thái trong bước commit, file staging và dọn file thất bại; khóa thao tác đang chạy, xử lý lỗi mạng/micro dừng đột ngột.

**Hoàn thành khi:** upload cùng submit, delete cùng submit, hai upload cùng lúc và DB/storage lỗi không ghi đè metadata mới, không sửa bản đã nộp, không để UI kẹt ở uploading.

### IELTS-04 — Snapshot chưa bảo vệ lịch sử khi xóa test/section

**Trạng thái sau sửa:** Đã áp dụng FK RESTRICT trên DB local và policy chặn xóa test/section đã có submission. Tắt xuất bản/is_active để ngừng dùng; không xóa lịch sử hoặc ép xóa nội dung đang được tham chiếu.

**Sửa lỗi phát sinh 02/10/2026:** IeltsExamSnapshotService::apply() đã truyền biến group vào closure khôi phục answer_options; trước đó mở phòng thi báo Undefined variable group tại dòng 131. Đã đọc lại snapshot của lượt bị lỗi: 7 group, 20 lựa chọn, 40 câu; cú pháp PHP hợp lệ.

**Xác nhận:** FK trong DB local vẫn cascade từ `ielts_tests/ielts_sections` đến `ielts_submissions`, rồi từ submission đến answers. Admin vẫn có DeleteAction cho test/section.

**Bằng chứng:** [migration submissions](../../src/database/migrations/2026_09_29_020003_create_ielts_submissions_tables.php), [migration snapshot](../../src/database/migrations/2026_10_02_000001_snapshot_ielts_submissions.php), trang Edit của test/section.

**Cần làm:** quyết định soft-delete, restrict hoặc FK nullable/set-null kết hợp snapshot; bổ sung hành vi cho lượt cũ không snapshot. Tách vòng đời dữ liệu thi và nội dung đề.

**Hoàn thành khi:** xóa/ẩn đề theo chính sách mới không làm mất bài làm, điểm giáo viên hoặc file Speaking cần giữ. Nghiệm thu xóa trên DB thử riêng.

### IELTS-05 — Quyền và quy trình giáo viên chấm chưa hoàn chỉnh

**Trạng thái sau sửa:** Đã thêm policy, quyền ielts.grade, phân công, bộ lọc chờ chấm, rubric, validation bước 0.5 và lịch sử/người chấm. Admin chấm mọi bài; tài khoản được cấp quyền và có quyền vào panel chỉ xem/chấm bài đã nộp được phân công. Chưa bổ sung quy trình phân công tự động/duyệt nhiều vòng.

**Đã có:** nhập một band tổng và nhận xét; lưu thời điểm chấm; kết quả học viên phân biệt AI/giáo viên.

**Còn thiếu:** role/quyền chấm IELTS riêng, phân công người chấm, hàng chờ, điểm từng tiêu chí/Task, định danh người chấm và lịch sử sửa điểm. Không thấy policy riêng cho các model IELTS. Panel cho cả editor/moderator vào, còn controller xem bài/audio của người khác chỉ cho admin/super-admin; quyền mở form và quyền xem bài chưa thống nhất.

**Bằng chứng:** [RoleEnum](../../src/app/Enums/RoleEnum.php), [User::canAccessPanel](../../src/app/Models/User.php), [resource](../../src/app/Filament/Resources/IeltsSubmissionResource.php), [controller::authorizeSubmission](../../src/app/Http/Controllers/IeltsExamController.php), [model](../../src/app/Models/IeltsSubmission.php).

**Cần làm:** policy theo chức năng và submission; rubric cho hai kỹ năng; audit người/thời điểm/lý do đổi điểm. Bổ sung validation band theo bước 0.5 phía server; hiện form dùng `step(0.5)`. Giới hạn sửa status của bài đã nộp.

**Hoàn thành khi:** người được phân công nghe/đọc/chấm được; người không có quyền bị chặn; lưu/sửa điểm có audit; AI không bao giờ thay điểm chính thức; chưa có điểm là null, điểm 0 hợp lệ vẫn hiển thị.

### IELTS-06 — Chấm AI đồng bộ trong transaction nộp bài

**Trạng thái sau sửa:** Đã thêm job trên connection/queue ielts, worker riêng, chốt bài trước AI, merge kết quả dưới lock, trạng thái pending/processing và nút Chấm AI lại. Retry không thay điểm giáo viên. Worker/scheduler local đã khởi động.

**Xác nhận từ code:** submit và tự chấm ở room/result giữ `lockForUpdate` khi gọi Gemini. Hai Task Writing gọi lần lượt; Speaking timeout 120 giây mỗi lần gọi và có retry HTTP. Chưa có job/hàng đợi và thao tác chấm lại bài completed.

**Bằng chứng:** [controller](../../src/app/Http/Controllers/IeltsExamController.php), [scoring](../../src/app/Services/IeltsScoringService.php), [GeminiService](../../src/app/Services/GeminiService.php).

**Cần làm:** hoàn tất bài thi và chốt dữ liệu trước; đánh giá AI qua job idempotent; trạng thái pending/processing/graded/failed; retry thủ công/tự động có giới hạn, tránh chấm hoặc tính phí trùng.

**Hoàn thành khi:** AI chậm/lỗi không giữ transaction nộp bài lâu; học viên vẫn thấy bài đã nộp và chờ AI/giáo viên; chấm lại không thay recording/essay hoặc điểm giáo viên. **Có retry HTTP hiện tại không đồng nghĩa có luồng chấm lại.**

### IELTS-07 — Autosave chưa bảo đảm thứ tự và thông báo lưu đúng

**Trạng thái sau sửa:** Đã sửa code: queue autosave tuần tự, expected_revision ở save/submit, chờ multi-select và drag đang lưu, hiển thị lỗi/retry và đồng bộ lại ô đáp án khi retry; nộp bài có timeout 30 giây, audio-end trả revision mới; không trả success cho question ID sai và kiểm tra key standard. Xung đột cửa sổ khác yêu cầu tải lại; chưa có hợp nhất bài làm offline giữa thiết bị.

**Xác nhận từ code:** save câu thường gửi fetch độc lập, không có revision/hàng đợi. Response cũ và rollback phía client có thể ghi đè thao tác mới. `saving` trở về false cả khi request lỗi; nhãn lại hiện “Đã tự lưu”. Submit chỉ chờ `pendingMultiSaves`, chưa chờ thao tác chuyển ô kéo thả đang lưu.

**Bằng chứng:** [room.blade.php](../../src/resources/views/ielts/room.blade.php), `saveAnswer/saveAnswerData/assignDragOption/submitExam`. Controller trả success khi question ID không khớp; standard choice chưa kiểm tra membership và notes/flags chưa có hợp đồng validation đầy đủ.

**Cần làm:** version/sequence hoặc hàng đợi theo submission, dirty/error state, retry; chờ mọi thao tác đáp án trước submit; mã lỗi rõ ràng cho payload không hợp lệ.

**Hoàn thành khi:** gõ/chọn nhanh, mạng chậm/đảo thứ tự, lỗi 422/409, kéo rồi nộp ngay giữ đúng thao tác cuối; UI không báo đã lưu khi server chưa nhận.

## 4. P2 — Hoàn thiện nghiệp vụ và các dạng câu

### IELTS-08 — Writing thiếu ngữ cảnh ảnh, General Training và cấu trúc Task chặt chẽ

**Trạng thái sau sửa:** Đã nối Academic/General, instruction/nội dung group và ảnh Task vào AI; ảnh lỗi khiến đánh giá thất bại thay vì chấm thiếu ảnh. Thêm validation hai Task và nhãn Letter/Report. Cần nghiệm thu AI thật và dữ liệu ảnh.

[Scorer](../../src/app/Services/IeltsScoringService.php) luôn gọi `ielts_academic` và `hasImage: false`, chỉ truyền prompt của câu; ảnh/instruction của group không được gửi. GeminiService có khả năng nhận ảnh/hệ General nhưng caller IELTS chưa nối vào. Room dùng câu đầu mỗi group, còn scorer coi câu số 1 là Task 1, các câu khác là Task 2; authoring chưa ép đúng hai group/mỗi group một câu.

**Cần làm và nghiệm thu:** schema/validation Task rõ ràng; gửi đầy đủ prompt/instruction/ảnh cần thiết; chọn đúng Academic/General; UI Task 1 không luôn ghi Report; đếm từ và kết quả khớp Task. Thử đủ/thiếu mỗi Task và ảnh không tải được; thiếu ngữ cảnh không được tạo đánh giá như đã có ảnh.

### IELTS-09 — Speaking hiện mới là bản ghi chung cho một section

**Trạng thái sau sửa:** Đã sửa ảnh Speaking và độ tin cậy lưu/khôi phục. Tiến trình Part 1–3/cue card/timing chuyên biệt vẫn là phần chức năng chưa triển khai; chưa có phân tích codec/thời lượng thực bằng công cụ media phía server.

Room hiển thị title/instruction/prompt của mọi group, một recorder chung. Chưa có tiến trình Part 1–3, cue card có cấu trúc, thời gian chuẩn bị/trả lời từng Part hoặc liên kết audio với từng câu. `image_url` nhập được ở admin Speaking nhưng room/result không render ảnh đó. Upload kiểm tra MIME/dung lượng; thời lượng lấy từ client, chưa kiểm tra audio track/độ dài thật/chất lượng đủ để chấm.

**Bằng chứng:** [room](../../src/resources/views/ielts/room.blade.php), [form](../../src/app/Filament/Forms/IeltsSectionForm.php), [controller](../../src/app/Http/Controllers/IeltsExamController.php).

**Hoàn thành khi:** dữ liệu Part và UI đồng nhất; cue card hiện đầy đủ; tệp hợp lệ được kiểm tra; các trình duyệt mục tiêu thu/phát/lưu được; file im lặng/quá ngắn có trạng thái không đủ dữ liệu đánh giá thay vì band có vẻ chắc chắn.

### IELTS-10 — Chưa validate đầy đủ kết quả AI

**Trạng thái sau sửa:** Đã thêm validator band/criteria/text/feedback, không lưu/render báo cáo Speaking failed như kết quả hợp lệ, hỗ trợ ungradable và lưu model/prompt version/thời điểm. Chưa đo độ chính xác AI trên bộ bài chuẩn.

Scorer mới kiểm tra `overallScore` trong khoảng 0–9. Chưa validate đầy đủ criteria, transcript, feedback và bước điểm. Đặc biệt Speaking gán `$evaluation` trước khi kiểm tra score; catch đặt failed nhưng cuối hàm vẫn lưu evaluation khác null. Result vẫn render evaluation đó. Prompt Speaking yêu cầu ước lượng thận trọng cả với audio im lặng/quá ngắn, chưa có hợp đồng `ungradable`.

**Bằng chứng:** [IeltsScoringService::scoreSpeakingSubmission](../../src/app/Services/IeltsScoringService.php), [GeminiService::evaluateSpeaking](../../src/app/Services/GeminiService.php), [result](../../src/resources/views/ielts/result.blade.php).

**Hoàn thành khi:** JSON sai schema, score/criteria sai kiểu hoặc ngoài khoảng không được lưu/render như báo cáo hợp lệ; failed/incomplete/ungradable rõ ràng; lưu model/prompt version và thời điểm đánh giá để truy nguyên.

### IELTS-11 — Biên tập/xuất bản chưa bảo đảm cấu trúc một đề hoàn chỉnh

**Trạng thái sau sửa:** Đã sửa navigation theo số câu thật, giới hạn Listening 1–40, validation Writing/Reading và preview trang giới thiệu đề nháp có quyền. Chưa có bộ trạng thái/preset riêng cho đề đủ so với đề luyện tập hoặc preview toàn phòng thi cho bản nháp.

Form chặn số trùng và blank không khớp, nhưng chưa ép số liên tục, bài đọc bắt buộc, đủ Task Writing, cấu trúc Part Speaking hoặc bộ dạng phù hợp theo skill. Navigation dùng `1..total_questions`; nhập câu 10–12 cho đề ba câu sẽ không có nút đúng. Listening suy Part mỗi 10 câu nhưng form cho số đến 200, UI giới hạn bốn Part. Preview admin trỏ trang public nên bản nháp chưa xuất bản trả 404.

**Bằng chứng:** [AuthoringService](../../src/app/Services/IeltsAuthoringService.php), [form](../../src/app/Filament/Forms/IeltsSectionForm.php), [EditIeltsTest](../../src/app/Filament/Resources/IeltsTestResource/Pages/EditIeltsTest.php), [room](../../src/resources/views/ielts/room.blade.php).

**Hoàn thành khi:** tách trạng thái bản nháp/đề luyện tập/đề đủ; validator xuất bản theo cấu hình; navigation dựa trên câu thật; preview riêng có quyền cho đề nháp. Không buộc mọi đề luyện tập phải đủ 40 câu.

### IELTS-12 — Word limit mới là số token, chưa diễn đạt đủ hướng dẫn

**Trạng thái sau sửa:** Đã thêm word_limit_mode, rule từ/số/AND-OR, đếm email/số thập phân/điện thoại, áp dụng map nhập chữ và kiểm tra answer key khi biên tập. Legacy có thể suy quy tắc từ hướng dẫn rõ ràng. Cần nghiệm thu tập đáp án biên rộng hơn.

[IeltsWordLimitService](../../src/app/Services/IeltsWordLimitService.php) đếm chuỗi chữ/số, số cũng là token. Chỉ áp dụng standard Completion/Short Answer; map nhập chữ không áp dụng. Một integer không phân biệt “ONE WORD ONLY” với “ONE WORD AND/OR A NUMBER”. Dấu chấm trong email/số thập phân có thể tách thành nhiều token; chưa validate key biên tập theo quy tắc tương ứng.

**Hoàn thành khi:** có loại giới hạn rõ ràng cho từ/số; rule dùng chung editor/API/scorer; key và đáp án như `3 cents`, địa chỉ, điện thoại, email, số thập phân, từ gạch nối có ví dụ kiểm chứng. Không chỉ tăng integer để bỏ qua hướng dẫn.

### IELTS-13 — Một số renderer/editor vẫn thiếu

**Trạng thái sau sửa:** Đã sửa mất question_content của Reading drag, ảnh map không có tọa độ, bàn phím map và ảnh Speaking; có chọn standard map nhập chữ. Admin đặt nhiều vị trí trên một ảnh chung của nhóm. Editor riêng Matching Features/Sentence Endings và bảng/flow-chart vẫn chưa có.

- Drag Reading chỉ render `question_content` khi có token `[blank_N]`; đoạn hướng dẫn/nội dung chung không có token có thể bị bỏ qua.
- Map kéo thả chỉ render ảnh nếu có ít nhất một câu đủ X/Y; map standard vẫn hiện ảnh khi thiếu tọa độ. Các ô map chưa có thao tác bàn phím tương đương target thường.
- Matching Features và Matching Sentence Endings có thể mô phỏng bằng matching/bank nhưng chưa có editor/renderer chuyên biệt.
- Table/Flow-chart đã có thể render HTML + blanks; chưa có công cụ tạo/sửa cấu trúc bảng/sơ đồ trong admin. Không nên ghi là hoàn toàn chưa hỗ trợ.
- Admin đặt các vị trí map bằng cách chọn câu rồi bấm trên một ảnh chung; cần nghiệm thu trực tiếp thao tác lưu và mở lại câu hỏi.

**Bằng chứng:** [room](../../src/resources/views/ielts/room.blade.php), [drag-drop-map](../../src/resources/views/ielts/partials/drag-drop-map.blade.php), [form](../../src/app/Filament/Forms/IeltsSectionForm.php), [enum](../../src/app/Enums/IeltsQuestionTypeEnum.php).

**Hoàn thành khi:** nội dung không mất theo response mode; ảnh luôn hiện khi hợp lệ; đặt/sửa/xóa bằng chuột, chạm và bàn phím; nghiệm thu hai group drag chung Passage, bank dưới targets, once/repeat, cuộn qua vùng ngoài màn hình. Dạng mới có thể là preset của schema hiện có, không bắt buộc thêm enum/DB riêng.

### IELTS-14 — Quy đổi band và vòng đời lượt thi còn thiếu quy tắc

**Trạng thái sau sửa:** Đã sửa mỗi ô đúng một điểm; chỉ quy đổi band cho 40 câu đúng hệ, thiếu bảng để null. Có resume lượt đang dở, duration_seconds và scheduler hoàn tất lượt mới quá hạn. time_spent_seconds từng câu chưa được đo; lượt cũ vẫn hoàn tất qua room/result/submit.

[IeltsBandScore::convert](../../src/app/Models/IeltsBandScore.php) dùng raw 0–40 cho cả đề ngắn, fallback sang bảng khác hệ thi nếu thiếu rồi trả 0.0 nếu không có dữ liệu. Điểm câu thường cộng `points`, không luôn 1 như slot multi-select.

Start luôn tạo lượt mới; chưa có luồng resume rõ ràng từ danh sách. Hết giờ chỉ hoàn tất khi client submit hoặc có request room/result; chưa có job quét lượt hết hạn. `duration_seconds` và `time_spent_seconds` chưa được cập nhật thời gian thực tế.

**Hoàn thành khi:** đề chưa chuẩn hóa có cách hiển thị raw/band rõ ràng; thiếu bảng điểm không thành band 0 giả; quy tắc mỗi ô một điểm nhất quán cho đề IELTS; có resume, hoàn tất quá hạn và thống kê thời lượng đáng tin cậy.

### IELTS-15 — Media và dữ liệu cũ cần chính sách lưu giữ

**Trạng thái sau sửa:** Đã bảo vệ lịch sử bằng RESTRICT và file Speaking bằng cập nhật nguyên tử/dọn file sau commit. Chưa version binary media ngoài hệ thống, backfill snapshot cũ hoặc triển khai retention/dọn file mồ côi tổng quát. Không tự suy đoán nguồn điểm của dữ liệu cũ.

Snapshot chụp nội dung/URL, không sao lưu binary của ảnh/audio bên ngoài; URL hỏng/thay file vẫn ảnh hưởng lượt cũ. Lượt trước snapshot không được backfill. Migration dual grading chuyển band Writing cũ thành AI và xóa band Speaking cũ; không phân loại điểm Writing nào từng được người chấm sửa tay.

Listening dùng một audio cho cả section, link trực tiếp chỉ validate URL, không kiểm chứng codec/file lúc xuất bản. Upload/Drive lưu public; bỏ chọn hoặc xóa đề chưa có quản lý file mồ côi. Speaking lưu private; cần chính sách backup, retention và xóa file gắn với vòng đời submission.

**Bằng chứng:** [snapshot service](../../src/app/Services/IeltsExamSnapshotService.php), [audio service](../../src/app/Services/IeltsAudioService.php), [dual migration](../../src/database/migrations/2026_10_02_000002_add_dual_ielts_subjective_scoring.php).

**Hoàn thành khi:** media dùng lại có version/tham chiếu; thao tác dọn file không phá lượt cũ; có kế hoạch dữ liệu legacy và khôi phục. Playlist là mở rộng tùy chọn; bài có một file chung không cần playlist để hoạt động.

### IELTS-16 — Thiếu kiểm thử bảo vệ các thay đổi mới

**Trạng thái sau sửa:** Chưa bổ sung/chạy bộ test tự động trong lượt sửa này. Đã kiểm tra cú pháp PHP/JS, biên dịch Blade/Vite và chạy migration. Nghiệm thu browser/micro/AI thật và bộ hồi quy vẫn còn.

[Test IELTS hiện có](../../src/tests/Feature/IeltsReadingListeningTest.php) gồm ba test: matching helper, luồng Reading và luồng Listening. Chưa bao phủ Filament, multi-select, drag once/repeat/chuyển ô, snapshot/xóa đề, owner/guest/admin, deadline, upload/ghi âm, AI lỗi và điểm giáo viên.

**Hoàn thành khi:** các lỗi P1 có kiểm thử hồi quy; nghiệm thu browser cho drag/autoscroll, upload/Drive và micro trên thiết bị mục tiêu; kiểm thử dữ liệu khác nhau giữa hai group, mạng lỗi và submit đồng thời. Gọi Gemini thật chỉ là kiểm tra tích hợp, không thay mock các lỗi/JSON sai và không chứng minh độ chính xác chấm.

## 5. P3 — Mở rộng sau khi ổn định

| Hạng mục | Phạm vi hiện tại | Điều kiện hoàn thành đề xuất |
| --- | --- | --- |
| Phiên full test | Mỗi submission thi một section | Liên kết bốn kỹ năng, chuyển phần, deadline từng phần, overall theo điểm chính thức và trạng thái chờ giáo viên |
| Import/export tổng quát | Có seeder chuyên biệt và dán bank | Preview/validate dữ liệu trước import, báo lỗi theo câu, tương thích phiên bản schema; xuất bộ đề có media |
| Công cụ quản lý nội dung | CRUD section/test, bộ lọc cơ bản | Nhân bản, phiên bản, tìm câu/bank, kiểm tra đề trước xuất bản, xem trước từng dạng |
| Báo cáo học tập/chấm bài | Lịch sử gần đây, kết quả từng lượt | Lịch sử đầy đủ, bộ lọc bài chờ chấm, tiến bộ theo kỹ năng/tiêu chí, dữ liệu thời lượng đúng |

## 6. Thứ tự thực hiện đề xuất

1. IELTS-01 và IELTS-04: mở lại được quản trị kết quả và bảo vệ lịch sử.
2. IELTS-02, IELTS-03, IELTS-07: chốt đường lưu/nộp bài, đặc biệt bản ghi Speaking.
3. IELTS-05, IELTS-06, IELTS-10: quyền giáo viên, quy trình hai nguồn điểm, chấm AI qua job và hợp đồng kết quả.
4. IELTS-08, IELTS-09: hoàn chỉnh dữ liệu và tương tác Writing/Speaking.
5. IELTS-11 đến IELTS-15: authoring, word limit, renderer, band và media.
6. IELTS-16 thực hiện cùng từng bước; chỉ mở rộng full test/import sau khi các luồng nền đã nghiệm thu.

Khi một mục hoàn thành, cập nhật bằng chứng và kết quả kiểm tra ở đây, đồng thời sửa README/ADMIN-GUIDE/QUESTION-TYPES tương ứng. Không đổi trạng thái thành “hoàn thiện” chỉ vì đã thêm field hoặc renderer.
