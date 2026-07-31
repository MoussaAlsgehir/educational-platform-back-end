<?php

namespace Database\Seeders;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quizz;
use App\Models\Section;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = Section::all();

        if ($sections->isEmpty()) {
            $this->command->warn('No sections found. Skipping QuizSeeder.');
            return;
        }

        $quizData = [
            [
                'title' => 'Core Concepts Quiz',
                'passing_score' => 60,
                'questions' => [
                    [
                        'question_text' => 'What does API stand for?',
                        'question_points' => 10,
                        'answers' => [
                            ['answer_text' => 'Application Programming Interface', 'is_correct' => true],
                            ['answer_text' => 'Automated Program Integration', 'is_correct' => false],
                            ['answer_text' => 'Advanced Programming Interface', 'is_correct' => false],
                            ['answer_text' => 'Application Process Integration', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question_text' => 'Which HTTP method is used to create a new resource?',
                        'question_points' => 10,
                        'answers' => [
                            ['answer_text' => 'GET', 'is_correct' => false],
                            ['answer_text' => 'POST', 'is_correct' => true],
                            ['answer_text' => 'PUT', 'is_correct' => false],
                            ['answer_text' => 'DELETE', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question_text' => 'What is the primary purpose of MVC architecture?',
                        'question_points' => 10,
                        'answers' => [
                            ['answer_text' => 'Separate concerns into Model, View, Controller', 'is_correct' => true],
                            ['answer_text' => 'Merge all code into one file', 'is_correct' => false],
                            ['answer_text' => 'Improve database performance', 'is_correct' => false],
                            ['answer_text' => 'Automate testing', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question_text' => 'Which of the following is a JavaScript framework?',
                        'question_points' => 10,
                        'answers' => [
                            ['answer_text' => 'Laravel', 'is_correct' => false],
                            ['answer_text' => 'Django', 'is_correct' => false],
                            ['answer_text' => 'React', 'is_correct' => true],
                            ['answer_text' => 'Flask', 'is_correct' => false],
                        ],
                    ],
                    [
                        'question_text' => 'What is a foreign key used for?',
                        'question_points' => 10,
                        'answers' => [
                            ['answer_text' => 'To uniquely identify a record', 'is_correct' => false],
                            ['answer_text' => 'To link two tables together', 'is_correct' => true],
                            ['answer_text' => 'To speed up queries', 'is_correct' => false],
                            ['answer_text' => 'To encrypt data', 'is_correct' => false],
                        ],
                    ],
                ],
            ],
        ];

        $orderNumber = 1;

        foreach ($sections as $section) {
            foreach ($quizData as $data) {
                $quiz = Quizz::create([
                    'section_id' => $section->id,
                    'title' => $section->title . ' - ' . $data['title'],
                    'passing_score' => $data['passing_score'],
                    'order_number' => $orderNumber++,
                ]);

                foreach ($data['questions'] as $qData) {
                    $question = Question::create([
                        'quizz_id' => $quiz->id,
                        'question_text' => $qData['question_text'],
                        'question_points' => $qData['question_points'],
                    ]);

                    foreach ($qData['answers'] as $aData) {
                        Answer::create([
                            'question_id' => $question->id,
                            'answer_text' => $aData['answer_text'],
                            'is_correct' => $aData['is_correct'],
                        ]);
                    }
                }
            }
        }

        $this->command->info('Quizzes, questions, and answers created successfully for all sections.');
    }
}
