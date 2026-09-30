<?php

namespace Database\Seeders;

use App\Enums\IeltsQuestionTypeEnum;
use App\Enums\IeltsSkillEnum;
use App\Enums\IeltsTestTypeEnum;
use App\Models\IeltsQuestion;
use App\Models\IeltsQuestionGroup;
use App\Models\IeltsSection;
use App\Models\IeltsTest;
use Illuminate\Database\Seeder;

class IeltsReadingImportSeeder extends Seeder
{
    public function run(): void
    {
        $test = IeltsTest::updateOrCreate(
            ['slug' => 'reading-multi-select-flood-gifted-museums-qa'],
            [
                'title' => 'Reading Multi-Select QA: Floods, Gifted Children & Art Museums',
                'type' => IeltsTestTypeEnum::ACADEMIC,
                'description' => 'Bộ test Reading 35 câu nhập từ ba passage người dùng cung cấp, có nhóm kéo thả và trắc nghiệm chọn nhiều đáp án.',
                'duration_minutes' => 55,
                'is_published' => true,
                'total_questions' => 35,
            ]
        );

        $section = IeltsSection::updateOrCreate(
            ['title' => 'Reading Multi-Select QA - Floods, Gifted Children & Art Museums'],
            [
                'skill' => IeltsSkillEnum::READING,
                'test_type' => IeltsTestTypeEnum::ACADEMIC,
                'time_limit_minutes' => 55,
                'total_questions' => 35,
                'description' => 'Ba passage, 35 câu có đáp án. Dùng để thử kéo thả và trắc nghiệm chọn nhiều đáp án.',
                'is_active' => true,
            ]
        );

        $test->sections()->syncWithoutDetaching([$section->id => ['order' => 1]]);

        $floodQuestions = [
            ['A new approach carried out in the UK.', 'D'],
            ['Reasons why the twisty path and dykes failed.', 'B'],
            ['An alternative Plan in LA which seems much unrealistic.', 'G'],
            ['The traditional way of tackling flood.', 'A'],
            ['The effort made in the Netherlands and Germany.', 'F'],
            ['One project on a river benefits three nations.', 'E'],
            ['What TWO benefits will the new approach in the UK and Austria bring according to the passage?', 'B'],
            ['What TWO benefits will the new approach in the UK and Austria bring according to the passage?', 'D'],
        ];

        $giftedQuestions = [
            ['A reference to the influence of the domestic background on the gifted child.', 'A'],
            ['What can be lost if learners are given too much guidance.', 'D'],
            ['A reference to the damaging effects of anxiety.', 'F'],
            ['Classroom techniques which favour socially-disadvantaged children.', 'D'],
            ['Less time can be spent on exercises with gifted pupils who produce accurate work.', 'B'],
            ['Self-reliance is a valuable tool that helps gifted students reach their goals.', 'D'],
            ['Gifted children know how to channel their feelings to assist their learning.', 'E'],
            ['The very gifted child benefits from appropriate support from close relatives.', 'A'],
            ['Really successful students have learnt a considerable amount about their subject.', 'C'],
            ['One study found a strong connection between children’s IQ and the availability of … and … at home.', 'books_activities'],
            ['Children of average ability seem to need more direction from teachers because they do not have … .', 'internal_regulation'],
            ['Meta-cognition involves children understanding their own learning strategies, as well as developing … .', 'emotional_awareness'],
            ['Teachers who rely on what is known as … often produce impressive grades in class tests.', 'spoon_feeding'],
        ];

        $museumQuestions = [
            ['Summary: novels have depended on … for a long time.', '22_B'],
            ['Summary: with novels, the … are the most important thing.', '23_H'],
            ['Summary: Leonardo’s workshop apprentices were … .', '24_L'],
            ['Summary: reproductions can copy colour and … .', '25_G'],
            ['Summary: museums should consider the interests of the … .', '26_D'],
            ['The National Gallery illustrates the negative effect a museum can have on visitors’ opinions of themselves.', '27_C'],
            ['Viewers may be unwilling to criticise a work because they feel their personal reaction is of no significance.', '28_D'],
            ['The “displacement effect” is caused by the variety of works and the way they are arranged.', '29_A'],
            ['Unlike other art forms, a painting does not have a specific beginning or end.', '30_D'],
            ['Art history should focus on discovering the meaning of art using a range of media.', '31_NOT_GIVEN'],
            ['The approach of art historians conflicts with that of art museums.', '32_NO'],
            ['People should be encouraged to give their opinions openly on works of art.', '33_YES'],
            ['Reproductions of fine art should only be sold to the public if they are of high quality.', '34_NOT_GIVEN'],
            ['Those with power are likely to encourage more people to enjoy art in the future.', '35_NO'],
        ];

        $floodBank = $this->letterBank(range('A', 'G'));
        $floodMultiChoiceOptions = [
            ['key' => 'A', 'text' => 'We can prepare before the flood comes'],
            ['key' => 'B', 'text' => 'It may stop the flood involving the whole area'],
            ['key' => 'C', 'text' => 'Decrease strong rainfalls around the Alps simply by engineering constructions'],
            ['key' => 'D', 'text' => 'Reserve water to protect downstream towns'],
            ['key' => 'E', 'text' => 'Store tons of water in the downstream area'],
        ];
        $giftedBank = array_merge(
            $this->letterBank(range('A', 'F')),
            [
                ['key' => 'books_activities', 'text' => 'books and activities'],
                ['key' => 'internal_regulation', 'text' => 'internal regulation'],
                ['key' => 'emotional_awareness', 'text' => 'emotional awareness'],
                ['key' => 'spoon_feeding', 'text' => 'spoon-feeding'],
            ]
        );
        $museumBank = $this->museumBank();

        $passages = [
            [
                'title' => 'Passage 1: Can We Hold Back the Flood?',
                'content' => <<<'HTML'
<h2>Can We Hold Back the Flood?</h2>
<h3>A</h3><p>Last winter’s floods on the rivers of central Europe were among the worst since the Middle Ages, and as winter storms return, the spectre of floods is returning too. Just weeks ago, the river Rhône in south-east France burst its banks, driving 15,000 people from their homes, and worse could be on the way. Traditionally, river engineers have gone for Plan A: get rid of the water fast, draining it off the land and down to the sea in tall-sided rivers re-engineered as high-performance drains. But however big they dig city drains, however wide and straight they make the rivers, and however high they build the banks, the floods keep coming back to haunt them, from the Mississippi to the Danube. And when the floods come, they seem to be worse than ever.</p>
<h3>B</h3><p>No wonder engineers are turning to Plan B: sap the water’s destructive strength by dispersing it into fields, forgotten lakes, flood plains and aquifers. Back in the days when rivers took a more tortuous path to the sea, floodwaters lost impetus and volume while meandering across flood plains and idling through wetlands and inland deltas. But today the water tends to have an unimpeded journey to the sea. And this means that when it rains in the uplands, the water comes down all at once. Worse, whenever we close off more flood plain, the river’s flow farther downstream becomes more violent and uncontrollable. Dykes are only as good as their weakest link – and the water will unerringly find it.</p>
<h3>C</h3><p>Today, the river has lost 7 per cent of its original length and runs up to a third faster. When it rains hard in the Alps, the peak flows from several tributaries coincide in the main river, where once they arrived separately. And with four-fifths of the Lower Rhine’s flood plain barricaded off, the waters rise ever higher. The result is more frequent flooding that does ever-greater damage to the homes, offices and roads that sit on the flood plain. Much the same has happened in the US on the mighty Mississippi, which drains the world’s second-largest river catchment into the Gulf of Mexico.</p>
<h3>D</h3><p>The European Union is trying to improve rain forecasts and more accurately model how intense rains swell rivers. That may help cities prepare, but it won’t stop the floods. To do that, say, hydrologists, you need a new approach to engineering, not just Agency – country £1 billion – puts it like this: “The focus is now on working with the forces of nature. Towering concrete walls are out, and new wetlands are in.” to help keep London’s upstream and reflooding 10 square kilometres outside Oxford. Nearer to London it has spent £100 million creating new wetlands and a relief channel across 16 kilometres.</p>
<h3>E</h3><p>The same is taking place on a much grander scale in Austria, in one of Europe’s largest river restorations to date. Engineers are regenerating flood plains along 60 kilometres of the river Drave as it exits the Alps. They are also widening the river bed and channelling it back into abandoned meanders, oxbow lakes and backwaters overhung with willows. The engineers calculate that the restored flood plain can now store up to 10 million cubic metres of floodwaters and slow storm surges coming out of the Alps by more than an hour, protecting towns as far downstream as Slovenia and Croatia.</p>
<h3>F</h3><p>“Rivers have to be allowed to take more space. They have to be turned from flood-chutes into flood-foilers,” says Nienhuis. And the Dutch, for whom preventing floods is a matter of survival, have gone furthest. A nation built largely on drained marshes and seabed had the fright of its life in 1993 when the Rhine almost overwhelmed it. The same happened again in 1995 when a quarter of a million people were evacuated from the Netherlands. But a new breed of “soft engineers” wants our cities to become porous, and Berlin is their governed by tough new rules to prevent its drains from becoming overloaded after heavy rains. Herald Kraft, an architect working in the city, says: “We now see rainwater as giant Potsdamer Platz, a huge new commercial redevelopment by DaimlerChrysler in the heart of the city.</p>
<h3>G</h3><p>Los Angeles has spent billions of dollars digging huge drains and concreting river beds to carry away the water from occasional intense storms. “In LA we receive half the water we need in rainfall, and we throw it away. Then we spend hundreds of millions to import water,” says Andy Lipkis, an LA environmentalist who kick-started the idea of the porous city by showing it could work on one house. Lipkis, along with citizens groups like Friends of the Los Angeles River and Unpaved LA, want to beat the urban flood hazard and fill the taps by holding onto the city’s floodwater. And it’s not just a pipe dream. The authorities this year launched a $100 million scheme to road-test the porous city in one flood-hit community in Sun Valley. The plan is to catch the rain that falls on thousands of driveways, parking lots and rooftops in the valley. Trees will soak up water from parking lots. Homes and public buildings will capture roof water to irrigate gardens and parks. And road drains will empty into old gravel pits and other leaky places that should recharge the city’s underground water reserves. Result: less flooding and more water for the city. Plan B says every city should be porous, every river should have room to flood naturally and every coastline should be left to build its own defences. It sounds expensive and utopian, until you realise how much we spend trying to drain cities and protect our watery margins – and how bad we are at it.</p>
HTML,
                'questions' => $floodQuestions,
                'bank' => $floodBank,
                'usage' => 'repeat',
            ],
            [
                'title' => 'Passage 2: Gifted Children and Learning',
                'content' => <<<'HTML'
<h2>Gifted children and learning</h2>
<h3>A</h3><p>Internationally, ‘giftedness’ is most frequently determined by a score on a general intelligence test, known as an IQ test, which is above a chosen cutoff point, usually at around the top 2-5%. Children’s educational environment contributes to the IQ score and the way intelligence is used. For example, a very close positive relationship was found when children’s IQ scores were compared with their home educational provision (Freeman, 2010). The higher the children’s IQ scores, especially over IQ 130, the better the quality of their educational backup, measured in terms of reported verbal interactions with parents, number of books and activities in their home etc. Because IQ tests are decidedly influenced by what the child has learned, they are to some extent measures of current achievement based on age-norms; that is, how well the children have learned to manipulate their knowledge and know-how within the terms of the test. The vocabulary aspect, for example, is dependent on having heard those words. But IQ tests can neither identify the processes of learning and thinking nor predict creativity.</p>
<h3>B</h3><p>Excellence does not emerge without appropriate help. To reach an exceptionally high standard in any area very able children need the means to learn, which includes material to work with and focused challenging tuition -and the encouragement to follow their dream. There appears to be a qualitative difference in the way the intellectually highly able think, compared with more average-ability or older pupils, for whom external regulation by the teacher often compensates for lack of internal regulation. To be at their most effective in their self-regulation, all children can be helped to identify their own ways of learning – metacognition – which will include strategies of planning, monitoring, evaluation, and choice of what to learn. Emotional awareness is also part of metacognition, so children should be helped to be aware of their feelings around the area to be learned, feelings of curiosity or confidence, for example.</p>
<h3>C</h3><p>High achievers have been found to use self-regulatory learning strategies more often and more effectively than lower achievers, and are better able to transfer these strategies to deal with unfamiliar tasks. This happens to such a high degree in some children that they appear to be demonstrating talent in particular areas. Overviewing research on the thinking process of highly able children, (Shore and Kanevsky, 1993) put the instructor’s problem succinctly: ‘If they [the gifted] merely think more quickly, then .we need only teach more quickly. If they merely make fewer errors, then we can shorten the practice’. But of course, this is not entirely the case; adjustments have to be made in methods of learning and teaching, to take account of the many ways individuals think.</p>
<h3>D</h3><p>Yet in order to learn by themselves, the gifted do need some support from their teachers. Conversely, teachers who have a tendency to ‘overdirect’ can diminish their gifted pupils’ learning autonomy. Although ‘spoon-feeding’ can produce extremely high examination results, these are not always followed by equally impressive life successes. Too much dependence on the teachers risks loss of autonomy and motivation to discover. However, when teachers encourage pupils to reflect on their own learning and thinking activities, they increase their pupils’ self-regulation. For a young child, it may be just the simple question ‘What have you learned today?’ which helps them to recognise what they are doing. Given that a fundamental goal of education is to transfer the control of learning from teachers to pupils, improving pupils’ learning to learn techniques should be a major outcome of the school experience, especially for the highly competent. There are quite a number of new methods which can help, such as child- initiated learning, ability-peer tutoring, etc. Such practices have been found to be particularly useful for bright children from deprived areas.</p>
<h3>E</h3><p>But scientific progress is not all theoretical, knowledge is a so vital to outstanding performance: individuals who know a great deal about a specific domain will achieve at a higher level than those who do not (Elshout, 1995). Research with creative scientists by Simonton (1988) brought him to the conclusion that above a certain high level, characteristics such as independence seemed to contribute more to reaching the highest levels of expertise than intellectual skills, due to the great demands of effort and time needed for learning and practice. Creativity in all forms can be seen as expertise se mixed with a high level of motivation (Weisberg, 1993).</p>
<h3>F</h3><p>To sum up, learning is affected by emotions of both the individual and significant others. Positive emotions facilitate the creative aspects of earning and negative emotions inhibit it. Fear, for example, can limit the development of curiosity, which is a strong force in scientific advance, because it motivates problem-solving behaviour. In Boekaerts’ (1991) review of emotion the learning of very high IQ and highly achieving children, she found emotional forces in harness. They were not only curious, but often had a strong desire to control their environment, improve their learning efficiency and increase their own learning resources.</p>
HTML,
                'questions' => $giftedQuestions,
                'bank' => $giftedBank,
                'usage' => 'repeat',
            ],
            [
                'title' => 'Passage 3: Museums of Fine Art and Their Public',
                'content' => <<<'HTML'
<h2>Museums of fine art and their public</h2>
<p>The fact that people go to the Louvre museum in Paris to see the original painting Mona Lisa when they can see a reproduction anywhere leads us to question some assumptions about the role of museums of fine art in today’s world. One of the most famous works of art in the world is Leonardo da Vinci’s Mona Lisa. Nearly everyone who goes to see the original will already be familiar with it from reproductions, but they accept that fine art is more rewardingly viewed in its original form.</p>
<p>However, if Mona Lisa was a famous novel, few people would bother to go to a museum to read the writer’s actual manuscript rather than a printed reproduction. This might be explained by the fact that the novel has evolved precisely because of technological developments that made it possible to print out huge numbers of texts, whereas oil paintings have always been produced as unique objects. In addition, it could be argued that the practice of interpreting or ‘reading’ each medium follows different conventions. With novels, the reader attends mainly to the meaning of words rather than the way they are printed on the page, whereas the ‘reader’ of a painting must attend just as closely to the material form of marks and shapes in the picture as to any ideas they may signify.</p>
<p>Yet it has always been possible to make very accurate facsimiles of pretty well any fine art work. The seven surviving versions of Mona Lisa bear witness to the fact that in the 16th century, artists seemed perfectly content to assign the reproduction of their creations to their workshop apprentices as regular ‘bread and butter’ work. And today the task of reproducing pictures is incomparably more simple and reliable, with reprographic techniques that allow the production of high-quality prints made exactly to the original scale, with faithful colour values, and even with duplication of the surface relief of the painting. But despite an implicit recognition that the spread of good reproductions can be culturally valuable, museums continue to promote the special status of original work. Unfortunately, this seems to place severe limitations on the kind of experience offered to visitors.</p>
<p>One limitation is related to the way the museum presents its exhibits. As repositories of unique historical objects, art museums are often called ‘treasure houses’. We are reminded of this even before we view a collection by the presence of security guards, attendants, ropes and display cases to keep us away from the exhibits. In many cases, the architectural style of the building further reinforces that notion. In addition, a major collection like that of London’s National Gallery is housed in numerous rooms, each with dozens of works, any one of which is likely to be worth more than all the average visitor possesses. In a society that judges the personal status of the individual so much by their material worth, it is therefore difficult not to be impressed by one’s own relative ‘worthlessness’ in such an environment.</p>
<p>Furthermore, consideration of the ‘value’ of the original work in its treasure house setting impresses upon the viewer that, since these works were originally produced, they have been assigned a huge monetary value by some person or institution more powerful than themselves. Evidently, nothing the viewer thinks about the work is going to alter that value, and so today’s viewer is deterred from trying to extend that spontaneous, immediate, self-reliant kind of reading which would originally have met the work. The visitor may then be struck by the strangeness of seeing such diverse paintings, drawings and sculptures brought together in an environment for which they were not originally created. This ‘displacement effect’ is further heightened by the sheer volume of exhibits. In the case of a major collection, there are probably more works on display than we could realistically view in weeks or even months.</p>
<p>This is particularly distressing because time seems to be a vital factor in the appreciation of all art forms. A fundamental difference between paintings and other art forms is that there is no prescribed time over which a painting is viewed. By contrast, the audience experience an opera or a play over a specific time, which is the duration of the performance. Similarly novels and poems are read in a prescribed temporal sequence, whereas a picture has no clear place at which to start viewing, or at which to finish. Thus art works themselves encourage us to view them superficially, without appreciating the richness of detail and labour that is involved.</p>
<p>Consequently, the dominant critical approach becomes that of the art historian, a specialised academic approach devoted to ‘discovering the meaning’ of art within the cultural context of its time. This is in perfect harmony with the museum’s function, since the approach is dedicated to seeking out and conserving ‘authentic’, original, readings of the exhibits. Again, this seems to put paid to that spontaneous, participators criticism which can be found in abundance in criticism of classic works of literature, but is absent from most art history. The displays of art museums serve as a warning of what critical practices can emerge when spontaneous criticism is suppressed. The museum public, like any other audience, experience art more rewardingly when given the confidence to express their views. If appropriate works of fine art could be rendered permanently accessible to the public by means of high-fidelity reproductions, as literature and music already are, the public may feel somewhat less in awe of them. Unfortunately, that may be too much to ask from those who seek to maintain and control the art establishment.</p>
HTML,
                'questions' => $museumQuestions,
                'bank' => $museumBank,
                'usage' => 'repeat',
            ],
        ];

        $questionNumber = 1;
        $groupOrder = 1;
        foreach ($passages as $index => $passage) {
            $questionSets = $index === 0
                ? [
                    [
                        'title' => $passage['title'],
                        'questions' => array_slice($passage['questions'], 0, 6),
                        'mode' => 'drag_drop',
                        'content' => true,
                    ],
                    [
                        'title' => 'Passage 1: Multiple Choice — Questions 7–8',
                        'questions' => array_slice($passage['questions'], 6),
                        'mode' => 'multi_select',
                        'content' => false,
                    ],
                ]
                : [[
                    'title' => $passage['title'],
                    'questions' => $passage['questions'],
                    'mode' => 'drag_drop',
                    'content' => true,
                ]];

            foreach ($questionSets as $questionSet) {
                $firstNumber = $questionNumber;
                $lastNumber = $firstNumber + count($questionSet['questions']) - 1;
                $questionsHtml = '<hr><h3>Questions ' . $firstNumber . '–' . $lastNumber . '</h3>';
                foreach ($questionSet['questions'] as $offset => [$prompt, $answer]) {
                    if ($questionSet['mode'] === 'drag_drop') {
                        $number = $firstNumber + $offset;
                        $questionsHtml .= '<p><strong>' . $number . '.</strong> ' . e($prompt) . ' <span class="drag-gap">[blank_' . $number . ']</span></p>';
                    }
                }

                $multiSelectOptions = $questionSet['mode'] === 'multi_select'
                    ? $floodMultiChoiceOptions
                    : null;
                $settings = $questionSet['mode'] === 'multi_select'
                    ? ['multi_select' => true, 'selection_limit' => count($questionSet['questions'])]
                    : ['drag_option_usage' => $passage['usage'], 'drag_options' => $passage['bank']];

                $group = IeltsQuestionGroup::updateOrCreate(
                    ['ielts_section_id' => $section->id, 'order' => $groupOrder],
                    [
                        'title' => $questionSet['title'],
                        'question_type' => $questionSet['mode'] === 'multi_select'
                            ? IeltsQuestionTypeEnum::MULTIPLE_CHOICE
                            : IeltsQuestionTypeEnum::DRAG_DROP,
                        'instruction' => $questionSet['mode'] === 'multi_select'
                            ? 'Questions ' . $firstNumber . '–' . $lastNumber . ': Choose TWO correct letters. Mỗi đáp án được tính là một câu riêng; hãy chọn theo thứ tự câu.'
                            : 'Questions ' . $firstNumber . '–' . $lastNumber . ': Drag an answer from the bank into each gap. You may also click an option and then click a gap. Options may be reused in this imported practice set.',
                        'passage_content' => $questionSet['content'] ? $passage['content'] . $questionsHtml : null,
                        'settings' => $settings,
                    ]
                );

                $questionIds = [];
                foreach ($questionSet['questions'] as $offset => [$prompt, $answer]) {
                    $number = $firstNumber + $offset;
                    $question = IeltsQuestion::updateOrCreate(
                        ['ielts_question_group_id' => $group->id, 'question_number' => $number],
                        [
                            'order' => $offset + 1,
                            'prompt' => $prompt,
                            'correct_answer' => $answer,
                            'options' => $multiSelectOptions,
                            'explanation' => 'Đáp án lấy từ đáp án kèm theo nội dung được cung cấp.',
                        ]
                    );
                    $questionIds[] = $question->id;
                }

                IeltsQuestion::where('ielts_question_group_id', $group->id)
                    ->whereNotIn('id', $questionIds)
                    ->delete();

                $questionNumber = $lastNumber + 1;
                $groupOrder++;
            }
        }
    }

