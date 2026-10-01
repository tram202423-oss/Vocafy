# IELTS Simulator — Hướng dẫn admin nhập đề

**Cập nhật:** 01/10/2026, theo code hiện có trên branch `feature/IELTS-Simulator-System-Blueprint`, bao gồm editor chọn nhiều mới.

- [Tổng quan module](README.md)
- [Dạng câu đã có và còn thiếu](QUESTION-TYPES.md)

Hướng dẫn này dùng tên trường của form hiện tại. Các ví dụ là dữ liệu nhập liệu; không phải xác nhận đã tạo hoặc cập nhật database của bạn.

## 1. Bộ đề, phần thi, nhóm và câu hỏi khác nhau thế nào?

| Khái niệm | Ví dụ | Cách sử dụng |
| --- | --- | --- |
| Bộ đề — Test | Reading Practice 01 hoặc bộ Listening + Reading | Chọn các phần thi cùng hệ, bật xuất bản |
| Phần thi — Section | Reading 60 phút | Chứa toàn bộ câu của một kỹ năng |
| Nhóm — Question Group | Passage 1, Questions 1–6: Matching Information | Một nhóm dùng cùng dạng câu và hướng dẫn |
| Câu/ô đáp án — Question | Câu 7, câu 8 | Mỗi số câu là một đơn vị chấm điểm |

Một Passage có thể có nhiều group. Một group “Choose TWO” có hai số câu, mặc dù chỉ nhập một nội dung câu hỏi chung.

## 2. Quy trình tạo một đề

1. Vào `/admin`, nhóm menu **IELTS System**.
2. Mở **Phần thi & câu hỏi**, tạo một phần thi.
3. Ở tab **Thông tin**, nhập tiêu đề, kỹ năng, hệ Academic/General Training và thời gian.
4. Ở **Nội dung & câu hỏi**, thêm các group theo thứ tự xuất hiện.
5. Nhập nguồn đề, dạng câu, hướng dẫn, lựa chọn và đáp án.
6. Lưu phần thi; hệ thống tính tổng câu từ các row câu hỏi.
7. Tạo bộ đề trong resource bộ đề IELTS, chọn các section vừa tạo.
8. Bật **Xuất bản cho học viên** và lưu.
9. Mở bộ đề từ đường dẫn `/ielts/tests/<slug>` để xem nội dung đã nhập.

Có thể tạo section ngay trong hộp thoại dấu **+** tại trường chọn section của bộ đề. Form dùng chung với trang **Phần thi & câu hỏi**.

### Ràng buộc hiện có

- Một bộ đề chỉ chọn một section cho mỗi kỹ năng.
- Section và test phải cùng hệ thi.
- Muốn xuất bản qua admin: phải có section active và đã có câu hỏi.
- Số câu không được trùng trong toàn section; form cho nhập 1–200.
- Nên đánh số liên tục từ 1 vì thanh điều hướng hiện dựa trên tổng câu.
- Listening hiện phân Part theo khoảng 1–10, 11–20, 21–30, 31–40.
- Không tạo Speaking để dùng như một bài thi đã hoàn thiện; module chưa có luồng thu âm/chấm Speaking.

## 3. Nhập nguồn bài đọc và đề câu hỏi

### 3.1. Bài đọc nguồn

Dùng tab **Bài đọc nguồn** → **Nội dung Reading Passage**.

Ví dụ Passage 1 gồm ba group:

| Group | Loại câu | Bài đọc nguồn |
| --- | --- | --- |
| Questions 1–6 | Matching Information | Nhập toàn bộ Passage 1 |
| Questions 7–8 | Multiple Choice chọn hai | Để trống |
| Questions 9–13 | True / False / Not Given | Để trống |

Ở group đầu của Passage 2, nhập bài đọc nguồn mới. Hệ thống hiểu các group tiếp theo thuộc Passage đó cho đến nguồn mới kế tiếp.

Không lặp lại cùng một bài đọc vào tất cả group: mỗi group có nguồn sẽ được xem là bắt đầu Passage mới.

### 3.2. Đề bài chung của group

Dùng tab **Đề bài**:

