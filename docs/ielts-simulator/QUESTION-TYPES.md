# IELTS Simulator — Trạng thái các dạng câu hỏi

**Rà soát:** 02/10/2026, WSL `/home/tram/Study/Vocafy`, branch `feature/IELTS-Simulator-System-Blueprint`, HEAD `761e76d` và thay đổi chưa commit.

- [Tổng quan kỹ thuật](README.md)
- [Hướng dẫn admin](ADMIN-GUIDE.md)
- [Báo cáo thiếu sót và thứ tự xử lý](AUDIT.md)

## 1. Cách hiểu trạng thái

**Có luồng cơ bản** nghĩa là có code nhập/hiển thị/lưu/chấm/xem kết quả cho trường hợp thông thường. **Một phần** nghĩa là có khả năng dùng nhưng còn giới hạn trực tiếp. **Chưa có editor riêng** không đồng nghĩa không thể biểu diễn bằng schema đang có.

Đợt này rà code và kiểm tra chỉ đọc một số thành phần trong container; chưa nghiệm thu trình duyệt hoặc gọi AI thật. Các vấn đề autosave, quyền, deadline và lịch sử trong [AUDIT.md](AUDIT.md) áp dụng cho toàn module.

## 2. Ma trận hỗ trợ

| Dạng / kỹ năng | Admin và dữ liệu | Phòng thi, lưu và chấm | Trạng thái / phần còn thiếu |
| --- | --- | --- | --- |
| Multiple Choice chọn một | Options theo câu, một key đúng | Radio Reading/Listening, lưu key, chấm theo đáp án | Có luồng cơ bản; API đã kiểm tra key lựa chọn; cần nghiệm thu browser |
| Multiple Choice chọn nhiều | Một prompt/options, N key đúng → N câu | Checkbox chung, lưu nhóm trong transaction; đúng mỗi key khác nhau được một điểm, không phụ thuộc thứ tự | Có luồng cơ bản; thiếu nghiệm thu editor, điểm từng phần và lưu/nộp đồng thời |
| True / False / Not Given | Ba lựa chọn cố định; chuẩn hóa alias cũ khi lưu | Chọn đáp án, scorer có T/F/NG | Có luồng cơ bản; dữ liệu legacy thiếu options cần rà renderer/lưu lại |
| Yes / No / Not Given | Ba lựa chọn cố định | Chọn đáp án, scorer có Y/N/NG | Có luồng cơ bản; cần nghiệm thu cả dữ liệu legacy |
| Matching Headings | Type riêng, mặc định drag + once; chỉ dùng cho Reading | Bank dưới targets, key nối từng Paragraph | Một phần; đã hiển thị nội dung không có blank và chặn Matching Headings ở Listening; cần nghiệm thu |
| Matching Information | Standard options theo câu hoặc bank drag chung | Chọn/kéo thả, once/repeat, chấm key | Có luồng cơ bản; thiếu nghiệm thu nhiều group và bàn phím |
| Sentence Completion | `fill_in_blanks`, prompt và đáp án chữ | Reading có inline blank trong prompt; Listening câu đơn vẫn có input riêng | Một phần; renderer hai skill chưa đồng nhất; đã có rule từ/số/AND-OR; cần nghiệm thu đáp án biên |
| Summary / Note / Form Completion | `question_content` + `[blank_N]`, mỗi blank một question row | Có typed blanks và drag blanks ở Reading/Listening | Có luồng cơ bản; thiếu preset/editor theo cấu trúc từng dạng |
| Table / Flow-chart Completion | HTML/ảnh + blanks dùng schema Completion | Đã render nội dung chung và ô trả lời, gồm bảng trong seeder Listening | Một phần; chưa có công cụ tạo/sửa bảng/flow-chart trong admin |
| Short Answer | Prompt, đáp án thay thế, word_limit | Input Reading/Listening, kiểm tra giới hạn ở server, chấm chữ | Có luồng cơ bản; đã có cấu hình giới hạn từ/số; cần nghiệm thu đáp án biên |
| Plan / Map / Diagram — standard | Một ảnh chung, chọn câu rồi bấm đặt vị trí, options theo câu | Select/input trên ảnh; câu thiếu vị trí ở danh sách | Một phần; admin hiển thị mọi marker trên một ảnh và lưu đáp án chọn/nhập chữ vào `correct_answer`; cần nghiệm thu giao diện |
| Plan / Map / Diagram — drag | Một ảnh chung, chọn câu rồi bấm đặt vị trí, bank chung | Drop targets trên ảnh; câu thiếu vị trí ở danh sách | Một phần; câu mới bắt buộc có vị trí, đã có Enter/Space/Delete; cần nghiệm thu bàn phím |
| Matching Features | Có thể dùng `matching_information` + bank, repeat khi đề cho phép | Tái sử dụng targets/scorer matching | Chưa có editor/preset chuyên biệt |
| Matching Sentence Endings | Có thể nhập đầu câu ở prompt, cuối câu ở bank matching | Tái sử dụng chọn/kéo thả và chấm key | Chưa có editor/renderer chuyên biệt |
| Writing Task 1 | Group, một câu số 1, ảnh tùy chọn | Essay, đếm từ, autosave, AI tham khảo và giáo viên chính thức | Một phần; đã gửi ảnh/instruction, phân Academic/General và validate hai Task; cần nghiệm thu AI |
| Writing Task 2 | Group, một câu số 2 | Essay, AI tham khảo; tổng AI trọng số Task 1:Task 2 = 1:2 | Một phần; đã có job/chấm lại và rubric giáo viên; cần nghiệm thu end-to-end |
| Speaking Part 1/2/3 | Các group/prompt; không cần answer key | Một bản ghi cả section; nghe/xóa/thu lại; file private; AI từ audio; giáo viên nhập band | Một phần; chưa chia tiến trình Part/cue card/timing; đã thêm draft/checkpoint/retry và hạn chuyển file; cần nghiệm thu browser |

