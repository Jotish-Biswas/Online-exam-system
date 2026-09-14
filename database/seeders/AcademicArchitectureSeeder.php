<?php

namespace Database\Seeders;

use App\Models\AcademicChapter;
use App\Models\AcademicGroup;
use App\Models\AcademicSubject;
use Illuminate\Database\Seeder;

class AcademicArchitectureSeeder extends Seeder
{
    public function run(): void
    {
        $science = [
            ['Physics 1st Paper', ['Physical World and Measurement', 'Vectors', 'Dynamics', 'Newtonian Mechanics', 'Work, Energy and Power', 'Gravitation and Gravity', 'Structural Properties of Matter', 'Periodic Motion', 'Waves', 'Ideal Gas and Kinetic Theory of Gases']],
            ['Physics 2nd Paper', ['Thermodynamics', 'Static Electricity', 'Current Electricity', 'Magnetic Effect of Electric Current and Magnetism', 'Electromagnetic Induction and Alternating Current', 'Geometric Optics', 'Physical Optics', 'Introduction to Modern Physics', 'Atomic Model and Nuclear Physics', 'Semiconductor and Electronics', 'Astronomy']],
            ['Chemistry 1st Paper', ['Safe Use of Laboratory', 'Qualitative Chemistry', 'Periodic Properties of Elements and Chemical Bonding', 'Chemical Change', 'Economic Chemistry']],
            ['Chemistry 2nd Paper', ['Environmental Chemistry', 'Organic Chemistry', 'Quantitative Chemistry', 'Electrochemistry', 'Industrial Chemistry']],
            ['Higher Math 1st Paper', ['Matrix and Determinants', 'Vectors', 'Straight Lines', 'Circles', 'Permutations and Combinations', 'Trigonometric Ratios', 'Trigonometric Ratios of Associated Angles', 'Functions and Graph of Functions', 'Differentiation', 'Integration']],
            ['Higher Math 2nd Paper', ['Real Numbers and Inequalities', 'Linear Programming', 'Complex Numbers', 'Polynomials and Polynomial Equations', 'Binomial Expansion', 'Conics', 'Inverse Trigonometric Functions and Trigonometric Equations', 'Statics', 'Dynamics', 'Measures of Dispersion and Probability']],
            ['Botany 1st Paper', ['Cell and its Structure', 'Cell Division', 'Cell Chemistry', 'Microorganisms - Virus, Bacteria, Malaria', 'Algae and Fungi', 'Bryophyta and Pteridophyta', 'Gymnosperms and Angiosperms', 'Tissue and Tissue System', 'Plant Physiology', 'Plant Reproduction', 'Biotechnology', 'Environment, Distribution and Conservation of Organisms']],
            ['Zoology 2nd Paper', ['Animal Diversity and Classification', 'Introduction to Animal - Hydra, Grasshopper, Rohu Fish', 'Human Physiology: Digestion and Absorption', 'Human Physiology: Blood and Circulation', 'Human Physiology: Respiration and Breathing', 'Human Physiology: Excretion and Elimination', 'Human Physiology: Locomotion and Movement', 'Human Physiology: Coordination and Control', 'Human Physiology: Continuity of Human Life', 'Human Body Defense', 'Genetics and Evolution', 'Animal Behavior']],
        ];

        foreach ([
            ['Science', 'science', $science],
            ['Arts / Humanities', 'arts', []],
            ['Commerce / Business Studies', 'commerce', []],
        ] as [$groupName, $slug, $subjects]) {
            $group = AcademicGroup::updateOrCreate(['slug' => $slug], ['name' => $groupName]);
            foreach ($subjects as $subjectIndex => [$name, $chapters]) {
                $subject = AcademicSubject::updateOrCreate(
                    ['academic_group_id' => $group->id, 'name' => $name, 'paper' => null],
                    ['sort_order' => $subjectIndex]
                );
                foreach ($chapters as $chapterIndex => $title) {
                    AcademicChapter::updateOrCreate(
                        ['academic_subject_id' => $subject->id, 'title' => $title],
                        ['sort_order' => $chapterIndex]
                    );
                }
            }
        }
    }
}