- **Hướng dẫn cho nhóm câu hỏi:** yêu cầu như Choose TWO, NO MORE THAN TWO WORDS, Match headings…
- **Đề bài / ghi chú / bảng có ô trống:** nội dung chung, đặc biệt hữu ích cho kéo thả với `[blank_N]`.
- **Ảnh sơ đồ / bản đồ:** dùng cho map hoặc ảnh đề Writing.

Lưu ý hiện tại: Reading standard chưa render trường đề chung này; để bảo đảm câu hỏi standard có nội dung, đặt nội dung cần trả lời trong từng prompt. Nhánh chọn nhiều mới có hiển thị đề chung. Với kéo thả, ưu tiên nội dung có `[blank_N]` hoặc prompt riêng cho từng đích.

## 4. Multiple Choice — chọn một đáp án

1. Chọn **Multiple Choice (Trắc nghiệm)**.
2. Để tắt **Chọn nhiều đáp án**.
3. Vào **Câu hỏi & đáp án**, thêm câu.
4. Nhập số câu và prompt.
5. Nhập lựa chọn, ví dụ `A / First option`, `B / Second option`, `C / Third option` vào hai trường key và nội dung.
6. Chọn đúng **một key** tại **Đáp án đúng**.

Mỗi row là một câu có một bộ lựa chọn riêng. Nếu các câu dùng chung một ngân hàng thì cân nhắc dạng matching với kéo thả.

## 5. Multiple Choice — chọn nhiều đáp án

### 5.1. Ví dụ Questions 7–8, đúng B và D

1. Thêm group, đặt tên `Passage 1 — Questions 7–8`.
2. Chọn type **Multiple Choice**.
3. Bật **Chọn nhiều đáp án (Choose TWO / THREE…)**.
4. Vào **Câu hỏi & đáp án**.
5. Nhập **Bắt đầu từ câu số = 7**.
6. Nhập **Nội dung câu hỏi chung**:

   > What TWO benefits will the new approach in the UK and Austria bring to us according to this passage?

7. Nhập lựa chọn A–E một lần. Có thể dùng **Dán danh sách lựa chọn**:

   ```text
   A | We can prepare before the flood comes
   B | It may stop the flood involving the whole area
   C | Decrease strong rainfalls around the Alps simply by engineering constructions
   D | Reserve water to protect downstream towns
   E | Store tons of water in the downstream area
   ```

8. Tại **Các đáp án đúng**, tích **B** và **D**.
9. Xem dòng **Questions 7–8 · Choose 2 · 2 điểm**.
10. Nhập lời giải/trích dẫn chung nếu có rồi lưu phần thi.

Hệ thống tạo hai ô mang số 7 và 8. Không cần tạo hai prompt hoặc lặp lại năm lựa chọn hai lần.

### 5.2. Quy tắc

- Số key đúng được chọn quyết định số câu và số điểm tối đa.
- Chọn ít nhất hai đáp án đúng khác nhau.
- Có thể tích ba key để tạo “Choose 3”, bắt đầu từ số đã nhập.
- Dãy câu phải nằm trong 1–200 và không trùng nhóm khác.
- Thí sinh chọn B rồi D hoặc D rồi B đều được hai điểm.
- Một key đúng và một key sai được một điểm.
- Một key đúng và một ô bỏ trống được một điểm.
- Không được chọn trùng hoặc chọn vượt số ô.
- Không nhập `B,D`, `B / D` hoặc `B|D` làm đáp án của một câu thường để mô phỏng dạng này.

### 5.3. Chỉnh sửa nhóm đã có

Khi mở nhóm multi-select cũ, form đọc prompt, options và đáp án từ các câu hiện có để điền vào editor chung. Khi lưu, các row số câu được đồng bộ lại.

Nếu đổi hai đáp án đúng thành ba, hệ thống cần thêm một số câu. Nếu đổi ba thành hai, row dư sẽ bị xóa. Kiểm tra số bắt đầu và các nhóm tiếp theo để không trùng số. Những thay đổi cấu trúc câu hiện có thể ảnh hưởng lịch sử bài làm vì module chưa có snapshot đề.

