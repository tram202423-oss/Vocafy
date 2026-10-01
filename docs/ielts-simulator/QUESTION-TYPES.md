# IELTS Simulator — Mức độ hoàn thiện các dạng câu hỏi

**Ngày rà soát:** 01/10/2026. **Branch:** `feature/IELTS-Simulator-System-Blueprint`.

Bản đánh giá dựa trên mã nguồn tại commit nền `5a2740e` và các thay đổi chọn nhiều đáp án đang có trong working tree. Đây là rà soát code, chưa phải kết quả chạy nghiệm thu trên trình duyệt. Không chạy test tự động trong đợt viết tài liệu này.

- [Tổng quan và tài liệu kỹ thuật](README.md)
- [Hướng dẫn nhập đề cho admin](ADMIN-GUIDE.md)

## 1. Cách hiểu trạng thái

| Trạng thái | Ý nghĩa |
| --- | --- |
| **Đủ luồng chính** | Có phần nhập liệu, hiển thị, lưu bài, chấm và xem kết quả cho cách dùng cơ bản. Vẫn cần nghiệm thu thực tế; không đồng nghĩa đã đủ điều kiện vận hành thi chính thức. |
| **Một phần** | Đã có chức năng dùng được, nhưng còn thiếu hoặc có điểm chưa nhất quán ảnh hưởng trực tiếp đến dạng câu. |
| **Chưa có dạng riêng** | Chưa có model/enum/editor/renderer chuyên biệt. Có thể mô phỏng bằng dạng khác trong phạm vi hạn chế. |
| **Chưa triển khai luồng thi** | Có thể có tên kỹ năng trong cấu hình nhưng chưa có luồng làm bài và chấm phù hợp. |

Các vấn đề chung về quyền truy cập, thời gian thi và chấm điểm ở mục 4 áp dụng cả với dạng được đánh giá **Đủ luồng chính**.

## 2. Các dạng đã đủ luồng chính trong code

| Dạng | Nhập tại admin | Phòng thi | Lưu và chấm | Phạm vi còn cần nghiệm thu |
| --- | --- | --- | --- | --- |
| Multiple Choice — chọn một | Một câu, danh sách `key/text`, chọn một `correct_answer` | Radio cho Reading và Listening | Lưu một ký hiệu, so với đáp án, cộng điểm của câu | Tạo/sửa qua Filament, tải lại trang, nộp trực tiếp; API chưa giới hạn ngân hàng lựa chọn cho mọi dạng standard |
| Multiple Choice — chọn nhiều | Một prompt, một danh sách lựa chọn, tích ít nhất hai đáp án đúng, đặt số câu bắt đầu | Checkbox dùng chung, giới hạn bằng số ô đáp án, có các số câu riêng | Lưu cả nhóm trong transaction; đúng một lựa chọn = một điểm, không phân biệt thứ tự, không cộng lặp | Thay đổi mới trong working tree; chưa có test chuyên biệt cho admin, lưu đồng thời, thứ tự và điểm từng phần |
| True / False / Not Given | Admin có sẵn ba đáp án; `prepareQuestion()` tạo options khi lưu | Chọn một trong ba lựa chọn | Có so khớp và hỗ trợ viết tắt T/F/NG | Dữ liệu cũ không có `options` có thể hiện ô nhập chữ cho đến khi được lưu lại qua admin |
| Yes / No / Not Given | Tương tự, dùng YES/NO/NOT GIVEN | Chọn một trong ba lựa chọn | Có so khớp và hỗ trợ Y/N/NG | Cần nghiệm thu dữ liệu cũ và quy trình tạo/sửa |

### Quy tắc chọn nhiều đã có

Ví dụ đáp án nhóm Questions 7–8 là **B và D**:

| Bài làm | Điểm theo logic hiện tại |
| --- | --- |
| B, D | 2 |
| D, B | 2 |
| B, A | 1 |
| D và một ô trống | 1 |
| A, E | 0 |
| B, B qua API | Validation từ chối; scorer cũng không cộng B hai lần nếu dữ liệu trùng đã tồn tại |
| Ba lựa chọn cho nhóm có hai ô | Endpoint lưu nhóm từ chối |

Không nhập `B / D` vào một câu để tạo dạng chọn hai. Dấu `/` trong trường đáp án chữ là **các cách viết thay thế cho cùng một ô**, không tạo thêm số câu hoặc điểm.

## 3. Các dạng chưa hoàn thiện

| Dạng / chức năng | Đã có | Phần còn thiếu / chưa nhất quán | Đánh giá |
| --- | --- | --- | --- |
| Matching Headings | Enum riêng, chọn dạng tự bật kéo thả và `once`, ngân hàng chung, ô nhận theo từng câu, chấm theo key | Luồng submit cuối chưa dùng validator kéo thả đầy đủ; đề chung không chứa blank có thể không được hiển thị trong nhánh Reading kéo thả | Một phần |
| Matching Information | Enum riêng; có radio hoặc ngân hàng kéo thả, dùng lại lựa chọn được cấu hình | Standard vẫn nhập lựa chọn theo từng câu; kéo thả còn các điểm thiếu validation/hiển thị như trên | Một phần |
| Sentence Completion | `fill_in_blanks`, prompt chứa `[blank]` hoặc gạch dưới, nhập chữ và so khớp | `word_limit` chưa được thực thi khi lưu/chấm; Reading và Listening chưa thống nhất cách hiển thị ô inline | Một phần |
| Summary / Note / Form Completion | Có thể dùng `fill_in_blanks`; nội dung `[blank_N]` có renderer kéo thả | Chưa có renderer nhập chữ chung cho mọi blank ở cấp group; Reading standard không render `question_content`; chưa có editor cấu trúc riêng | Một phần |
| Table / Flow-chart Completion | Có thể dùng nội dung HTML hoặc ảnh để mô phỏng và tái sử dụng dạng điền từ/kéo thả | Toolbar hiện không có công cụ tạo bảng/flow-chart; không có schema và editor riêng; typed blanks trong đề chung chưa đầy đủ | Chưa có dạng riêng; hỗ trợ mô phỏng một phần |
| Short Answer | Ô nhập chữ, đáp án thay thế, trường giới hạn từ | Không kiểm tra số từ ở backend; normalization hiện khá rộng | Một phần |
| Plan / Map / Diagram Labeling — kéo thả | `map_labeling`, `image_url`, tọa độ X/Y phần trăm, ngân hàng, các ô trên ảnh | Phải nhập tọa độ thủ công; không có công cụ click lên ảnh để đặt ô; ảnh chỉ render trong partial khi có ít nhất một câu đủ tọa độ; thiếu validation cuối như các dạng kéo thả | Một phần |
| Plan / Map / Diagram Labeling — standard | Có enum, trường ảnh và lựa chọn theo câu | Nhánh standard đang không render `image_url` của group thành bản đồ tương tác; chưa có ô nhập chữ trên ảnh | Một phần, chưa đủ luồng bản đồ |
| Drag & Drop dùng chung | Pointer drag, click để chọn rồi gán, xóa/chuyển đáp án, dùng lại/không dùng lại, ngân hàng và blank chung | Lưu chuyển ô là nhiều request; có thể xóa ô nguồn rồi lưu ô đích thất bại; chưa kiểm tra cùng một bộ quy tắc ở autosave và submit; một số đích chưa đủ thao tác bàn phím | Một phần |
| Matching Features | Có thể mô phỏng bằng `matching_information` và ngân hàng lặp lại | Không có enum/editor riêng hoặc hướng dẫn authoring chuyên biệt | Chưa có dạng riêng |
| Matching Sentence Endings | Có thể mô phỏng bằng một dạng matching | Không có editor ghép đầu/cuối câu và renderer chuyên biệt | Chưa có dạng riêng |
| Writing Task 1 | Đề, ảnh, editor bài viết, đếm từ, autosave, gọi Gemini | Gửi `hasImage: false`, không gửi ảnh đề vào lệnh chấm tại đây; lỗi AI vẫn cho điểm dự phòng; chưa có luồng General Training riêng | Một phần |
| Writing Task 2 | Editor, đếm từ, gọi AI, tổng hợp band với Task 1 | Chấm đồng bộ; fallback/default có thể cho điểm khi AI lỗi hoặc thiếu bài; chưa có trạng thái chờ chấm/thử lại | Một phần |
| Speaking Part 1/2/3 | Có enum kỹ năng và lựa chọn trong admin | Không tìm thấy renderer Speaking riêng, thu âm, upload bản ghi, phiên phỏng vấn hay scorer Speaking trong module này | Chưa triển khai luồng thi |