## 3. Kéo thả là cách trả lời

Dữ liệu mới chọn loại nội dung rồi đặt `response_mode = drag_drop`. Enum `drag_drop` giữ tương thích legacy. [IeltsAuthoringService](../../src/app/Services/IeltsAuthoringService.php) cho phép kéo thả ở matching headings/information, fill in blanks và map labeling, chỉ trong Reading/Listening.

Đã có trong code:

- Ngân hàng độc lập từng group, chỉ hiện khi có lựa chọn; đặt phía dưới vùng trả lời.
- `option_usage = once` hoặc `repeat`; được kiểm tra ở admin, autosave, submit và scorer.
- Kéo từ bank, thay đáp án, chuyển ô, kéo về bank hoặc bấm × để bỏ đáp án.
- Pointer drag có cuộn khung khi đưa con trỏ sát mép; tách Reading passage khỏi đề có blank và giữ Listening trên cùng trang.
- [IeltsDragDropService](../../src/app/Services/IeltsDragDropService.php) kiểm tra key và trạng thái cả group; chuyển ô ghi nguồn/đích cùng transaction.
- Bank tương thích quan hệ `answerOptions`, `settings.drag_options`, rồi options của câu đầu.

Đã sửa chờ drag đang lưu trước submit, nội dung Reading không có blank và ảnh map thiếu tọa độ; thêm Enter/Space/Delete cho map. Còn công cụ biên tập chuyên biệt và nghiệm thu browser cho nhiều group, once/repeat, cuộn/chạm/bàn phím. Trạng thái từng việc ở [AUDIT](AUDIT.md).

## 4. Điểm và validation

### 4.1. Chọn nhiều

Nhóm đúng B và D, hai slot Questions 7–8:

| Câu trả lời | Điểm theo logic hiện tại |
| --- | --- |
| B, D hoặc D, B | 2 |
| B, A | 1 |
| D và một ô trống | 1 |
| A, E | 0 |
| B, B qua API | Bị từ chối; scorer không cộng key trùng hai lần |
| Ba lựa chọn cho hai slot | Bị từ chối |

`B / D` trong đáp án chữ nghĩa là hai cách viết thay thế cho **một ô**, không tạo câu chọn hai.

### 4.2. Completion/Short Answer

[IeltsWordLimitService](../../src/app/Services/IeltsWordLimitService.php) áp dụng ở autosave/submit/scorer cho Reading/Listening standard Completion và Short Answer. Bank key và Writing không áp dụng. Từ có dấu nháy/gạch nối tính một token, số cũng là token.

Đã thêm word_limit_mode cho tổng từ/số, chỉ từ và N từ AND/OR một số; có thể suy từ instruction rõ ràng. Email/số thập phân không bị tách theo dấu chấm, điện thoại có khoảng trắng tính một số. Map nhập chữ cũng dùng validator này.

