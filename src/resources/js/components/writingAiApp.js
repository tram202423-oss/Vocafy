/**
 * Alpine.js Component: writingAiApp
 * Quản lý giao diện, dữ liệu bài làm và gọi API chấm điểm bằng Gemini AI
 */
export default function writingAiApp() {
    return {
        stage: 'input', // 'input', 'analyzing', 'result'
        activeTab: 'analysis', // 'analysis', 'corrections', 'sample'
        examCategory: 'ielts_academic', // 'ielts_academic', 'ielts_general', 'toeic', 'toefl'
        essayType: 'ielts_academic_task2',
        topic: '',
        essay: '',
        copied: false,
        currentStep: 0,
        progressPercent: 0,

        // Sample topics presets for all 4 exam formats
        presets: [
            {
                tag: 'IELTS Academic Task 2',
                title: 'Is artificial intelligence beneficial or harmful to modern education?',
                type: 'ielts_academic_task2',
                topic: 'Some people believe that artificial intelligence will revolutionize modern education for the better, while others worry it will make students lazy and dependent. Discuss both views and give your opinion.'
            },
            {
                tag: 'IELTS Academic Task 1',
                title: 'Process of recycling plastic bottles into new products',
                type: 'ielts_academic_task1',
                topic: 'The diagram below shows the process of recycling plastic bottles to manufacture new products. Summarize the information by selecting and reporting the main features, and make comparisons where relevant.'
            },
            {
                tag: 'IELTS General Task 1',
                title: 'Letter of complaint to a hotel manager regarding poor service',
                type: 'ielts_general_task1',
                topic: 'You recently stayed at a hotel and were dissatisfied with the room service and noisy environment. Write a letter to the hotel manager explaining the problem, how it affected your stay, and what action you expect them to take.'
            },
            {
                tag: 'TOEIC Writing - Email',
                title: 'Replying to a customer about a delayed product shipment',
                type: 'toeic_email',
                topic: 'Write an email response to a customer who asked why their order of office supplies is late. Apologize for the delay, explain the reason (warehouse technical issue), and offer a revised delivery time along with a discount.'
            },
            {
                tag: 'TOEIC Writing - Essay',
                title: 'Working remotely from home vs Working in a traditional office',
                type: 'toeic_essay',
                topic: 'Many companies allow employees to work remotely from home. Do you prefer working from home or working in a traditional office environment? Support your opinion with reasons and examples.'
            },
            {
                tag: 'TOEFL iBT Discussion',
                title: 'Academic Discussion: Is higher education required for success?',
                type: 'toefl_academic_discussion',
                topic: 'Professor Smith: This week we discuss whether a university degree is essential for career success in today’s modern job market. Doctor Taylor believes practical skills matter more, while Professor Davis argues academic degrees remain essential. What is your perspective?'
            }
        ],

        loadingSteps: [
            { title: 'Phân tích ngữ pháp & chính tả', desc: 'Kiểm tra từng câu, thì động từ và cấu trúc cú pháp...' },
            { title: 'Đánh giá vốn từ vựng & Collocations', desc: 'Xác định từ vựng học thuật, chuyên ngành và mức độ tự nhiên...' },
            { title: 'Chấm điểm theo tiêu chuẩn kỳ thi đã chọn', desc: 'Tính toán điểm theo thang chuẩn IELTS / TOEIC / TOEFL...' },
            { title: 'Tổng hợp nhận xét & Bài viết mẫu', desc: 'Tạo phản hồi cá nhân hóa và phiên bản viết mẫu nâng cấp...' }
        ],

        // Mocked initial result structure
        result: {
            overallScore: 0,
            bandLevel: '',
            complexity: '',
            criteria: [],
            strengths: [],
            improvements: [],
            corrections: [],
            sampleEssay: ''
        },

        // Computed properties
        get examSummary() {
            switch(this.examCategory) {
                case 'ielts_academic': return 'IELTS Academic (2 tasks • 60 phút)';
                case 'ielts_general': return 'IELTS General (2 tasks • 60 phút)';
                case 'toeic': return 'TOEIC Writing (Email & Essay • ~60 phút)';
                case 'toefl': return 'TOEFL iBT (2 tasks • ~30 phút)';
                default: return '';
            }
        },

        get wordCount() {
            if (!this.essay || !this.essay.trim()) return 0;
            return this.essay.trim().split(/\s+/).filter(Boolean).length;
        },

        get readingTime() {
            const words = this.wordCount;
            if (words === 0) return 0;
            return Math.max(1, Math.ceil(words / 200));
        },

        get recommendedWords() {
            switch(this.essayType) {
                case 'ielts_academic_task1': return '150 - 200 từ (20 phút)';
                case 'ielts_academic_task2': return '250 - 350 từ (40 phút)';
                case 'ielts_general_task1': return '150 - 200 từ (20 phút)';
                case 'ielts_general_task2': return '250 - 350 từ (40 phút)';
                case 'toeic_email': return '100 - 150 từ (10-15 phút)';
                case 'toeic_essay': return '300 - 350 từ (30 phút)';
                case 'toefl_integrated': return '150 - 225 từ (20 phút)';
                case 'toefl_academic_discussion': return '100 - 150 từ (10 phút)';
                default: return '100+ từ';
            }
        },

        get isValidSubmission() {
            return this.topic.trim().length >= 10 && this.wordCount >= 15;
        },

        // Methods
        selectExamCategory(category) {
            this.examCategory = category;
            switch(category) {
                case 'ielts_academic': this.essayType = 'ielts_academic_task2'; break;
                case 'ielts_general': this.essayType = 'ielts_general_task2'; break;
                case 'toeic': this.essayType = 'toeic_essay'; break;
                case 'toefl': this.essayType = 'toefl_integrated'; break;
            }
        },

        applyPreset(preset) {
            this.essayType = preset.type;
            this.topic = preset.topic;
            if (preset.type.startsWith('ielts_academic')) this.examCategory = 'ielts_academic';
            else if (preset.type.startsWith('ielts_general')) this.examCategory = 'ielts_general';
            else if (preset.type.startsWith('toeic')) this.examCategory = 'toeic';
            else if (preset.type.startsWith('toefl')) this.examCategory = 'toefl';
        },

        loadRandomPreset() {
            const random = this.presets[Math.floor(Math.random() * this.presets.length)];
            this.applyPreset(random);
        },

        insertSampleEssay() {
            if (!this.topic) {
                this.applyPreset(this.presets[0]);
            }
            this.essay = `AI technology have become very popular in schools today. Many people think this brings great benefits, while others worry about its negative impacts.

On the one hand, students can learn many good information from online AI tools. It helps them solve difficult homework faster and practice skills effectively.

On the other hand, relying too much on machines might make students lazy. If they just copy answer from AI, they will lose their critical thinking ability.

In conclusion, I think AI is a good thing for everyone if we use it wisely.`;
        },

        async startAnalysis() {
            if (!this.isValidSubmission) return;

            this.stage = 'analyzing';
            this.currentStep = 0;
            this.progressPercent = 15;

            const stepInterval = setInterval(() => {
                if (this.currentStep < this.loadingSteps.length - 1) {
                    this.currentStep++;
                    this.progressPercent = Math.min(85, ((this.currentStep + 1) / this.loadingSteps.length) * 80);
                }
            }, 1000);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            try {
                const response = await fetch('/writing-ai/evaluate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        topic: this.topic,
                        essay: this.essay,
                        exam_category: this.examCategory,
                        essay_type: this.essayType
                    })
                });

                const resData = await response.json();

                clearInterval(stepInterval);
                this.currentStep = this.loadingSteps.length - 1;
                this.progressPercent = 100;

                if (resData.success && resData.data) {
                    this.result = resData.data;
                } else if (resData.message) {
                    alert(resData.message);
                }

                setTimeout(() => {
                    this.stage = 'result';
                    this.activeTab = 'analysis';
                }, 500);

            } catch (error) {
                clearInterval(stepInterval);
                console.error('Error evaluating essay:', error);
                alert('Có lỗi mạng hoặc máy chủ không phản hồi. Vui lòng thử lại sau.');
                setTimeout(() => {
                    this.stage = 'input';
                }, 500);
            }
        },

        copySampleEssay() {
            if (this.result && this.result.sampleEssay) {
                navigator.clipboard.writeText(this.result.sampleEssay);
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            }
        },

        resetForm() {
            this.stage = 'input';
            this.topic = '';
            this.essay = '';
            this.currentStep = 0;
            this.progressPercent = 0;
        }
    };
}