`drag_drop` trong enum được gắn nhãn **Legacy Drag & Drop**. Khi nhập đề mới, chọn loại câu thực tế rồi đặt `response_mode = drag_drop`, thay vì coi kéo thả là một dạng nội dung độc lập.

## 4. Những điểm cụ thể cần hoàn thiện

### 4.1. Chưa đồng nhất validation kéo thả

**Mã nguồn:** `IeltsExamController::saveAnswer()`, `submit()`, `IeltsDragDropService::validateAnswers()`, `IeltsScoringService::scoreSubmission()`.

- Autosave kiểm tra key thuộc ngân hàng và lựa chọn chỉ được dùng một lần.
- `submit()` gọi validator chọn nhiều nhưng chưa gọi validator kéo thả.
- Service kéo thả cũ chỉ nhận dạng enum `DRAG_DROP`, chưa bao phủ `response_mode = drag_drop`, ngân hàng quan hệ và `option_usage` mới.
- Scorer có xử lý đáp án kéo thả bị dùng lặp khi cấu hình `once`, nhưng vẫn dùng so khớp văn bản chung cho đáp án từng câu.
- Chỉ cắm service cũ vào submit chưa đủ; cần cập nhật service và dùng chung ở cả hai đường ghi.

**Điều kiện hoàn thành:** mọi request lưu/nộp phải áp dụng cùng ngân hàng, quy tắc dùng lại và so khớp key; chuyển ô phải lưu nguyên tử cả nguồn lẫn đích.

### 4.2. Giới hạn từ và quy tắc so khớp

**Mã nguồn:** `IeltsSectionForm::questionSchema()`, `IeltsScoringService::checkAnswer()`, `isMatch()`.

- `word_limit` được lưu và hiển thị ở một số giao diện; scorer không đọc trường này.
- Scorer bỏ qua chữ hoa/thường, một số dấu câu, ký hiệu tiền tệ, khoảng trắng/gạch nối.
- Nhánh số điện thoại so cùng chuỗi chữ số khi có ít nhất năm chữ số; có thể chấp nhận thêm chữ ngoài đáp án.
- So khớp viết tắt T/F/Y/N đang là quy tắc chung, chưa giới hạn theo question type.

**Điều kiện hoàn thành:** có chính sách so khớp theo loại câu; quy định đếm từ và số được áp dụng phía server; các biến thể được chấp nhận phải rõ ràng.

### 4.3. Hiển thị đề và bản đồ

**Mã nguồn:** các nhánh Reading/Listening trong `room.blade.php`, `partials/drag-drop-map.blade.php`.

- Reading standard hiện render instruction và từng prompt nhưng bỏ `question_content`.
- Listening standard render `question_content` dạng HTML, không chuyển `[blank_N]` trong đó thành ô nhập chữ.
- `[blank]` trong Reading prompt là ô của chính câu đó. Nếu đặt nhiều token trong cùng prompt, chúng cùng liên kết một answer; không phải nhiều câu độc lập.
- Map standard chưa sử dụng ảnh group trong renderer tương ứng.
- Map kéo thả thiếu tọa độ sẽ rơi về danh sách câu; ảnh chỉ nằm trong nhánh có câu được đặt tọa độ.

