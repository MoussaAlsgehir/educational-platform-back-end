<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class SectionService
{
    /**
     * إنشاء قسم جديد وإعادة الترتيب التلقائي
     */
    public function createSection(Course $course, array $data): Section
    {
        return DB::transaction(function () use ($course, $data) {
            $maxOrder = $course->sections()->max('order') ?? 0;

            // إذا ما انبعث order، بياخذ الماكس + 1، وإذا انبعث كبير كتير بنفرمله
            $targetOrder = $data['order'] ?? ($maxOrder + 1);
            if ($targetOrder > ($maxOrder + 1)) {
                $targetOrder = $maxOrder + 1;
            }

            // إخلاء مكان للترتيب الجديد
            $this->reorderSections($course->id, null, $targetOrder);

            return Section::create([
                'course_id' => $course->id,
                'title'     => $data['title'],
                'order'     => $targetOrder,
            ]);
        });
    }

    /**
     * تعديل قسم موجود وإعادة الترتيب الذكي
     */
    public function updateSection(Section $section, array $data): Section
    {
        return DB::transaction(function () use ($section, $data) {
            if (isset($data['order'])) {
                $course = $section->course;
                $maxOrder = $course->sections()->max('order') ?? 1;
                $targetOrder = $data['order'];

                if ($targetOrder > $maxOrder) {
                    $targetOrder = $maxOrder;
                }

                // إخلاء المكان الجديد مع استثناء السجل الحالي
                $this->reorderSections($course->id, $section->id, $targetOrder);
                $section->order = $targetOrder;
            }

            if (isset($data['title'])) {
                $section->title = $data['title'];
            }

            $section->save();
            return $section->refresh();
        });
    }

    /**
     * حذف قسم وسد الفجوات بالترتيب
     */
    public function deleteSection(Section $section): void
    {
        DB::transaction(function () use ($section) {
            $courseId = $section->course_id;
            $section->delete();

            // سد الفراغات الناتجة عن الحذف
            $this->reorderSections($courseId);
        });
    }

    /**
     * الخوارزمية الداخلية لإعادة الترتيب (Private مخصصة للسيرفس فقط)
     */
    private function reorderSections(int $courseId, ?int $ignoredSectionId = null, ?int $targetOrder = null): void
    {
        $sections = Section::where('course_id', $courseId)
            ->where('id', '!=', $ignoredSectionId)
            ->orderBy('order', 'asc')
            ->get();

        $newOrder = 1;

        foreach ($sections as $section) {
            if ($newOrder === $targetOrder) {
                $newOrder++;
            }
            if ($section->order !== $newOrder) {
                $section->update(['order' => $newOrder]);
            }
            $newOrder++;
        }
    }
}
