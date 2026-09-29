<?php

namespace Database\Seeders;

use App\Enums\IeltsQuestionTypeEnum;
use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use App\Models\IeltsBandScore;
use App\Models\IeltsQuestion;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IeltsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBandScores();
        $this->seedSampleTests();
    }

    private function seedBandScores(): void
    {
        // Listening (0-40)
        $listeningTable = [
            40 => 9.0, 39 => 9.0, 38 => 8.5, 37 => 8.5, 36 => 8.0, 35 => 8.0,
            34 => 7.5, 33 => 7.5, 32 => 7.5, 31 => 7.0, 30 => 7.0, 29 => 6.5,
            28 => 6.5, 27 => 6.5, 26 => 6.5, 25 => 6.0, 24 => 6.0, 23 => 6.0,
            22 => 5.5, 21 => 5.5, 20 => 5.5, 19 => 5.5, 18 => 5.5, 17 => 5.0,
            16 => 5.0, 15 => 4.5, 14 => 4.5, 13 => 4.5, 12 => 4.0, 11 => 4.0,
            10 => 4.0, 9 => 3.5, 8 => 3.5, 7 => 3.0, 6 => 3.0, 5 => 2.5,
            4 => 2.5, 3 => 2.0, 2 => 2.0, 1 => 1.0, 0 => 0.0,
        ];

        foreach ($listeningTable as $raw => $band) {
            IeltsBandScore::updateOrCreate(
                ['skill' => 'listening', 'test_type' => 'academic', 'raw_score' => $raw],
                ['band_score' => $band]
            );
            IeltsBandScore::updateOrCreate(
                ['skill' => 'listening', 'test_type' => 'general_training', 'raw_score' => $raw],
                ['band_score' => $band]
            );
        }

        // Reading Academic (0-40)
        $academicReadingTable = [
            40 => 9.0, 39 => 9.0, 38 => 8.5, 37 => 8.5, 36 => 8.0, 35 => 8.0,
            34 => 7.5, 33 => 7.5, 32 => 7.0, 31 => 7.0, 30 => 7.0, 29 => 6.5,
            28 => 6.5, 27 => 6.5, 26 => 6.0, 25 => 6.0, 24 => 6.0, 23 => 6.0,
            22 => 5.5, 21 => 5.5, 20 => 5.5, 19 => 5.5, 18 => 5.0, 17 => 5.0,
            16 => 5.0, 15 => 5.0, 14 => 4.5, 13 => 4.5, 12 => 4.0, 11 => 4.0,
            10 => 4.0, 9 => 3.5, 8 => 3.5, 7 => 3.0, 6 => 3.0, 5 => 2.5,
            4 => 2.5, 3 => 2.0, 2 => 2.0, 1 => 1.0, 0 => 0.0,
        ];

        foreach ($academicReadingTable as $raw => $band) {
            IeltsBandScore::updateOrCreate(
                ['skill' => 'reading', 'test_type' => 'academic', 'raw_score' => $raw],
                ['band_score' => $band]
            );
        }

        // Reading General (0-40)
        $generalReadingTable = [
            40 => 9.0, 39 => 8.5, 38 => 8.0, 37 => 8.0, 36 => 7.5, 35 => 7.0,
            34 => 7.0, 33 => 6.5, 32 => 6.5, 31 => 6.0, 30 => 6.0, 29 => 5.5,
            28 => 5.5, 27 => 5.5, 26 => 5.0, 25 => 5.0, 24 => 5.0, 23 => 5.0,
            22 => 4.5, 21 => 4.5, 20 => 4.5, 19 => 4.5, 18 => 4.0, 17 => 4.0,
            16 => 4.0, 15 => 4.0, 14 => 3.5, 13 => 3.5, 12 => 3.5, 11 => 3.0,
            10 => 3.0, 9 => 3.0, 8 => 3.0, 7 => 2.5, 6 => 2.5, 5 => 2.5,
            4 => 2.0, 3 => 2.0, 2 => 2.0, 1 => 1.0, 0 => 0.0,
        ];

        foreach ($generalReadingTable as $raw => $band) {
            IeltsBandScore::updateOrCreate(
                ['skill' => 'reading', 'test_type' => 'general_training', 'raw_score' => $raw],
                ['band_score' => $band]
            );
        }
    }

    private function seedSampleTests(): void
    {
        // 1. Tạo Full Test
        $test = IeltsTest::updateOrCreate(
            ['slug' => 'cambridge-ielts-18-academic-test-1'],
            [
                'title' => 'Cambridge IELTS 18 - Academic Test 1',
                'type' => IeltsTestTypeEnum::ACADEMIC,
                'description' => 'Bộ đề thi mẫu chuẩn định dạng IELTS trên máy tính của IDP / British Council gồm đầy đủ các kỹ năng Reading & Listening.',
                'duration_minutes' => 90,
                'is_published' => true,
                'total_questions' => 40,
            ]
        );

        // 2. Tạo Section Reading
        $readingSection = IeltsSection::updateOrCreate(
            ['title' => 'Academic Reading - Test 1'],
            [
                'skill' => IeltsSkillEnum::READING,
                'test_type' => IeltsTestTypeEnum::ACADEMIC,
                'time_limit_minutes' => 60,
                'total_questions' => 40,
                'description' => 'Reading Section gồm 3 passages (40 câu hỏi) kéo dài 60 phút.',
                'is_active' => true,
            ]
        );

        $test->sections()->syncWithoutDetaching([
            $readingSection->id => ['order' => 1]
        ]);

        // PASSAGE 1: Urban Farming
        $p1 = IeltsQuestionGroup::updateOrCreate(
            [
                'ielts_section_id' => $readingSection->id,
                'order' => 1,
            ],
            [
                'title' => 'Passage 1: Urban Farming and Vertical Agriculture',
                'passage_content' => <<<HTML
<h3>Urban Farming: The High-Rise Future of Food</h3>
<p id="para-A"><strong>A.</strong> By the year 2050, nearly 80% of the Earth's population will reside in urban centres. Applying conservative estimates to current demographic trends, the human population will increase by about 3 billion people during this period. An estimated 109 hectares of new land will be needed to grow enough food to feed them, if traditional farming methods continue to be practised as they are at present. Throughout history, vast swathes of fertile land have been degraded or destroyed through mismanagement and climate-related crises, rendering them useless for cultivation.</p>

<p id="para-B"><strong>B.</strong> The concept of indoor farming is not new, since hothouse production of tomatoes and other produce has been in vogue for some time. What is new is the urgent need to scale up this technology to accommodate another three billion people. Many believe an entirely new approach to indoor farming is required, employing cutting-edge technologies. One such proposal is for 'Vertical Farming'. This visionary concept envisions multi-storey buildings in the heart of cities where food crops are produced year-round under controlled environments.</p>

<p id="para-C"><strong>C.</strong> Vertical farming offers numerous compelling advantages over conventional field agriculture. First, crops would be produced all year round, as they would be sheltered from weather extremes like droughts, floods, and unseasonable frosts. Second, all food grown indoors could be organic, eliminating the need for herbicides, pesticides, and chemical fertilizers. The system would consume 70% to 95% less water through innovative recycling and hydroponic systems. Furthermore, growing food inside urban boundaries would drastically slash transportation emissions generated by hauling farm products hundreds or thousands of kilometres to city supermarkets.</p>

<p id="para-D"><strong>D.</strong> However, vertical farming is not without severe challenges. The foremost obstacle is the astronomical initial capital expenditure required to construct multi-level indoor growing facilities in high-density urban areas where real estate values are steep. In addition, the massive electrical consumption demanded by artificial LED grow lights to substitute for natural sunlight presents a substantial ecological and economic footprint, unless renewable energy sources are readily accessible.</p>
HTML,
                'question_type' => IeltsQuestionTypeEnum::TRUE_FALSE_NOT_GIVEN,
                'instruction' => 'Questions 1–6: Do the following statements agree with the information given in Reading Passage 1? Write TRUE, FALSE, or NOT GIVEN.',
            ]
        );

        // Questions 1 to 6
        $qData1 = [
            [
                'num' => 1,
                'prompt' => 'Human population is expected to expand by around three billion individuals by 2050.',
                'correct' => 'TRUE',
                'explanation' => 'Đoạn A ghi rõ: "the human population will increase by about 3 billion people during this period (by 2050)".',
                'quote' => 'the human population will increase by about 3 billion people during this period',
            ],
            [
                'num' => 2,
                'prompt' => 'Indoor farming technology was only recently invented within the past five years.',
                'correct' => 'FALSE',
                'explanation' => 'Đoạn B khẳng định: "The concept of indoor farming is not new, since hothouse production of tomatoes and other produce has been in vogue for some time."',
                'quote' => 'The concept of indoor farming is not new, since hothouse production of tomatoes and other produce has been in vogue for some time.',
            ],
            [
                'num' => 3,
                'prompt' => 'Vertical farming requires greater amounts of water than traditional soil-based agriculture.',
                'correct' => 'FALSE',
                'explanation' => 'Đoạn C nêu: "The system would consume 70% to 95% less water through innovative recycling and hydroponic systems."',
                'quote' => 'The system would consume 70% to 95% less water through innovative recycling',
            ],
            [
                'num' => 4,
                'prompt' => 'Crops grown in vertical farms are less prone to damage from unseasonal weather.',
                'correct' => 'TRUE',
                'explanation' => 'Đoạn C chỉ ra: "crops would be produced all year round, as they would be sheltered from weather extremes like droughts, floods, and unseasonable frosts."',
                'quote' => 'sheltered from weather extremes like droughts, floods, and unseasonable frosts',
            ],
            [
                'num' => 5,
                'prompt' => 'Most city residents prefer to buy vegetables grown on vertical farms over organic farm produce.',
                'correct' => 'NOT GIVEN',
                'explanation' => 'Trong bài đọc không có bất kỳ thông tin nào đề cập đến sở thích mua hàng của người dân thành phố.',
                'quote' => null,
            ],
            [
                'num' => 6,
                'prompt' => 'The high cost of urban property is a major hurdle in establishing vertical farms.',
                'correct' => 'TRUE',
                'explanation' => 'Đoạn D giải thích: "The foremost obstacle is the astronomical initial capital expenditure required to construct multi-level indoor growing facilities in high-density urban areas where real estate values are steep."',
                'quote' => 'in high-density urban areas where real estate values are steep',
            ],
        ];

        foreach ($qData1 as $item) {
            IeltsQuestion::updateOrCreate(
                [
                    'ielts_question_group_id' => $p1->id,
                    'question_number' => $item['num'],
                ],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'quote_reference' => $item['quote'],
                    'options' => [
                        ['key' => 'TRUE', 'text' => 'TRUE'],
                        ['key' => 'FALSE', 'text' => 'FALSE'],
                        ['key' => 'NOT GIVEN', 'text' => 'NOT GIVEN'],
                    ],
                ]
            );
        }

        // Questions 7 to 13 (Fill in the blanks)
        $p1Group2 = IeltsQuestionGroup::updateOrCreate(
            [
                'ielts_section_id' => $readingSection->id,
                'order' => 2,
            ],
            [
                'title' => 'Passage 1: Summary Completion',
                'passage_content' => null,
                'question_type' => IeltsQuestionTypeEnum::FILL_IN_BLANKS,
                'instruction' => 'Questions 7–13: Complete the notes below. Choose ONE WORD ONLY from the passage for each answer.',
            ]
        );

        $qData2 = [
            [
                'num' => 7,
                'prompt' => 'By 2050, roughly four-fifths of the human populace will live in [blank] areas.',
                'correct' => 'urban',
                'explanation' => 'Đoạn A: "nearly 80% of the Earth\'s population will reside in urban centres". Four-fifths = 80%.',
                'quote' => 'nearly 80% of the Earth\'s population will reside in urban centres',
                'word_limit' => 1,
            ],
            [
                'num' => 8,
                'prompt' => 'Over history, fertile land has suffered degradation caused by climate emergencies and poor [blank].',
                'correct' => 'mismanagement',
                'explanation' => 'Đoạn A: "fertile land have been degraded or destroyed through mismanagement and climate-related crises".',
                'quote' => 'through mismanagement and climate-related crises',
                'word_limit' => 1,
            ],
            [
                'num' => 9,
                'prompt' => 'Vertical farming imagines food cultivation occurring within [blank] buildings.',
                'correct' => 'multi-storey',
                'explanation' => 'Đoạn B: "This visionary concept envisions multi-storey buildings in the heart of cities".',
                'quote' => 'multi-storey buildings in the heart of cities',
                'word_limit' => 1,
            ],
            [
                'num' => 10,
                'prompt' => 'Indoor food production completely removes the necessity for chemical [blank].',
                'correct' => 'fertilizers',
                'explanation' => 'Đoạn C: "eliminating the need for herbicides, pesticides, and chemical fertilizers".',
                'quote' => 'eliminating the need for herbicides, pesticides, and chemical fertilizers',
                'word_limit' => 1,
            ],
            [
                'num' => 11,
                'prompt' => 'Water usage would be significantly cut using closed-loop recycling and [blank] methods.',
                'correct' => 'hydroponic',
                'explanation' => 'Đoạn C: "70% to 95% less water through innovative recycling and hydroponic systems".',
                'quote' => 'innovative recycling and hydroponic systems',
                'word_limit' => 1,
            ],
            [
                'num' => 12,
                'prompt' => 'Local production minimizes carbon [blank] by cutting long-distance vehicle transport.',
                'correct' => 'emissions',
                'explanation' => 'Đoạn C: "drastically slash transportation emissions generated by hauling farm products".',
                'quote' => 'drastically slash transportation emissions',
                'word_limit' => 1,
            ],
            [
                'num' => 13,
                'prompt' => 'A primary challenge involves powering artificial [blank] used in place of daylight.',
                'correct' => 'lights',
                'explanation' => 'Đoạn D: "electrical consumption demanded by artificial LED grow lights to substitute for natural sunlight".',
                'quote' => 'artificial LED grow lights to substitute for natural sunlight',
                'word_limit' => 1,
            ],
        ];

        foreach ($qData2 as $item) {
            IeltsQuestion::updateOrCreate(
                [
                    'ielts_question_group_id' => $p1Group2->id,
                    'question_number' => $item['num'],
                ],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'quote_reference' => $item['quote'],
                    'word_limit' => $item['word_limit'],
                ]
            );
        }

        // PASSAGE 2: Forest Management & Biodiversity
        $p2 = IeltsQuestionGroup::updateOrCreate(
            [
                'ielts_section_id' => $readingSection->id,
                'order' => 3,
            ],
            [
                'title' => 'Passage 2: Sustainable Forestry and Ecosystem Stewardship',
                'passage_content' => <<<HTML
<h3>Ecosystem Stewardship: Managing Woodlands in the Anthropocene</h3>
<p id="p2-A"><strong>Paragraph A:</strong> Forests cover nearly a third of the planet's landmass, serving as pivotal carbon sinks, habitats for 80% of terrestrial amphibian, avian, and mammal species, and livelihoods for over a billion rural inhabitants. However, deforestation and ill-conceived commercial logging have degraded immense expanses of ancient timberlands. Modern silviculture is transitioning from timber maximization towards holistic stewardship that balances wood production with biodiversity preservation.</p>

<p id="p2-B"><strong>Paragraph B:</strong> One revolutionary method is continuous cover forestry (CCF). Unlike clear-cutting, which wipes out all vegetation on an entire mountainside, CCF selectively harvests individual trees while retaining an uneven-aged canopy. This maintains microclimatic humidity, shields forest soil from severe erosion during torrential downpours, and protects sensitive subterranean mycorrhizal fungal networks that shuttle nutrients between flora.</p>

<p id="p2-C"><strong>Paragraph C:</strong> Wildlife corridors represent another vital intervention. Human fragmentation of natural ecosystems by constructing highway networks and sprawling residential developments has isolated species into tiny genetic islands. By systematically linking isolated forest patches with contiguous green belts, species such as wildcats, deer, and endangered reptiles can safely migrate, forage, and mate without human interference.</p>

<p id="p2-D"><strong>Paragraph D:</strong> Community-based forest management in developing nations has proven substantially more durable than state enforcement. When local indigenous communities are granted legal tenure and a stake in carbon credit revenues, illegal logging plummets dramatically compared to national parks guarded solely by underpaid rangers.</p>
HTML,
                'question_type' => IeltsQuestionTypeEnum::MATCHING_HEADINGS,
                'instruction' => 'Questions 14–17: Reading Passage 2 has four paragraphs, A–D. Choose the correct heading for each paragraph from the list of headings below.',
                'settings' => [
                    'headings' => [
                        'i' => 'A selective harvesting technique preserving ground cover and fungi',
                        'ii' => 'The commercial profitability of monoculture timber planting',
                        'iii' => 'Connecting fragmented habitats to ensure genetic survival',
                        'iv' => 'Empowering indigenous populations produces superior conservation outcomes',
                        'v' => 'The vast ecological importance of woodland and shift in management philosophy',
                        'vi' => 'The total elimination of wildfire risks through drone patrols',
                    ]
                ],
            ]
        );

        $qData3 = [
            [
                'num' => 14,
                'prompt' => 'Paragraph A',
                'correct' => 'v',
                'explanation' => 'Đoạn A bàn về tầm quan trọng của rừng (carbon sinks, 80% habitat) và sự chuyển dịch triết lý quản lý rừng (timber maximization -> holistic stewardship).',
                'quote' => 'transitioning from timber maximization towards holistic stewardship that balances wood production with biodiversity preservation',
            ],
            [
                'num' => 15,
                'prompt' => 'Paragraph B',
                'correct' => 'i',
                'explanation' => 'Đoạn B mô tả phương pháp continuous cover forestry (CCF) chọn lọc từng cây, giữ tán cây và bảo vệ mạng lưới nấm ngầm (mycorrhizal fungal networks).',
                'quote' => 'selectively harvests individual trees while retaining an uneven-aged canopy',
            ],
            [
                'num' => 16,
                'prompt' => 'Paragraph C',
                'correct' => 'iii',
                'explanation' => 'Đoạn C phân tích các hành lang động vật (wildlife corridors) kết nối các mảnh sinh cảnh phân mảnh giúp duy trì nguồn gen.',
                'quote' => 'linking isolated forest patches with contiguous green belts... without human interference',
            ],
            [
                'num' => 17,
                'prompt' => 'Paragraph D',
                'correct' => 'iv',
                'explanation' => 'Đoạn D khẳng định trao quyền cho cộng đồng bản địa (indigenous communities) mang lại hiệu quả bảo tồn vượt trội.',
                'quote' => 'When local indigenous communities are granted legal tenure and a stake in carbon credit revenues',
            ],
        ];

        foreach ($qData3 as $item) {
            IeltsQuestion::updateOrCreate(
                [
                    'ielts_question_group_id' => $p2->id,
                    'question_number' => $item['num'],
                ],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'quote_reference' => $item['quote'],
                    'options' => [
                        ['key' => 'i', 'text' => 'i. A selective harvesting technique preserving ground cover and fungi'],
                        ['key' => 'ii', 'text' => 'ii. The commercial profitability of monoculture timber planting'],
                        ['key' => 'iii', 'text' => 'iii. Connecting fragmented habitats to ensure genetic survival'],
                        ['key' => 'iv', 'text' => 'iv. Empowering indigenous populations produces superior conservation outcomes'],
                        ['key' => 'v', 'text' => 'v. The vast ecological importance of woodland and shift in management philosophy'],
                        ['key' => 'vi', 'text' => 'vi. The total elimination of wildfire risks through drone patrols'],
                    ],
                ]
            );
        }

        // Questions 18-26: Multiple choice & Sentence completion
        $p2Group2 = IeltsQuestionGroup::updateOrCreate(
            [
                'ielts_section_id' => $readingSection->id,
                'order' => 4,
            ],
            [
                'title' => 'Passage 2: Sentence Completion',
                'passage_content' => null,
                'question_type' => IeltsQuestionTypeEnum::FILL_IN_BLANKS,
                'instruction' => 'Questions 18–26: Complete the sentences below. Choose NO MORE THAN TWO WORDS from the passage for each answer.',
            ]
        );

        $qData4 = [
            ['num' => 18, 'prompt' => 'Forests function as critical carbon [blank] while harboring the majority of land animal species.', 'correct' => 'sinks', 'explanation' => 'Đoạn A: serving as pivotal carbon sinks', 'quote' => 'pivotal carbon sinks'],
            ['num' => 19, 'prompt' => 'Modern forestry is moving away from purely aiming for timber [blank].', 'correct' => 'maximization', 'explanation' => 'Đoạn A: transitioning from timber maximization', 'quote' => 'transitioning from timber maximization'],
            ['num' => 20, 'prompt' => 'Unlike CCF, the practice of [blank] removes all trees simultaneously.', 'correct' => 'clear-cutting', 'explanation' => 'Đoạn B: Unlike clear-cutting, which wipes out all vegetation', 'quote' => 'Unlike clear-cutting'],
            ['num' => 21, 'prompt' => 'A selective canopy helps protect woodland soil against [blank] during heavy rainstorms.', 'correct' => 'erosion', 'explanation' => 'Đoạn B: shields forest soil from severe erosion', 'quote' => 'severe erosion during torrential downpours'],
            ['num' => 22, 'prompt' => 'Underground networks composed of [blank] facilitate the transfer of food among plants.', 'correct' => 'fungi', 'explanation' => 'Đoạn B: mycorrhizal fungal networks that shuttle nutrients between flora.', 'quote' => 'mycorrhizal fungal networks'],
            ['num' => 23, 'prompt' => 'Urban expansion and road construction have created isolated [blank] for wildlife populations.', 'correct' => 'islands', 'explanation' => 'Đoạn C: isolated species into tiny genetic islands.', 'quote' => 'into tiny genetic islands'],
            ['num' => 24, 'prompt' => 'Corridors are formed by joining distinct forest parcels using [blank] corridors or belts.', 'correct' => 'green belts', 'explanation' => 'Đoạn C: linking isolated forest patches with contiguous green belts', 'quote' => 'contiguous green belts'],
            ['num' => 25, 'prompt' => 'Local stewardship has proven more resilient than mere [blank] oversight.', 'correct' => 'state', 'explanation' => 'Đoạn D: proven substantially more durable than state enforcement.', 'quote' => 'substantially more durable than state enforcement'],
            ['num' => 26, 'prompt' => 'Giving communities a share of [blank] revenues encourages long-term preservation.', 'correct' => 'carbon credit', 'explanation' => 'Đoạn D: a stake in carbon credit revenues', 'quote' => 'a stake in carbon credit revenues'],
        ];

        foreach ($qData4 as $item) {
            IeltsQuestion::updateOrCreate(
                [
                    'ielts_question_group_id' => $p2Group2->id,
                    'question_number' => $item['num'],
                ],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'quote_reference' => $item['quote'],
                    'word_limit' => 2,
                ]
            );
        }

        // PASSAGE 3: Cognitive Science & Artificial Intelligence
        $p3 = IeltsQuestionGroup::updateOrCreate(
            [
                'ielts_section_id' => $readingSection->id,
                'order' => 5,
            ],
            [
                'title' => 'Passage 3: The Enigma of Human and Machine Cognition',
                'passage_content' => <<<HTML
<h3>Deconstructing Intelligence: From Neurons to Silicon</h3>
<p id="p3-1">The fundamental definition of intelligence has provoked fierce debate among philosophers, psychologists, and computer scientists for generations. Early 20th-century psychologists such as Charles Spearman proposed the concept of general cognitive ability, known as the 'g factor', positing that individual proficiency in mathematical logic correlates strongly with verbal dexterity and spatial reasoning. However, this unitary perspective was later challenged by Howard Gardner's theory of multiple intelligences, which recognizes distinct modalities including musical, kinesthetic, and interpersonal aptitudes.</p>

<p id="p3-2">The emergence of contemporary artificial intelligence, specifically transformer-based large language models (LLMs), has intensified this inquiry. Modern neural networks can synthesize complex legal briefs, diagnose rare dermatological conditions with uncanny precision, and compose sonnets mimicking Shakespeare. Yet cognitive scientists caution against conflating statistical pattern recognition with genuine comprehension. Deep learning systems operate through calculating probabilistic distributions across massive corpora of tokens. They do not possess subjective phenomenological experience, nor do they formulate authentic semantic intent.</p>

<p id="p3-3">Philosopher John Searle's seminal 'Chinese Room' thought experiment remains remarkably pertinent. Searle argued that an individual inside an enclosed room manipulating Chinese ideograms according to a precise set of syntactical rules could convince external observers that they understand Chinese, despite having zero semantic awareness of what the characters convey. In much the same manner, contemporary neural models manipulate syntax flawlessly while remaining entirely devoid of genuine semantic consciousness.</p>

<p id="p3-4">Looking ahead, neuroscientists argue that true artificial general intelligence (AGI) requires embodiment—interaction with physical reality through sensors and actuators that ground linguistic symbols in sensory-motor interactions. Without sensorimotor grounding, machines will remain powerful linguistic simulators rather than sentient, reasoning minds.</p>
HTML,
                'question_type' => IeltsQuestionTypeEnum::MULTIPLE_CHOICE,
                'instruction' => 'Questions 27–40: Choose the correct letter, A, B, C or D.',
            ]
        );

        $qData5 = [
            [
                'num' => 27,
                'prompt' => 'What was Charles Spearman\'s principal view regarding human intelligence?',
                'options' => [
                    ['key' => 'A', 'text' => 'Cognitive abilities operate entirely independently of one another.'],
                    ['key' => 'B', 'text' => 'A singular general factor underlies performance across varied mental tasks.'],
                    ['key' => 'C', 'text' => 'Intelligence is overwhelmingly determined by musical and kinesthetic skills.'],
                    ['key' => 'D', 'text' => 'Language ability cannot be measured using standardized psychometric testing.'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 1 nêu rõ: Spearman đề xuất general cognitive ability (\'g factor\') cho rằng năng lực trong logic tương quan chặt chẽ với ngôn ngữ và không gian.',
                'quote' => 'individual proficiency in mathematical logic correlates strongly with verbal dexterity and spatial reasoning',
            ],
            [
                'num' => 28,
                'prompt' => 'Why did Howard Gardner criticize Spearman\'s theory?',
                'options' => [
                    ['key' => 'A', 'text' => 'Because Gardner believed intelligence is composed of multiple distinct aptitudes.'],
                    ['key' => 'B', 'text' => 'Because Spearman ignored animal neurological data.'],
                    ['key' => 'C', 'text' => 'Because mathematical skills cannot be learned.'],
                    ['key' => 'D', 'text' => 'Because Gardner favored silicon-based computation over biological brains.'],
                ],
                'correct' => 'A',
                'explanation' => 'Đoạn 1: "this unitary perspective was later challenged by Howard Gardner\'s theory of multiple intelligences, which recognizes distinct modalities".',
                'quote' => 'Gardner\'s theory of multiple intelligences, which recognizes distinct modalities',
            ],
            [
                'num' => 29,
                'prompt' => 'What is the primary mechanism through which large language models operate?',
                'options' => [
                    ['key' => 'A', 'text' => 'They experience consciousness and emotional empathy.'],
                    ['key' => 'B', 'text' => 'They calculate probability distributions across vast text datasets.'],
                    ['key' => 'C', 'text' => 'They follow biological evolutionary genetics.'],
                    ['key' => 'D', 'text' => 'They memorize human memories through sensory touch.'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 2: "Deep learning systems operate through calculating probabilistic distributions across massive corpora of tokens."',
                'quote' => 'calculating probabilistic distributions across massive corpora of tokens',
            ],
            [
                'num' => 30,
                'prompt' => 'What did John Searle illustrate with the "Chinese Room" thought experiment?',
                'options' => [
                    ['key' => 'A', 'text' => 'That Chinese is more complex than other world languages.'],
                    ['key' => 'B', 'text' => 'Syntactic manipulation does not equal genuine semantic comprehension.'],
                    ['key' => 'C', 'text' => 'Computers will eventually develop biological emotions.'],
                    ['key' => 'D', 'text' => 'Human translators are less reliable than algorithms.'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 3: "manipulating Chinese ideograms according to syntactical rules... zero semantic awareness... manipulate syntax flawlessly while remaining entirely devoid of genuine semantic consciousness".',
                'quote' => 'manipulate syntax flawlessly while remaining entirely devoid of genuine semantic consciousness',
            ],
            [
                'num' => 31,
                'prompt' => 'According to modern neuroscientists, what is indispensable for achieving AGI?',
                'options' => [
                    ['key' => 'A', 'text' => 'Access to larger financial investments.'],
                    ['key' => 'B', 'text' => 'Embodied interaction grounding language in physical reality.'],
                    ['key' => 'C', 'text' => 'Removing all ethical regulations on computing hardware.'],
                    ['key' => 'D', 'text' => 'Replacing transformer architectures with older decision trees.'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 4: "true artificial general intelligence (AGI) requires embodiment—interaction with physical reality through sensors and actuators".',
                'quote' => 'true artificial general intelligence (AGI) requires embodiment',
            ],
            [
                'num' => 32,
                'prompt' => 'Spearman believed performance in logic correlates with verbal competence.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'A',
                'explanation' => 'Đoạn 1 nêu rõ: individual proficiency in mathematical logic correlates strongly with verbal dexterity.',
                'quote' => 'proficiency in mathematical logic correlates strongly with verbal dexterity',
            ],
            [
                'num' => 33,
                'prompt' => 'Gardner considered musical talent to be an independent form of intelligence.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'A',
                'explanation' => 'Đoạn 1: distinct modalities including musical, kinesthetic, and interpersonal aptitudes.',
                'quote' => 'distinct modalities including musical',
            ],
            [
                'num' => 34,
                'prompt' => 'LLMs currently experience genuine feelings of joy and sorrow when interacting with humans.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 2 khẳng định: They do not possess subjective phenomenological experience, nor do they formulate authentic semantic intent.',
                'quote' => 'They do not possess subjective phenomenological experience',
            ],
            [
                'num' => 35,
                'prompt' => 'Medical doctors have universally rejected AI diagnostic systems in hospital settings.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'C',
                'explanation' => 'Bài đọc nói AI có thể chẩn đoán da liễu với độ chính xác cao nhưng không đề cập việc các bác sĩ có hoàn toàn từ chối hay không.',
                'quote' => null,
            ],
            [
                'num' => 36,
                'prompt' => 'The person inside Searle\'s Chinese Room understood both spoken and written Chinese perfectly.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 3: having zero semantic awareness of what the characters convey.',
                'quote' => 'having zero semantic awareness of what the characters convey',
            ],
            [
                'num' => 37,
                'prompt' => 'Sensory-motor grounding enables concepts to connect to physical interactions.',
                'options' => [
                    ['key' => 'A', 'text' => 'YES'],
                    ['key' => 'B', 'text' => 'NO'],
                    ['key' => 'C', 'text' => 'NOT GIVEN'],
                ],
                'correct' => 'A',
                'explanation' => 'Đoạn 4: ground linguistic symbols in sensory-motor interactions.',
                'quote' => 'ground linguistic symbols in sensory-motor interactions',
            ],
            [
                'num' => 38,
                'prompt' => 'Without embodiment, neural networks remain primarily:',
                'options' => [
                    ['key' => 'A', 'text' => 'Conscious moral entities'],
                    ['key' => 'B', 'text' => 'Linguistic simulators'],
                    ['key' => 'C', 'text' => 'Biological duplicates'],
                    ['key' => 'D', 'text' => 'Sentient reasoning minds'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 4: Without sensorimotor grounding, machines will remain powerful linguistic simulators rather than sentient, reasoning minds.',
                'quote' => 'remain powerful linguistic simulators rather than sentient, reasoning minds',
            ],
            [
                'num' => 39,
                'prompt' => 'Which word best describes the tone of cognitive scientists regarding current AI comprehension?',
                'options' => [
                    ['key' => 'A', 'text' => 'Unconditionally enthusiastic'],
                    ['key' => 'B', 'text' => 'Cautious and analytical'],
                    ['key' => 'C', 'text' => 'Aggressively dismissive'],
                    ['key' => 'D', 'text' => 'Indifferent and disinterested'],
                ],
                'correct' => 'B',
                'explanation' => 'Đoạn 2 nêu: Yet cognitive scientists caution against conflating statistical pattern recognition with genuine comprehension.',
                'quote' => 'cognitive scientists caution against conflating statistical pattern recognition',
            ],
            [
                'num' => 40,
                'prompt' => 'What is the author\'s main thesis regarding the nature of intelligence?',
                'options' => [
                    ['key' => 'A', 'text' => 'Statistical correlation of words is sufficient proof of human-level cognition.'],
                    ['key' => 'B', 'text' => 'Biological brains are obsolete and should be replaced by computer chips.'],
                    ['key' => 'C', 'text' => 'True understanding involves semantic consciousness and embodied interaction beyond mere syntax.'],
                    ['key' => 'D', 'text' => 'Speech ability is the single definitive criterion for intelligence.'],
                ],
                'correct' => 'C',
                'explanation' => 'Toàn bài đọc làm rõ sự khác biệt giữa xử lý cú pháp thống kê và sự hiểu biết thực thụ đòi hỏi ý thức ngữ nghĩa và tương tác thể hiện trong thế giới thực.',
                'quote' => 'Without sensorimotor grounding, machines will remain powerful linguistic simulators rather than sentient, reasoning minds.',
            ],
        ];

        foreach ($qData5 as $item) {
            IeltsQuestion::updateOrCreate(
                [
                    'ielts_question_group_id' => $p3->id,
                    'question_number' => $item['num'],
                ],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'explanation' => $item['explanation'],
                    'quote_reference' => $item['quote'],
                    'options' => $item['options'],
                ]
            );
        }

        // ==========================================
        // 3. TẠO SECTION LISTENING (40 CÂU - 30 PHÚT)
        // ==========================================
        $listeningSection = IeltsSection::updateOrCreate(
            ['title' => 'Academic & General Listening - Test 1'],
            [
                'skill' => IeltsSkillEnum::LISTENING,
                'test_type' => IeltsTestTypeEnum::ACADEMIC,
                'time_limit_minutes' => 30,
                'total_questions' => 40,
                'description' => 'IELTS Listening gồm 4 Parts (40 câu hỏi) kéo dài 30 phút phát audio tự động + 2 phút kiểm tra đáp án.',
                'is_active' => true,
            ]
        );

        $test->sections()->syncWithoutDetaching([
            $listeningSection->id => ['order' => 1],
            $readingSection->id => ['order' => 2],
        ]);

        // LISTENING PART 1: Transport Survey / Bicycle Rental
        $l1 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $listeningSection->id, 'order' => 1],
            [
                'title' => 'Part 1: Bicycle Rental & Commute Survey',
                'question_type' => IeltsQuestionTypeEnum::FILL_IN_BLANKS,
                'instruction' => 'Questions 1–10: Complete the notes below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
                'audio_url' => 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg',
                'transcript' => <<<TEXT
Agent: Good morning, Metro Bike Hire. How can I help you today?
Customer: Hi, I'd like to inquire about long-term bicycle hire for commuting to work. My name is Michael Sanders. (Q1)
Agent: Great to meet you, Michael. Let me take down your details. Could I have your phone number?
Customer: Yes, it's 07700 900342. (Q2)
Agent: And your current residential address?
Customer: I live at 24 Highbury Road in Bristol. (Q3)
Agent: Perfect. And how long are you looking to rent the bike for?
Customer: I will be working on a short project, so around 3 months would be ideal. (Q4)
Agent: We have several models available: road bikes, mountain bikes, and hybrid models.
Customer: I think a hybrid bike will suit city streets and occasional park paths best. (Q5)
Agent: Excellent choice. Do you need any optional accessories?
Customer: I already own lights, but I will need a helmet and a combination lock for security. (Q6)
Agent: Noted. And what is the primary purpose of your daily hire?
Customer: Just my daily commute between home and Temple Meads station. (Q7)
Agent: Understood. You can collect your bicycle this Saturday morning if that suits you? (Q8)
Customer: Saturday morning works brilliantly. How would you like me to pay?
Customer: Can I do a direct bank transfer? (Q9)
Agent: Yes, bank transfer is accepted. The refundable security deposit is £50. (Q10)
TEXT,
            ]
        );

        $lData1 = [
            ['num' => 1, 'prompt' => 'Customer surname: Michael [blank]', 'correct' => 'Sanders', 'quote' => 'My name is Michael Sanders.'],
            ['num' => 2, 'prompt' => 'Contact telephone: [blank]', 'correct' => '07700 900342', 'quote' => 'it\'s 07700 900342.'],
            ['num' => 3, 'prompt' => 'Address: 24 [blank] Road, Bristol', 'correct' => 'Highbury', 'quote' => 'I live at 24 Highbury Road in Bristol.'],
            ['num' => 4, 'prompt' => 'Rental duration needed: [blank] months', 'correct' => '3 / three', 'quote' => 'so around 3 months would be ideal.'],
            ['num' => 5, 'prompt' => 'Bicycle model chosen: [blank] bicycle', 'correct' => 'hybrid', 'quote' => 'a hybrid bike will suit city streets'],
            ['num' => 6, 'prompt' => 'Additional accessory requested: a [blank] lock', 'correct' => 'combination', 'quote' => 'a combination lock for security.'],
            ['num' => 7, 'prompt' => 'Main purpose of hire: daily [blank] to work', 'correct' => 'commute', 'quote' => 'Just my daily commute between home'],
            ['num' => 8, 'prompt' => 'Collection preferred on: [blank] morning', 'correct' => 'Saturday', 'quote' => 'collect your bicycle this Saturday morning'],
            ['num' => 9, 'prompt' => 'Payment will be made via: [blank] transfer', 'correct' => 'bank', 'quote' => 'do a direct bank transfer?'],
            ['num' => 10, 'prompt' => 'Refundable security deposit amount: £[blank]', 'correct' => '50 / fifty', 'quote' => 'The refundable security deposit is £50.'],
        ];

        foreach ($lData1 as $item) {
            IeltsQuestion::updateOrCreate(
                ['ielts_question_group_id' => $l1->id, 'question_number' => $item['num']],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'quote_reference' => $item['quote'],
                    'explanation' => 'Nghe rõ từ đoạn audio transcript: "' . $item['quote'] . '"',
                    'word_limit' => 2,
                ]
            );
        }

        // LISTENING PART 2 (Questions 11-20): Community Centre & Map
        $l2 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $listeningSection->id, 'order' => 2],
            [
                'title' => 'Part 2: Community Centre Facilities & Map',
                'question_type' => IeltsQuestionTypeEnum::MULTIPLE_CHOICE,
                'instruction' => 'Questions 11–15: Choose the correct letter, A, B or C. Questions 16–20: Label the map below. Write the correct letter, A–E.',
                'audio_url' => 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg',
                'transcript' => <<<TEXT
Director: Welcome, everyone, to the newly renovated Westbridge Community Centre! I'm delighted to show you around today. (Q11) Our principal goal behind this extensive refurbishment was to attract younger members from the surrounding neighborhood, who previously felt the building didn't cater to their interests. (Q12) You'll notice our brand-new fitness suite on the ground floor. While gym members have priority during weekdays, I'm pleased to announce that non-members can book sessions all day on weekends. (Q13) We are also committed to our senior community: our digital literacy and tablet workshops are funded through a municipal grant and provided completely free of charge. (Q14) Now, about arrivals: if you drive here, please do not park along the front entrance. Vehicle parking has been relocated to our spacious underground basement garage behind the sports hall. (Q15) Lastly, a reminder for all our wonderful volunteers attending the orientation tomorrow evening: please ensure you bring two passport-sized photographs so we can issue your official ID badges.

(Q16) Now, let me orient you with our floor plan. Entering through the main entrance at the bottom, the Main Reception Desk is immediately on your left at Location A. (Q17) Directly opposite reception, along the eastern corridor at Location B, is the Community Café where hot lunches and beverages are served daily. (Q18) If you have young children, turn right past the café into Location C, which houses our state-of-the-art Children's Playroom. (Q19) For those seeking a peaceful place to study or work, head down the central hallway past the courtyard; Location D is our Quiet Reading Room. (Q20) Finally, stepping through the double glass doors at the far end of the corridor brings you out to Location E, our newly landscaped Outdoor Garden Terrace.
TEXT,
            ]
        );

        $lData2 = [
            [
                'num' => 11,
                'prompt' => 'What was the main reason for refurbishing the community centre?',
                'options' => [
                    ['key' => 'A', 'text' => 'To attract younger members from the surrounding neighborhood.'],
                    ['key' => 'B', 'text' => 'To repair damage caused by recent severe winter floods.'],
                    ['key' => 'C', 'text' => 'To fulfill municipal energy efficiency guidelines.'],
                ],
                'correct' => 'A',
                'quote' => 'Our principal goal behind this extensive refurbishment was to attract younger members from the surrounding neighborhood',
                'explanation' => 'Diễn giả nêu rõ mục tiêu chính là thu hút các bạn trẻ trong khu vực lân cận.',
            ],
            [
                'num' => 12,
                'prompt' => 'When is the new fitness suite open to non-members?',
                'options' => [
                    ['key' => 'A', 'text' => 'Weekday mornings only'],
                    ['key' => 'B', 'text' => 'Every afternoon from 1 PM to 5 PM'],
                    ['key' => 'C', 'text' => 'All day on weekends'],
                ],
                'correct' => 'C',
                'quote' => 'non-members can book sessions all day on weekends.',
                'explanation' => 'Người không có thẻ thành viên có thể đặt lịch tập cả ngày vào các ngày cuối tuần.',
            ],
            [
                'num' => 13,
                'prompt' => 'Which service is provided free of charge to seniors?',
                'options' => [
                    ['key' => 'A', 'text' => 'Digital literacy and tablet workshops'],
                    ['key' => 'B', 'text' => 'Swimming coaching sessions'],
                    ['key' => 'C', 'text' => 'Pottery and painting classes'],
                ],
                'correct' => 'A',
                'quote' => 'our digital literacy and tablet workshops are funded through a municipal grant and provided completely free of charge.',
                'explanation' => 'Lớp kỹ năng số và sử dụng máy tính bảng được tài trợ miễn phí hoàn toàn.',
            ],
            [
                'num' => 14,
                'prompt' => 'Where should visitors park their vehicles?',
                'options' => [
                    ['key' => 'A', 'text' => 'Directly in front of the main entrance.'],
                    ['key' => 'B', 'text' => 'In the underground basement garage behind the sports hall.'],
                    ['key' => 'C', 'text' => 'On the opposite public street with a parking voucher.'],
                ],
                'correct' => 'B',
                'quote' => 'Vehicle parking has been relocated to our spacious underground basement garage behind the sports hall.',
                'explanation' => 'Bãi đậu xe đã được dời xuống tầng hầm rộng rãi phía sau nhà thi đấu thể thao.',
            ],
            [
                'num' => 15,
                'prompt' => 'What must all volunteers bring to the orientation meeting?',
                'options' => [
                    ['key' => 'A', 'text' => 'A signed criminal record check form'],
                    ['key' => 'B', 'text' => 'Two passport-sized photographs'],
                    ['key' => 'C', 'text' => 'Proof of their current home address'],
                ],
                'correct' => 'B',
                'quote' => 'please ensure you bring two passport-sized photographs so we can issue your official ID badges.',
                'explanation' => 'Tình nguyện viên cần mang 2 ảnh thẻ cỡ hộ chiếu để làm thẻ nhận diện.',
            ],
        ];

        foreach ($lData2 as $item) {
            IeltsQuestion::updateOrCreate(
                ['ielts_question_group_id' => $l2->id, 'question_number' => $item['num']],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'options' => $item['options'],
                    'correct_answer' => $item['correct'],
                    'quote_reference' => $item['quote'],
                    'explanation' => $item['explanation'],
                ]
            );
        }

        // Questions 16-20: Map / Plan Labeling (also in Part 2)
        $mapQuestions = [
            ['num' => 16, 'prompt' => 'Main Reception Desk', 'correct' => 'A', 'quote' => 'the Main Reception Desk is immediately on your left at Location A.', 'options' => [['key' => 'A', 'text' => 'Location A'], ['key' => 'B', 'text' => 'Location B'], ['key' => 'C', 'text' => 'Location C'], ['key' => 'D', 'text' => 'Location D'], ['key' => 'E', 'text' => 'Location E']]],
            ['num' => 17, 'prompt' => 'Community Café', 'correct' => 'B', 'quote' => 'Directly opposite reception, along the eastern corridor at Location B, is the Community Café', 'options' => [['key' => 'A', 'text' => 'Location A'], ['key' => 'B', 'text' => 'Location B'], ['key' => 'C', 'text' => 'Location C'], ['key' => 'D', 'text' => 'Location D'], ['key' => 'E', 'text' => 'Location E']]],
            ['num' => 18, 'prompt' => 'Children\'s Playroom', 'correct' => 'C', 'quote' => 'turn right past the café into Location C, which houses our state-of-the-art Children\'s Playroom.', 'options' => [['key' => 'A', 'text' => 'Location A'], ['key' => 'B', 'text' => 'Location B'], ['key' => 'C', 'text' => 'Location C'], ['key' => 'D', 'text' => 'Location D'], ['key' => 'E', 'text' => 'Location E']]],
            ['num' => 19, 'prompt' => 'Quiet Reading Room', 'correct' => 'D', 'quote' => 'head down the central hallway past the courtyard; Location D is our Quiet Reading Room.', 'options' => [['key' => 'A', 'text' => 'Location A'], ['key' => 'B', 'text' => 'Location B'], ['key' => 'C', 'text' => 'Location C'], ['key' => 'D', 'text' => 'Location D'], ['key' => 'E', 'text' => 'Location E']]],
            ['num' => 20, 'prompt' => 'Outdoor Garden Terrace', 'correct' => 'E', 'quote' => 'stepping through the double glass doors at the far end of the corridor brings you out to Location E, our newly landscaped Outdoor Garden Terrace.', 'options' => [['key' => 'A', 'text' => 'Location A'], ['key' => 'B', 'text' => 'Location B'], ['key' => 'C', 'text' => 'Location C'], ['key' => 'D', 'text' => 'Location D'], ['key' => 'E', 'text' => 'Location E']]],
        ];

        foreach ($mapQuestions as $item) {
            IeltsQuestion::updateOrCreate(
                ['ielts_question_group_id' => $l2->id, 'question_number' => $item['num']],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'options' => $item['options'],
                    'correct_answer' => $item['correct'],
                    'quote_reference' => $item['quote'],
                    'explanation' => 'Vị trí được xác định rõ qua lời dẫn hướng của diễn giả: "' . $item['quote'] . '"',
                ]
            );
        }

        // LISTENING PART 3 (Questions 21-30): Academic Seminar Discussion
        $l3 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $listeningSection->id, 'order' => 3],
            [
                'title' => 'Part 3: Academic Presentation on Marine Biology',
                'question_type' => IeltsQuestionTypeEnum::MULTIPLE_CHOICE,
                'instruction' => 'Questions 21–30: Choose the correct letter, A, B or C.',
                'audio_url' => 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg',
                'transcript' => <<<TEXT
Professor: Good morning, Clara and David. Let's review the draft of your seminar presentation on coastal ecosystems. Clara, what was your main finding regarding blue carbon sinks?
Clara: Well, Professor Davis, according to recent oceanic data, (Q21) mangrove forests absorb significantly more carbon dioxide than previously estimated, far surpassing terrestrial rainforests.
David: Yes, and when we examined fisheries management policies, (Q22) commercial fishing quotas have consistently failed to protect nearshore coral reefs due to weak enforcement mechanisms.
Professor: That is a critical observation. How did you verify the satellite readings?
Clara: (Q23) Satellite imaging often provides inaccurate sea surface temperature data in shallow bays due to sediment reflection, so we cross-referenced with buoy sensors.
David: For the section on tidal estuaries, (Q24) local industrial runoff has accelerated algae blooms that deplete dissolved oxygen overnight.
Clara: Exactly. Furthermore, (Q25) biodiversity indices dropped by nearly forty percent in wetlands where natural water channels had been dredged.
David: When preparing our visual aids, (Q26) we decided to prioritize interactive GIS maps rather than static charts to keep the audience engaged.
Professor: A wise choice. What about the community restoration initiative in St. Jude’s Bay?
Clara: (Q27) The local volunteer seagrass replanting program yielded an eighty percent survival rate within the first six months.
David: However, (Q28) lack of sustained municipal funding remains the single greatest obstacle to long-term monitoring.
Professor: How do you plan to conclude your presentation?
Clara: (Q29) We will propose an integrated coastal zone framework uniting researchers, fisheries, and urban planners.
David: And (Q30) we are recommending mandatory marine ecological audits before any further port expansions are authorized.
Professor: Excellent structure. Make sure you practice your timing before Thursday.
TEXT,
            ]
        );

        $lData3 = [
            ['num' => 21, 'prompt' => 'What did Clara find regarding blue carbon sinks?', 'options' => [['key' => 'A', 'text' => 'Mangrove forests absorb more carbon than previously estimated.'], ['key' => 'B', 'text' => 'Temperate kelp forests store carbon longer than salt marshes.'], ['key' => 'C', 'text' => 'Deep ocean trenches are the primary absorption areas.']], 'correct' => 'A', 'quote' => 'mangrove forests absorb significantly more carbon dioxide than previously estimated', 'exp' => 'Clara nêu rõ rừng ngập mặn hấp thụ nhiều carbon hơn đáng kể so với ước tính.'],
            ['num' => 22, 'prompt' => 'Why have commercial fishing quotas failed to protect reefs?', 'options' => [['key' => 'A', 'text' => 'The target fish species migrate outside territorial waters.'], ['key' => 'B', 'text' => 'Enforcement mechanisms and inspections are insufficiently strict.'], ['key' => 'C', 'text' => 'Local fishermen lacked modern tracking technology.']], 'correct' => 'B', 'quote' => 'commercial fishing quotas have consistently failed to protect nearshore coral reefs due to weak enforcement mechanisms.', 'exp' => 'David giải thích thất bại là do các cơ chế thực thi và kiểm tra lỏng lẻo.'],
            ['num' => 23, 'prompt' => 'Why did the students cross-reference satellite sea surface data?', 'options' => [['key' => 'A', 'text' => 'Cloud cover obscured satellite views during storm seasons.'], ['key' => 'B', 'text' => 'Sediment reflection caused inaccurate readings in shallow bays.'], ['key' => 'C', 'text' => 'The satellite calibration was expired by two years.']], 'correct' => 'B', 'quote' => 'Satellite imaging often provides inaccurate sea surface temperature data in shallow bays due to sediment reflection', 'exp' => 'Độ phản xạ trầm tích ở các vịnh nông làm sai lệch dữ liệu nhiệt độ vệ tinh.'],
            ['num' => 24, 'prompt' => 'What primary effect did industrial runoff cause in estuaries?', 'options' => [['key' => 'A', 'text' => 'Accelerated algae blooms leading to oxygen depletion.'], ['key' => 'B', 'text' => 'Drastic acidification killing juvenile crustaceans.'], ['key' => 'C', 'text' => 'Heavy metal sedimentation in shellfish beds.']], 'correct' => 'A', 'quote' => 'local industrial runoff has accelerated algae blooms that deplete dissolved oxygen overnight.', 'exp' => 'Nước thải công nghiệp thúc đẩy tảo nở hoa gây cạn kiệt oxy hòa tan.'],
            ['num' => 25, 'prompt' => 'What resulted from dredging natural water channels?', 'options' => [['key' => 'A', 'text' => 'Wetland biodiversity dropped by almost forty percent.'], ['key' => 'B', 'text' => 'Increased tidal salinity poisoned freshwater reeds.'], ['key' => 'C', 'text' => 'Surrounding farmland suffered repeated flash floods.']], 'correct' => 'A', 'quote' => 'biodiversity indices dropped by nearly forty percent in wetlands where natural water channels had been dredged.', 'exp' => 'Chỉ số đa dạng sinh học giảm gần 40% tại các vùng đất ngập nước bị nạo vét.'],
            ['num' => 26, 'prompt' => 'Why did the students choose interactive GIS maps for their slides?', 'options' => [['key' => 'A', 'text' => 'To comply with the university’s multimedia rules.'], ['key' => 'B', 'text' => 'To maintain active interest and audience engagement.'], ['key' => 'C', 'text' => 'Because static charts contained confidential figures.']], 'correct' => 'B', 'quote' => 'we decided to prioritize interactive GIS maps rather than static charts to keep the audience engaged.', 'exp' => 'Sử dụng bản đồ GIS tương tác nhằm giữ sự chú ý và tương tác của khán giả.'],
            ['num' => 27, 'prompt' => 'What was the initial success rate of the seagrass replanting program?', 'options' => [['key' => 'A', 'text' => 'Around sixty-five percent after one year.'], ['key' => 'B', 'text' => 'Eighty percent survival within six months.'], ['key' => 'C', 'text' => 'Over ninety percent across all test plots.']], 'correct' => 'B', 'quote' => 'seagrass replanting program yielded an eighty percent survival rate within the first six months.', 'exp' => 'Tỷ lệ cỏ biển sống sót đạt 80% trong 6 tháng đầu.'],
            ['num' => 28, 'prompt' => 'What is the greatest obstacle to long-term coastal monitoring?', 'options' => [['key' => 'A', 'text' => 'A lack of sustained municipal funding.'], ['key' => 'B', 'text' => 'Resistance from local commercial harbor operators.'], ['key' => 'C', 'text' => 'A shortage of qualified marine field scientists.']], 'correct' => 'A', 'quote' => 'lack of sustained municipal funding remains the single greatest obstacle', 'exp' => 'Thiếu hụt nguồn vốn tài trợ ngân sách duy trì từ chính quyền là rào cản lớn nhất.'],
            ['num' => 29, 'prompt' => 'What framework will the students propose in their conclusion?', 'options' => [['key' => 'A', 'text' => 'An integrated zone framework uniting researchers, fisheries, and planners.'], ['key' => 'B', 'text' => 'A total ban on private leisure boating within coral reserves.'], ['key' => 'C', 'text' => 'An automated drone surveillance fleet for coastlines.']], 'correct' => 'A', 'quote' => 'We will propose an integrated coastal zone framework uniting researchers, fisheries, and urban planners.', 'exp' => 'Họ đề xuất khung quản lý vùng bờ tích hợp liên kết các nhà nghiên cứu, ngư nghiệp và nhà quy hoạch.'],
            ['num' => 30, 'prompt' => 'What mandatory policy do the students recommend before port expansions?', 'options' => [['key' => 'A', 'text' => 'Environmental compensation taxes levied on shipping lines.'], ['key' => 'B', 'text' => 'Mandatory marine ecological audits.'], ['key' => 'C', 'text' => 'Public referendums in all adjacent seaside communities.']], 'correct' => 'B', 'quote' => 'we are recommending mandatory marine ecological audits before any further port expansions', 'exp' => 'Khuyến nghị bắt buộc kiểm toán sinh thái biển trước mọi dự án mở rộng cảng biển.'],
        ];

        foreach ($lData3 as $item) {
            IeltsQuestion::updateOrCreate(
                ['ielts_question_group_id' => $l3->id, 'question_number' => $item['num']],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'options' => $item['options'],
                    'correct_answer' => $item['correct'],
                    'quote_reference' => $item['quote'],
                    'explanation' => $item['exp'],
                ]
            );
        }

        // LISTENING PART 4 (Questions 31-40): Lecture on Cephalopod Intelligence
        $l4 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $listeningSection->id, 'order' => 4],
            [
                'title' => 'Part 4: Lecture on Cephalopod Intelligence',
                'question_type' => IeltsQuestionTypeEnum::FILL_IN_BLANKS,
                'instruction' => 'Questions 31–40: Complete the notes below. Write ONE WORD ONLY for each answer.',
                'audio_url' => 'https://actions.google.com/sounds/v1/ambiences/coffee_shop.ogg',
                'transcript' => <<<TEXT
Lecturer: Good afternoon, students. Today we will explore the fascinating world of cephalopod cognition, with a particular focus on the common octopus. 

To understand cephalopod intelligence, we must examine their decentralized nervous system. (Q31) Fascinatingly, octopuses possess approximately two-thirds of their neurons located in their flexible arms, allowing each limb to taste, feel, and make basic decisions semi-independently from the central brain. 

Another extraordinary feature is their rapid camouflage mechanism. (Q32) Their specialized pigment cells and muscle fibers allow them to alter their skin texture in milliseconds, serving primarily as dynamic camouflage against predators. 

In laboratory cognition tests, their problem-solving prowess has surprised researchers. (Q33) Several experiments demonstrated that octopuses can unscrew childproof jars to access crabs placed inside without any prior demonstration or human training. 

Their internal physiology is equally unique. (Q34) Unlike vertebrates who operate with a single cardiovascular pump, their circulatory system relies on three separate hearts to circulate blood throughout their muscular bodies. (Q35) Furthermore, their blood contains copper-rich hemocyanin, which gives oxygenated cephalopod blood a distinct blue coloration instead of iron-based red.

Regarding orientation, (Q36) field tracking reveals they exhibit advanced navigational spatial memory by recognizing coastal landmarks such as rock formations and submerged caves. 

Curiously, despite their impressive cognitive capabilities, (Q37) their short lifespans—typically one to two years—prevent cultural knowledge transfer across each generation, meaning every octopus must learn survival skills entirely from scratch. 

Additionally, (Q38) their strictly solitary behavioral habits mean they rarely engage in social play, unlike cetaceans or primates. 

Yet neuroscientists have found intriguing parallels: (Q39) recent brainwave studies indicate active neurological patterns during octopus rest that are remarkably similar to human sleep. 

In conclusion, (Q40) the unique evolutionary lineage of cephalopods proves conclusively that high-level intelligence evolved on Earth more than once, following an evolutionary trajectory completely separate from mammals and birds.
TEXT,
            ]
        );

        $lData4 = [
            ['num' => 31, 'prompt' => 'Octopuses possess two-thirds of their neurons located in their [blank].', 'correct' => 'arms', 'quote' => 'octopuses possess approximately two-thirds of their neurons located in their flexible arms'],
            ['num' => 32, 'prompt' => 'Their ability to alter skin texture serves primarily as dynamic [blank].', 'correct' => 'camouflage', 'quote' => 'serving primarily as dynamic camouflage against predators.'],
            ['num' => 33, 'prompt' => 'Experiments show octopuses can open childproof [blank] without prior training.', 'correct' => 'jars', 'quote' => 'octopuses can unscrew childproof jars to access crabs'],
            ['num' => 34, 'prompt' => 'Unlike vertebrates, their circulatory system relies on three separate [blank].', 'correct' => 'hearts', 'quote' => 'their circulatory system relies on three separate hearts'],
            ['num' => 35, 'prompt' => 'Copper-rich hemocyanin gives their blood a distinct [blank] coloration.', 'correct' => 'blue', 'quote' => 'gives oxygenated cephalopod blood a distinct blue coloration'],
            ['num' => 36, 'prompt' => 'They exhibit navigational spatial memory through recognizing coastal [blank].', 'correct' => 'landmarks', 'quote' => 'recognizing coastal landmarks such as rock formations'],
            ['num' => 37, 'prompt' => 'Short lifespans prevent cultural knowledge transfer across each [blank].', 'correct' => 'generation', 'quote' => 'prevent cultural knowledge transfer across each generation'],
            ['num' => 38, 'prompt' => 'Solitary behavioral habits mean they rarely engage in social [blank].', 'correct' => 'play', 'quote' => 'rarely engage in social play'],
            ['num' => 39, 'prompt' => 'Recent studies indicate active neurological patterns similar to human [blank].', 'correct' => 'sleep', 'quote' => 'remarkably similar to human sleep'],
            ['num' => 40, 'prompt' => 'Their unique evolutionary lineage proves intelligence evolved more than [blank].', 'correct' => 'once', 'quote' => 'intelligence evolved on Earth more than once'],
        ];

        foreach ($lData4 as $item) {
            IeltsQuestion::updateOrCreate(
                ['ielts_question_group_id' => $l4->id, 'question_number' => $item['num']],
                [
                    'order' => $item['num'],
                    'prompt' => $item['prompt'],
                    'correct_answer' => $item['correct'],
                    'quote_reference' => $item['quote'],
                    'explanation' => 'Từ vựng xuất hiện chính xác trong bài giảng: "' . $item['quote'] . '"',
                    'word_limit' => 1,
                ]
            );
        }

        // ==========================================
        // 4. TẠO SECTION WRITING (2 TASKS - 60 PHÚT)
        // ==========================================
        $writingSection = IeltsSection::updateOrCreate(
            ['title' => 'Academic Writing - Test 1'],
            [
                'skill' => IeltsSkillEnum::WRITING,
                'test_type' => IeltsTestTypeEnum::ACADEMIC,
                'time_limit_minutes' => 60,
                'total_questions' => 2,
                'description' => 'IELTS Academic Writing gồm Task 1 (tối thiểu 150 từ - 20 phút) và Task 2 (tối thiểu 250 từ - 40 phút).',
                'is_active' => true,
            ]
        );

        $test->sections()->syncWithoutDetaching([
            $writingSection->id => ['order' => 3],
        ]);

        $w1 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $writingSection->id, 'order' => 1],
            [
                'title' => 'Writing Task 1: Academic Report (150 words)',
                'question_type' => IeltsQuestionTypeEnum::SHORT_ANSWER,
                'instruction' => 'You should spend about 20 minutes on this task. Write at least 150 words.',
            ]
        );

        IeltsQuestion::updateOrCreate(
            ['ielts_question_group_id' => $w1->id, 'question_number' => 1],
            [
                'order' => 1,
                'prompt' => 'The chart below shows the percentage of households with internet access in four European countries (United Kingdom, Germany, France, and Spain) between 2000 and 2020. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.',
                'word_limit' => 150,
                'explanation' => 'Đảm bảo bài viết có Overview nêu rõ xu hướng tăng chung, các đoạn Body so sánh số liệu cụ thể giữa 4 quốc gia và không đưa quan điểm cá nhân.',
            ]
        );

        $w2 = IeltsQuestionGroup::updateOrCreate(
            ['ielts_section_id' => $writingSection->id, 'order' => 2],
            [
                'title' => 'Writing Task 2: Discursive Essay (250 words)',
                'question_type' => IeltsQuestionTypeEnum::SHORT_ANSWER,
                'instruction' => 'You should spend about 40 minutes on this task. Write at least 250 words.',
            ]
        );

        IeltsQuestion::updateOrCreate(
            ['ielts_question_group_id' => $w2->id, 'question_number' => 2],
            [
                'order' => 2,
                'prompt' => 'Some people believe that artificial intelligence will create more opportunities than problems for future generations, while others fear it will lead to massive unemployment and loss of human agency. Discuss both views and give your own opinion. Give reasons for your answer and include any relevant examples from your own knowledge or experience.',
                'word_limit' => 250,
                'explanation' => 'Bài viết cần cấu trúc 4 đoạn: Introduction (Paraphrase + Thesis statement), Body 1 (Phân tích cơ hội/lợi ích), Body 2 (Phân tích rủi ro thất nghiệp), và Conclusion khẳng định quan điểm bản thân.',
            ]
        );
    }
}
