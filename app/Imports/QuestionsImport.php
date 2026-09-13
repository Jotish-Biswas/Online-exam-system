<?php

namespace App\Imports;

use App\Models\Question;
use App\Models\Answer;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class QuestionsImport implements ToCollection, WithHeadingRow
{
    protected int $examId;
    public int $importedCount = 0;

    public function __construct(int $examId)
    {
        $this->examId = $examId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $questionText = trim($row['question'] ?? $row['question_text'] ?? '');
            if (empty($questionText)) {
                continue;
            }

            $rawType = strtolower(trim($row['type'] ?? $row['question_type'] ?? 'single'));
            $questionType = in_array($rawType, ['multiple', 'file_upload']) ? $rawType : 'single';
            $marks = isset($row['marks']) && is_numeric($row['marks']) ? (float) $row['marks'] : 1.00;
            $explanation = trim($row['explanation'] ?? '');

            $question = Question::create([
                'exam_id' => $this->examId,
                'question_text' => $questionText,
                'question_type' => $questionType,
                'marks' => $marks,
                'explanation' => !empty($explanation) ? $explanation : null,
            ]);

            // If MCQ, extract options
            if ($questionType !== 'file_upload') {
                $options = [];
                foreach (['a', 'b', 'c', 'd', 'e', 'f'] as $optKey) {
                    $val = trim($row['option_' . $optKey] ?? $row['option' . $optKey] ?? $row[$optKey] ?? '');
                    if (!empty($val)) {
                        $options[strtoupper($optKey)] = $val;
                    }
                }

                // If no named options, try generic options 1, 2, 3, 4
                if (empty($options)) {
                    foreach (range(1, 6) as $num) {
                        $val = trim($row['option_' . $num] ?? $row['option' . $num] ?? '');
                        if (!empty($val)) {
                            $options[chr(64 + $num)] = $val;
                        }
                    }
                }

                // Parse correct answer(s)
                $rawCorrect = strtoupper(trim((string) ($row['correct_answers'] ?? $row['correct_answer'] ?? $row['correct'] ?? '')));
                // Can be "A", "A,B", "1,2", "A;B"
                $correctList = preg_split('/[\s,;]+/', $rawCorrect, -1, PREG_SPLIT_NO_EMPTY);

                $optIndex = 0;
                foreach ($options as $letter => $optText) {
                    $optIndex++;
                    $isCorrect = in_array($letter, $correctList) || in_array((string)$optIndex, $correctList);

                    Answer::create([
                        'question_id' => $question->id,
                        'answer_text' => $optText,
                        'is_correct' => $isCorrect,
                    ]);
                }
            }

            $this->importedCount++;
        }
    }
}
