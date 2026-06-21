<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

class LessonService
{
    /**
     * إنشاء درس جديد بـ Order تلقائي أو محدد وإعادة الترتيب
     */
    public function createLesson(Section $section, array $data): Lesson
    {
        return DB::transaction(function () use ($section, $data) {
            $maxOrder = $section->lessons()->max('order') ?? 0;

            // إذا لم يرسل الترتيب نضعه في النهاية، وإذا أرسله كبيراً جداً نفرمله
            $targetOrder = $data['order'] ?? ($maxOrder + 1);
            if ($targetOrder > ($maxOrder + 1)) {
                $targetOrder = $maxOrder + 1;
            }

            // إخلاء مكان للترتيب الجديد في هذا القسم
            $this->reorderLessons($section->id, null, $targetOrder);

            return Lesson::create([
                'section_id' => $section->id,
                'title'      => $data['title'],
                'order'      => $targetOrder,
                'is_preview' => $data['is_preview'] ?? false,
            ]);
        });
    }

    /**
     * تعديل بيانات درس وإعادة ترتيب الأقران ذكياً
     */
    public function updateLesson(Lesson $lesson, array $data): Lesson
    {
        return DB::transaction(function () use ($lesson, $data) {
            if (isset($data['order'])) {
                $section = $lesson->section;
                $maxOrder = $section->lessons()->max('order') ?? 1;
                $targetOrder = $data['order'];

                if ($targetOrder > $maxOrder) {
                    $targetOrder = $maxOrder;
                }

                // إعادة ترتيب الدروس الأخرى لإخلاء المكان الجديد
                $this->reorderLessons($section->id, $lesson->id, $targetOrder);
                $lesson->order = $targetOrder;
            }

            if (isset($data['title'])) {
                $lesson->title = $data['title'];
            }

            if (isset($data['is_preview'])) {
                $lesson->is_preview = $data['is_preview'];
            }

            $lesson->save();
            return $lesson->refresh();
        });
    }

    /**
     * حذف درس وسد فجوات الترتيب تلقائياً
     */
    public function deleteLesson(Lesson $lesson): void
    {
        DB::transaction(function () use ($lesson) {
            $sectionId = $lesson->section_id;
            $lesson->delete();

            // سد الفراغ الناتجة عن الحذف (مثلاً تحويل 1، 3، 4 إلى 1، 2، 3)
            $this->reorderLessons($sectionId);
        });
    }

    /**
     * الخوارزمية الداخلية لإعادة الترتيب المتسلسل ومنع الفراغات والتكرار
     */
    private function reorderLessons(int $sectionId, ?int $ignoredLessonId = null, ?int $targetOrder = null): void
    {
        $lessons = Lesson::where('section_id', $sectionId)
            ->where('id', '!=', $ignoredLessonId)
            ->orderBy('order', 'asc')
            ->get();

        $newOrder = 1;

        foreach ($lessons as $lesson) {
            if ($newOrder === $targetOrder) {
                $newOrder++;
            }
            if ($lesson->order !== $newOrder) {
                $lesson->update(['order' => $newOrder]);
            }
            $newOrder++;
        }
    }
}