Nếu nhóm cũ còn instruction “hãy chọn theo thứ tự câu”, sửa thành hướng dẫn không yêu cầu thứ tự. Scorer hiện chấp nhận mọi thứ tự trong nhóm.

## 6. True / False / Not Given và Yes / No / Not Given

1. Chọn đúng type.
2. Thêm các câu và prompt.
3. Chọn đáp án từ danh sách có sẵn.
4. Lưu.

Không cần nhập thủ công ba lựa chọn cho mỗi câu. Form tạo options khi lưu. Đáp án cũ T/F/NG hoặc Y/N được chuyển sang giá trị đầy đủ trong editor.

Không dùng YES/NO thay cho TRUE/FALSE: chọn type và đáp án phù hợp với nội dung đề. Việc scorer có hỗ trợ viết tắt không thay thế việc nhập đúng dạng câu.

## 7. Matching Headings và Matching Information

### 7.1. Matching Headings

Khi chọn type này ở Reading/Listening, form tự đặt:

- **Cách trả lời:** kéo thả.
- **Sử dụng đáp án:** mỗi đáp án chỉ dùng một lần.

Vào **Ngân hàng đáp án**, thêm cả headings đúng và headings nhiễu:

```text
i | A change in flood prevention
ii | The impact of urban development
iii | Research into river systems
iv | The cost of earlier solutions
```

Vào **Câu hỏi & đáp án**, tạo các câu có prompt `Paragraph A`, `Paragraph B`… rồi chọn heading đúng từ bank.

### 7.2. Matching Information

Chọn type **Matching Information**. Có thể:

- Dùng standard với lựa chọn riêng cho mỗi câu.
- Dùng kéo thả để nhập một bank chung.

Ví dụ bank là A–G, prompt là thông tin cần tìm, đáp án đúng là ký hiệu đoạn. Nếu một đoạn được dùng cho nhiều câu, đặt **Một đáp án được dùng nhiều lần**.

Chọn đúng `once/repeat` theo đề. UI kéo thả và autosave đã có xử lý; validation tại submit cuối chưa thống nhất đầy đủ, nên xem đây là phần đang cần hoàn thiện.

## 8. Completion và Short Answer nhập chữ

### 8.1. Một câu có một ô trống

Chọn **Fill in the Blanks / Completion**, đặt cách trả lời standard. Ví dụ:

```text
Prompt: The area is protected by a series of [blank].
Đáp án đúng: reservoirs
Giới hạn số từ: 1
```

Reading có thể thay `[blank]`, `[blank_12]` hoặc chuỗi gạch dưới bằng ô nhập của chính row câu hỏi. Số trong token của prompt không tạo row mới; dùng số câu đã nhập trong form.

Một prompt nên có một ô trả lời. Nhiều token trong cùng prompt đang cùng dùng một answer ID.

Listening standard hiện dùng prompt cùng ô nhập tách bên dưới, chưa có cùng renderer inline như Reading.

### 8.2. Short Answer

Chọn **Short Answer Questions**, nhập câu hỏi, đáp án đúng và giới hạn từ. Không cần token blank; giao diện có ô nhập trả lời.

### 8.3. Chấp nhận nhiều cách viết của cùng một đáp án

Các ví dụ hợp lệ với scorer hiện tại:

```text
center / centre
3 | three
colour; color
["center", "the center", "centre"]
```

Đây vẫn là một câu, thí sinh chỉ cần trả lời một cách viết phù hợp. Nếu đáp án thực tế có dấu `/`, `|` hoặc `;`, lưu ý parser đang coi chúng là dấu phân cách; cần xem xét trước khi dùng cho nội dung như phân số.

**Giới hạn hiện tại:** trường giới hạn từ chưa được kiểm tra ở scorer/backend. Không coi việc nhập “2” vào form là đã chặn đáp án dài hơn hai từ.

## 9. Completion kéo thả trong đoạn ghi chú

1. Chọn type **Fill in the Blanks / Completion**.
2. Chọn **Kéo thả từ ngân hàng đáp án**.
3. Chọn `once` hoặc `repeat`.
4. Trong **Đề bài / ghi chú / bảng có ô trống**, nhập:

   ```text
   The new scheme creates [blank_9] near the river.
   These areas protect [blank_10] from flooding.
   ```