**Điều kiện hoàn thành:** renderer nội dung chung thống nhất; mỗi blank có liên kết rõ ràng; ảnh và ô trả lời có đủ cả chế độ được công bố hỗ trợ.

### 4.4. Listening chưa hoàn chỉnh như một phiên thi

**Mã nguồn:** `room.blade.php` phần audio, `onAudioEnded()`; `result.blade.php` phần transcript.

- Chỉ phát `audio_url` đầu tiên tìm thấy; chưa có playlist nối audio theo group/Part.
- Thiếu audio thì dùng âm thanh quán cà phê dự phòng.
- Audio kết thúc có thể rút thời gian còn lại xuống 120 giây trên client; trạng thái này chưa được lưu phía server.
- Reload khởi tạo lại player, chưa khôi phục tiến độ nghe.
- Phòng thi suy ra Part theo số câu chia nhóm 10 câu, nhưng trang transcript dùng `group.order`; nhiều nhóm trong cùng Part có thể bị lệch.
- Group trải qua hai khoảng Part vẫn được đặt vào Part của câu đầu tiên.

**Điều kiện hoàn thành:** lựa chọn rõ mô hình một audio toàn bài hoặc nhiều audio, lưu tiến độ/thời gian nhất quán và có trường/mapping Part dùng chung.

### 4.5. Writing còn điểm dự phòng

**Mã nguồn:** `IeltsScoringService::scoreWritingSubmission()`.

- Exception từ Gemini: Task 1 nhận band 6.0, Task 2 nhận 6.5.
- Khi thiếu Task 1, phép tổng hợp dùng 5.5; Task 2 có fallback về điểm Task 1 hoặc 5.5.
- Vì vậy bài trống hoặc lỗi chấm không tương đương trạng thái “chưa có điểm”.
- Lệnh chấm gắn `examCategory = ielts_academic` cho mọi hệ thi.
- Giao diện Writing dùng mốc cố định 150/250 từ theo Task, chưa lấy mốc từ `word_limit` của câu; mỗi group chỉ render câu đầu tiên.

**Điều kiện hoàn thành:** bỏ điểm giả định, có trạng thái chấm lỗi/chờ chấm, xử lý bài trống, ảnh Task 1 và General Training theo nội dung thực tế.

### 4.6. Quyền truy cập, xuất bản và thời hạn

**Mã nguồn:** `src/routes/web.php`, `IeltsExamController`.

- Route IELTS không nằm trong group `auth`; lượt thi cho khách có `user_id = null`.
- Chưa thấy kiểm tra chủ sở hữu/session token trong room/save/submit/result. UUID không thay thế kiểm tra quyền.
- Index lọc published/active; show/start tra theo slug chưa áp dụng cùng điều kiện.
- Khi `section_id`/`skill` không tìm thấy, start rơi về section đầu thay vì trả lỗi rõ ràng.
- `room()` xử lý hết thời gian, còn save/submit chưa kiểm tra deadline tương ứng.
- Result chưa yêu cầu submission đã hoàn tất trước khi render đáp án đúng. Người có URL lượt thi có thể truy cập trang kết quả sớm theo luồng hiện tại.

**Điều kiện hoàn thành:** policy cho học viên/khách/admin; chặn xem đáp án sớm; kiểm tra xuất bản và deadline tại mọi endpoint liên quan.

### 4.7. Lịch sử, lưu bài và full test

