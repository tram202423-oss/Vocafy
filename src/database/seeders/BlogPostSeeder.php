<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::first();
        if (!$author) {
            return;
        }

        $categories = [
            [
                'name' => 'Phương Pháp Học Từ Vựng',
                'slug' => 'phuong-phap-hoc-tu-vung',
                'description' => 'Các phương pháp ghi nhớ từ vựng tiếng Anh nhanh, sâu và không bị quên theo khoa học não bộ.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Mẹo Luyện Thi TOEIC & IELTS',
                'slug' => 'meo-luyen-thi-toeic-ielts',
                'description' => 'Chiến thuật làm bài thi thực chiến, mẹo tránh bẫy và bí quyết đạt điểm cao.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Ngữ Pháp Thực Hành',
                'slug' => 'ngu-phap-thuc-hanh',
                'description' => 'Học ngữ pháp tiếng Anh ứng dụng một cách dễ hiểu, trực quan và không khô khan.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Kinh Nghiệm & Chia Sẻ',
                'slug' => 'kinh-nghiem-va-chia-se',
                'description' => 'Góc truyền cảm hứng, lộ trình tự học và chia sẻ từ những người học thành công.',
                'sort_order' => 4,
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = PostCategory::firstOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'is_active' => true,
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }

        $posts = [
            [
                'category_slug' => 'phuong-phap-hoc-tu-vung',
                'title' => 'Bí quyết ghi nhớ 1000 từ vựng với phương pháp Spaced Repetition (Lặp lại ngắt quãng)',
                'slug' => 'bi-quyet-ghi-nho-1000-tu-vung-spaced-repetition',
                'excerpt' => 'Khám phá đường cong quên lãng Ebbinghaus và cách áp dụng phương pháp lặp lại ngắt quãng để nhớ từ vựng tiếng Anh vĩnh viễn vào trí nhớ dài hạn.',
                'content' => '<h2>Đường cong lãng quên là gì?</h2><p>Nhà tâm lý học người Đức Hermann Ebbinghaus đã chứng minh rằng sau 24 giờ kể từ khi học một thông tin mới, chúng ta sẽ quên đi tới <strong>70%</strong> những gì đã nạp vào nếu không có sự ôn tập kịp thời.</p><h3>Spaced Repetition giải quyết vấn đề này ra sao?</h3><p>Phương pháp <em>Spaced Repetition</em> (Lặp lại ngắt quãng) tận dụng các thời điểm não bộ sắp sửa quên để nhắc lại từ vựng. Mỗi lần bạn gợi nhớ thành công, khoảng cách giữa các lần ôn tập sau sẽ được kéo dài hơn.</p><ul><li><strong>Lần 1:</strong> Ngay sau khi học 20 phút</li><li><strong>Lần 2:</strong> Sau 1 ngày</li><li><strong>Lần 3:</strong> Sau 3 ngày</li><li><strong>Lần 4:</strong> Sau 1 tuần</li><li><strong>Lần 5:</strong> Sau 1 tháng</li></ul><p>Tại Vocafy, chúng tôi đã tích hợp sẵn thuật toán thông minh này vào tiến trình ôn tập từ vựng, giúp bạn tối ưu hóa thời gian và tăng hiệu quả học tập gấp 3 lần!</p>',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'views_count' => 342,
            ],
            [
                'category_slug' => 'meo-luyen-thi-toeic-ielts',
                'title' => 'Trọn bộ 600 từ vựng TOEIC thường gặp nhất trong đề thi format mới',
                'slug' => 'tron-bo-600-tu-vung-toeic-thuong-gap-nhat',
                'excerpt' => 'Tổng hợp các chủ điểm từ vựng kinh doanh, hợp đồng, văn phòng và mẹo xử lý nhanh câu hỏi Part 5 & Part 6 TOEIC.',
                'content' => '<h2>Tổng quan về từ vựng TOEIC Format Mới</h2><p>Đề thi TOEIC hiện nay tập trung nhiều hơn vào các ngữ cảnh làm việc thực tế trong môi trường doanh nghiệp quốc tế. Để đạt 650+ hay 800+ TOEIC, bạn cần nắm vững các nhóm từ vựng sau:</p><h3>1. Hợp đồng & Pháp lý (Contracts & Warranties)</h3><p>Các từ khóa như <em>agreement, specify, abide by, assurance, obligation</em> xuất hiện với tần suất cực cao ở phần đọc hiểu và cả phần nghe.</p><h3>2. Tiếp thị & Bán hàng (Marketing & Sales)</h3><p>Chú ý các collocation như <em>market research, attract consumers, launch a campaign, competitive price</em>.</p><p>Hãy lưu lại các chủ đề này trên Vocafy và luyện tập hằng ngày để làm chủ hoàn toàn các dạng bài thi nhé!</p>',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'views_count' => 521,
            ],
            [
                'category_slug' => 'ngu-phap-thuc-hanh',
                'title' => 'Phân biệt cách dùng In - On - At chỉ thời gian và nơi chốn cực dễ hiểu',
                'slug' => 'phan-biet-in-on-at-chi-thoi-gian-va-noi-chon',
                'excerpt' => 'Quy tắc tam giác ngược giúp bạn không bao giờ nhầm lẫn giữa In, On và At trong tiếng Anh giao tiếp và thi cử.',
                'content' => '<h2>Quy tắc hình kim tự tháp (Tam giác ngược)</h2><p>Để ghi nhớ giới từ <strong>IN - ON - AT</strong>, bạn chỉ cần nhớ nguyên tắc từ tổng quát đến chi tiết nhất:</p><h3>IN: Khái quát nhất (Lớn nhất)</h3><ul><li>Thời gian: Thế kỷ, thập kỷ, năm, tháng, mùa (<em>in 2026, in summer, in December</em>).</li><li>Nơi chốn: Quốc gia, thành phố, châu lục (<em>in Vietnam, in Hanoi</em>).</li></ul><h3>ON: Cụ thể hơn (Vừa phải)</h3><ul><li>Thời gian: Các ngày trong tuần, ngày tháng cụ thể (<em>on Monday, on September 25th</em>).</li><li>Nơi chốn: Tên đường, bề mặt (<em>on Oxford Street, on the table</em>).</li></ul><h3>AT: Cực kỳ cụ thể (Điểm mốc chính xác)</h3><ul><li>Thời gian: Giờ cụ thể (<em>at 8:00 AM, at midnight</em>).</li><li>Nơi chốn: Địa chỉ nhà cụ thể, vị trí xác định (<em>at 123 Main Street, at home, at the bus stop</em>).</li></ul>',
                'status' => 'published',
                'published_at' => now()->subHours(8),
                'views_count' => 189,
            ],
            [
                'category_slug' => 'kinh-nghiem-va-chia-se',
                'title' => 'Lộ trình 3 tháng tự học từ mất gốc lên giao tiếp tự tin mỗi ngày 15 phút',
                'slug' => 'lo-trinh-3-thang-tu-hoc-tu-mat-goc-len-giao-tiep-tu-tin',
                'excerpt' => 'Chia sẻ phương pháp xây dựng thói quen học tập nhỏ gọn, không gây áp lực nhưng đem lại tiến bộ bền bỉ.',
                'content' => '<h2>Sức mạnh của 15 phút mỗi ngày</h2><p>Nhiều người nghĩ rằng phải dành 2-3 tiếng mỗi ngày mới có thể giỏi tiếng Anh. Tuy nhiên, bí quyết cốt lõi của việc tiếp thu ngôn ngữ nằm ở <strong>tính liên tục (consistency)</strong> chứ không phải cường độ dồn dập.</p><h3>Tháng thứ 1: Chuẩn hóa phát âm và nạp 300 từ vựng cơ bản</h3><p>Hãy bắt đầu bằng bảng ký hiệu phiên âm IPA và 300 từ vựng căn bản nhất về cuộc sống hàng ngày. Dành 10 phút học từ mới trên Vocafy và 5 phút đọc to các ví dụ.</p><h3>Tháng thứ 2: Học cụm từ và mẫu câu thường gặp</h3><p>Đừng học từng từ đơn lẻ. Hãy học theo cụm (collocations) như <em>make a decision, take a break, have lunch</em>.</p><h3>Tháng thứ 3: Ứng dụng công nghệ AI để sửa phát âm và luyện viết</h3><p>Tận dụng tính năng Writing AI trên Vocafy để viết những đoạn nhật ký ngắn 3-5 câu mỗi ngày và nhận phản hồi chi tiết ngay lập tức!</p>',
                'status' => 'published',
                'published_at' => now()->subHours(2),
                'views_count' => 278,
            ],
        ];

        foreach ($posts as $postData) {
            $catSlug = $postData['category_slug'];
            unset($postData['category_slug']);

            if (isset($categoryModels[$catSlug])) {
                Post::firstOrCreate(
                    ['slug' => $postData['slug']],
                    array_merge($postData, [
                        'post_category_id' => $categoryModels[$catSlug]->id,
                        'user_id' => $author->id,
                        'meta_title' => $postData['title'],
                        'meta_description' => $postData['excerpt'],
                    ])
                );
            }
        }
    }
}