5. Nhập bank trong tab **Ngân hàng đáp án**:

   ```text
   A | reservoirs
   B | downstream towns
   C | mountain roads
   ```

6. Bấm **Tạo câu từ [blank_N]** để tạo câu 9 và 10.
7. Chọn đáp án câu 9 = A, câu 10 = B.
8. Lưu.

Khi có blank tương ứng, prompt riêng của row có thể để trống. Mỗi `[blank_N]` phải xuất hiện đúng một lần và có đúng một câu số N. Nếu có câu dư hoặc blank không khớp, form báo lỗi.

`[blank]` không có số dùng cho prompt nhập chữ; `[blank_N]` có số dùng để nối blank của nội dung chung tới row câu. Hai trường hợp này không nên trộn lẫn.

Toolbar rich editor hiện không có công cụ tạo bảng/flow-chart chuyên biệt. Dạng nội dung đó chỉ được hỗ trợ một phần qua HTML/ảnh và các thành phần có sẵn.

## 10. Plan / Map / Diagram Labeling

Luồng đã có nhiều chức năng nhất là **kéo thả trên ảnh**:

1. Chọn **Plan / Map / Diagram Labeling**.
2. Chọn cách trả lời kéo thả.
3. Nhập `image_url` trỏ đến ảnh truy cập được.
4. Thêm bank A/B/C… hoặc các nhãn cần gán.
5. Tạo từng câu, chọn đáp án đúng.
6. Nhập **X** và **Y** theo phần trăm ảnh, 0–100.

Ví dụ X = 25, Y = 60 đặt tâm ô ở vị trí 25% chiều ngang và 60% chiều cao ảnh. Vị trí sẽ thay đổi theo kích thước hiển thị ảnh.

Chưa có công cụ bấm lên ảnh để tự điền tọa độ. Với dữ liệu legacy thiếu tọa độ, renderer rơi về danh sách đích trả lời; nếu không có câu nào đủ tọa độ thì partial hiện không render ảnh.

Chế độ standard chưa có đủ luồng hiển thị ảnh/ô trên ảnh. Không chỉ nhập URL ảnh rồi kỳ vọng map standard đã hoạt động như kéo thả.

## 11. Nhập Listening và audio

### 11.1. Câu hỏi và Part

- Part 1: câu 1–10.
- Part 2: câu 11–20.
- Part 3: câu 21–30.
- Part 4: câu 31–40.

Một Part có thể có nhiều group, nhưng một group nên nằm trọn trong một khoảng. Trang transcript hiện vẫn dùng `group.order`, nên nhiều group trong cùng Part có thể chưa được ghép đúng ở kết quả.

### 11.2. Audio

Trong tab **Audio & transcript**:

- **Tải audio lên:** MP3, WAV, OGG, M4A; tối đa 50 MB.
- **Dán link audio:** link trực tiếp đến file hoặc link Google Drive công khai.
- Với Drive, cần bật **Anyone with the link** và cho phép tải xuống. Hệ thống nhập file về storage.
- Nghe thử trong admin rồi lưu phần thi.
- **Bỏ chọn audio** chỉ bỏ URL đã chọn trong form.

Chuẩn bị một audio cho toàn bộ section: player hiện phát audio đầu tiên tìm thấy, không tự nối nhiều audio theo group. Khi chưa cấu hình audio, phòng thi còn fallback tiếng quán cà phê của demo; đó không phải bài nghe.

### 11.3. Transcript

Nhập lời bài nghe vào trường transcript. Có thể thêm các marker `(Q1)`, `(Q2)`… để trang kết quả gắn liên kết đến câu. Transcript được hiển thị trong kết quả, không phải nội dung phòng thi.

Audio kết thúc hiện có thể rút đồng hồ phía client xuống hai phút cuối. Reload chưa khôi phục tiến độ audio; cần lưu ý khi nghiệm thu bài nghe.

## 12. Writing