    /** @return array<int, array{key: string, text: string}> */
    private function letterBank(array $letters): array
    {
        return array_map(fn (string $letter) => ['key' => $letter, 'text' => $letter], $letters);
    }

    /** @return array<int, array{key: string, text: string}> */
    private function museumBank(): array
    {
        $choices = [
            'A' => 'institution', 'B' => 'mass production', 'C' => 'mechanical processes',
            'D' => 'public', 'E' => 'paints', 'F' => 'artist', 'G' => 'size',
            'H' => 'underlying ideas', 'I' => 'basic technology', 'J' => 'readers',
            'K' => 'picture frames', 'L' => 'assistants',
        ];
        $bank = [];
        for ($question = 22; $question <= 26; $question++) {
            foreach ($choices as $letter => $text) {
                $bank[] = ['key' => "{$question}_{$letter}", 'text' => "{$question}: {$letter} — {$text}"];
            }
        }

        $multipleChoice = [
            27 => ['A' => 'cost of maintaining a collection', 'B' => 'conflict between financial and artistic values', 'C' => 'negative effect on visitors’ self-opinion', 'D' => 'individual well-being over art schemes'],
            28 => ['A' => 'lack of knowledge', 'B' => 'fear of financial implications', 'C' => 'no concept of value', 'D' => 'personal reaction seems insignificant'],
            29 => ['A' => 'variety and arrangement of works', 'B' => 'works cannot be viewed for long enough', 'C' => 'similar paintings and lack of great works', 'D' => 'inappropriate individual works'],
            30 => ['A' => 'direct contact with an audience', 'B' => 'a specific performance location', 'C' => 'other professionals', 'D' => 'a specific beginning or end'],
        ];
        foreach ($multipleChoice as $question => $options) {
            foreach ($options as $letter => $text) {
                $bank[] = ['key' => "{$question}_{$letter}", 'text' => "{$question}: {$letter} — {$text}"];
            }
        }

        foreach ([31 => 'YES', 32 => 'NO', 33 => 'YES', 34 => 'NOT GIVEN', 35 => 'NO'] as $question => $answer) {
            foreach (['YES', 'NO', 'NOT GIVEN'] as $choice) {
                $bank[] = ['key' => "{$question}_{$choice}", 'text' => "{$question}: {$choice}"];
            }
        }

        return $bank;
    }
}