Đáp án thay thế vẫn dùng slash/pipe/semicolon hoặc JSON array. Band objective chỉ tra bảng đúng hệ cho đề 40 câu; thiếu bảng hoặc đề ngắn để null và hiện lý do, không tạo band 0 dự phòng.

### 4.3. Writing/Speaking

| Trường | Vai trò |
| --- | --- |
| `ai_band_score` | Band tham khảo, null khi AI chưa có kết quả đủ/hợp lệ |
| `teacher_band_score` | Band chính thức do người chấm nhập |
| `band_score` | Với Writing/Speaking, đồng bộ từ điểm giáo viên |
| `teacher_scored_at` / `examiner_notes` | Thời điểm và nhận xét; có thêm teacher_scored_by, teacher_criteria và grading_history |

Writing chỉ tổng hợp AI khi cả hai Task được chấm hợp lệ; không gán band dự phòng. Thiếu bài → incomplete, lỗi AI → failed. Điểm chính thức vẫn phụ thuộc giáo viên, không phụ thuộc AI đã xong hay chưa.

Speaking lưu draft trên thiết bị, checkpoint server và file cuối khi dừng. Có khôi phục/retry/download; phiên trước deadline có tối đa 120 giây để hoàn tất upload. AI chạy qua job riêng, validate kết quả, có ungradable và chấm lại. Điểm giáo viên/rubric độc lập và có lịch sử. Tiến trình Speaking từng Part chưa có; browser/micro/Gemini thật chưa nghiệm thu trong lượt sửa này.

## 5. Listening và dữ liệu mẫu

- Một audio chung cho section; authoring chặn các URL khác nhau giữa group. Upload/link trực tiếp/Drive đã có.
- Thiếu audio: room hiện thông báo, không dùng âm thanh demo làm fallback. Seeder demo cũ vẫn có thể cấu hình âm thanh môi trường như URL của chính đề.
- Tiến độ lưu khoảng 10 giây và khôi phục khi reload. Audio-end gửi về server, rút deadline còn tối đa 120 giây.
- Part trong room/transcript lấy từ số câu theo từng khoảng 10; group không được trải qua hai Part. Chưa có mapping Part độc lập số câu.
- Có seeder Reading Tea/Transport/Innovation 40 câu và Listening Call Unlimited/London Eye 40 câu; seeder cũ Flood/Gifted/Museums 35 câu chỉ là dữ liệu luyện tập.
- Seeder và việc có đủ câu không chứng minh tất cả tương tác đã nghiệm thu. Chi tiết nhập và lưu giữ media ở [ADMIN-GUIDE](ADMIN-GUIDE.md).

## 6. Ma trận nghiệm thu cần bổ sung

| Nhóm | Kịch bản bắt buộc |
| --- | --- |
| Khách/tài khoản/quyền | Owner, người khác, guest đúng/sai session, admin, người chấm; result trước khi nộp; file audio private |
| Reading/Listening | Mỗi type qua tạo/sửa → làm → reload → nộp → xem điểm/lời giải; đúng/sai/trống |
| Drag/Matching | Hai group cùng Passage, bank riêng dưới targets, once/repeat, đổi/xóa/chuyển/trả về bank, auto-scroll, touch/keyboard, nộp khi đang lưu |
| Multi-select | Đủ/thiếu/sai thứ tự, điểm từng phần, key trùng/thừa, thay số slot trong admin |
| Completion/Map | Token khớp số câu, nội dung dài/bảng, standard/drag, ảnh và tọa độ thiếu, giới hạn từ/số |
| Listening media | Upload và Drive, file hỏng/không có quyền, autoplay bị chặn, reload, audio-end/mạng lỗi/deadline ngắn |
| Writing | Task 1 ảnh, General, đủ/thiếu từng Task, lỗi AI, báo cáo sai JSON, chấm giáo viên độc lập AI |
| Speaking | Cho/từ chối micro, stop/re-record/delete, file quá lớn, reload/mất mạng/hết giờ, upload-submit đồng thời, audio không đủ để chấm |
| Dữ liệu và admin | Sửa/xóa câu/test/section khi đã có lượt thi, snapshot/legacy, bảng kết quả, sửa band và lịch sử người chấm |

Chưa đánh dấu các kịch bản này là đã đạt. Thứ tự sửa và tiêu chí đóng từng công việc được duy trì tại [AUDIT.md](AUDIT.md).