1. Tạo section skill Writing.
2. Dùng hai group theo Task 1 và Task 2.
3. Mỗi group chỉ thêm một câu cho Task đó. Form Writing ẩn bộ chọn question type; không cần chọn một dạng Reading/Listening.
4. Đánh số câu 1 và 2 để scorer nhận diện Task.
5. Nhập prompt, instruction và ảnh Task 1 nếu có.
6. Giao diện Writing hiện dùng mốc cố định 150 từ cho Task 1 và 250 từ cho Task 2 để đếm/nhắc; không chặn nhập. Nếu form có trường word limit, thay đổi trường này chưa đổi các mốc cố định của renderer.
7. Lưu và ghép vào test.

AI cần `GEMINI_API_KEY` ở môi trường server. Tuy nhiên Writing hiện chưa hoàn thiện: ảnh không được gửi qua lời gọi scorer này, hệ General Training chưa có nhánh riêng, và lỗi AI có điểm fallback. Xem [báo cáo Writing](QUESTION-TYPES.md#45-writing-còn-điểm-dự-phòng) trước khi sử dụng kết quả làm đánh giá chính thức.

## 13. Tạo hàng loạt và chỉnh sửa

### Tạo dãy câu

Nút **Tạo dãy câu hỏi** yêu cầu số bắt đầu và số lượng. Nó thêm row chưa có số tương ứng trong group; không tự điền prompt/đáp án. Validation khi lưu kiểm tra trùng giữa các group.

### Dán ngân hàng

Dùng định dạng một lựa chọn mỗi dòng:

```text
A | First choice
B | Second choice
C | Third choice
```

Có thể dán hai cột ngăn bằng tab. Danh sách được thêm vào dữ liệu đang có; key trùng sẽ bị báo lỗi. Với multi-select, dùng nút dán trong editor chọn nhiều; với kéo thả, dùng nút ở tab ngân hàng.

### Lời giải

- Câu thường: nhập `Trích dẫn chứa đáp án` và `Giải thích` tại từng câu.
- Multi-select: nhập một lần tại **Lời giải chung**.
- Kết quả hiển thị lời giải/trích dẫn từ các row tương ứng.

## 14. Bộ ba passage đã nhập bằng seeder

| Thuộc tính | Giá trị trong code |
| --- | --- |
| Seeder | `IeltsReadingImportSeeder` |
| Slug | `reading-multi-select-flood-gifted-museums-qa` |
| Passage 1 | Can We Hold Back the Flood? |
| Passage 2 | Gifted children and learning |
| Passage 3 | Museums of fine art and their public |
| Tổng câu | 35 |
| Thời gian | 55 phút |
| Nhóm chọn hai | Questions 7–8; B và D |

Đề này được tạo để thử tương tác kéo thả/chọn nhiều, với nhiều câu chuyển sang dạng kéo thả. Không dùng nó để kết luận mọi question type đã hoạt động đầy đủ. Band hiện vẫn tra raw trên thang 40, chưa điều chỉnh theo đề 35 câu.

Seeder là công cụ nhập chuyên biệt, không phải tính năng import file tổng quát trong admin. Chạy lại có thể ghi đè nội dung demo; không cần chạy lại chỉ để dùng bản sửa multi-select.

## 15. Kiểm tra nội dung trước khi bàn giao đề

Các bước sau là danh sách nghiệm thu đề cho người biên tập; chưa được thực hiện tự động bởi tài liệu này:

- Đúng thứ tự Passage/Part, số câu liên tục, không trùng và không thiếu.
- Passage nguồn chỉ có ở group đầu của mỗi Passage.
- Prompt/instruction có đủ thông tin trong chế độ trả lời đã chọn.
- Ngân hàng có cả đáp án đúng và nhiễu; cấu hình `once/repeat` phù hợp.
- Multi-select có đúng số key, đúng dãy câu, instruction không ép thứ tự.
- Mỗi `[blank_N]` nối đúng một câu; map có ảnh và tọa độ.
- Audio thật phát được; không dựa vào audio fallback demo.
- Section active, hệ thi phù hợp, test đã được xuất bản.
- Các giới hạn chưa hoàn thiện trong [báo cáo trạng thái](QUESTION-TYPES.md) đã được hiểu trước khi sử dụng bài làm/điểm.