- Submission giữ FK đến câu hỏi hiện tại, không lưu snapshot đề/đáp án. Chỉnh sửa hoặc xóa câu có thể thay đổi nội dung xem lại; xóa câu có cascade đến user answers.
- Một submission chỉ gắn một section; chưa có phiên full test tự nối bốn kỹ năng và tính overall chung.
- Notes gắn vào highlight hiện lưu ở DOM, chưa có luồng khôi phục sau reload; trường notes/API tồn tại không có nghĩa mọi ghi chú giao diện đã được lưu.
- `duration_seconds` và `time_spent_seconds` có cột, nhưng controller chưa cập nhật thời lượng thực tế.
- Autosave một câu vẫn có thể nhận request về sai thứ tự khi gõ liên tiếp; luồng chọn nhiều mới có cơ chế chờ lưu riêng.
- Band conversion dùng thang raw 0–40, chưa chuẩn hóa cho đề luyện tập ít hơn 40 câu.

## 5. Dữ liệu mẫu không thay cho mức độ hoàn thiện

- `IeltsSeeder`: bảng band, đề demo Reading/Listening/Writing và demo kéo thả. Audio Listening demo là âm thanh môi trường, không chứng minh bài nghe đã đủ nội dung.
- `IeltsReadingImportSeeder`: ba passage Floods/Gifted Children/Art Museums, **35 câu, 55 phút**; nhiều câu được mô phỏng bằng kéo thả, không phải bộ kiểm chứng mọi dạng IELTS.
- Nhóm chọn nhiều trong seeder import còn instruction cũ “hãy chọn theo thứ tự câu”; câu này không còn đúng với scorer hiện tại.
- Seeder import vẫn lưu một phần nội dung và ngân hàng theo cấu trúc legacy. Renderer có fallback, nhưng cần rà soát lại cách tách passage/đề khi chuyển dữ liệu sang editor mới.

## 6. Thứ tự hoàn thiện đề xuất

| Ưu tiên | Công việc | Tiêu chí nhận bàn giao |
| --- | --- | --- |
| P1 | Quyền truy cập, chặn result trước khi hoàn tất, deadline server, trạng thái xuất bản | Không thể đọc/sửa lượt thi khác; không xem đáp án sớm; không ghi bài quá hạn hoặc bắt đầu đề không được phép |
| P1 | Validator kéo thả thống nhất và chuyển ô nguyên tử | Autosave và submit cho cùng kết quả; lưu lỗi không mất đáp án nguồn |
| P1 | Loại bỏ band dự phòng Writing | Bài trống/chấm lỗi có trạng thái rõ, không nhận điểm giả định |
| P2 | Nghiệm thu chọn nhiều và editor mới | Tạo/sửa/đổi số đáp án; B–D và D–B đều 2 điểm; chọn đúng một được 1; reload/nộp khi đang lưu không mất dữ liệu |
| P2 | Giới hạn từ và so khớp theo loại | Có quy tắc được chấp nhận nhất quán ở UI/API/scorer |
| P2 | Completion và map renderer | Đề chung hiện đúng ở cả Reading/Listening; từng ô độc lập; ảnh luôn đủ ngữ cảnh |
| P2 | Audio, Part và transcript | Cùng một mapping Part, khôi phục tiến độ nghe, thời gian đồng nhất |
| P2 | Snapshot đề và ghi chú | Xem lại bài cũ không thay đổi khi sửa đề; notes sống qua reload |
| P3 | Các editor chuyên biệt và Speaking | Có đặc tả, editor, tương tác, lưu, chấm và kết quả cho từng dạng mới |

## 7. Phạm vi test hiện có

File `src/tests/Feature/IeltsReadingListeningTest.php` chứa ba test:

1. `test_answer_matching_with_various_formats` — so khớp viết tắt, cách viết thay thế, dấu tiền tệ, gạch nối, số điện thoại, dấu câu.
2. `test_reading_exam_flow` — start, room, autosave, submit, band và result của dữ liệu seed.
3. `test_listening_exam_flow` — start, room, submit, band và result/transcript của dữ liệu seed.

Đọc các test cho thấy chưa có kịch bản chuyên biệt cho multi-select mới, form Filament, drag/map theo response mode mới, giới hạn từ, quyền truy cập hoặc hết giờ. Không khẳng định ba test hiện có đang pass vì chưa chạy trong đợt tài liệu này.
