<?php

namespace App\Services;

use App\Models\LessonContent;
use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LessonContentService
{
    /**
     * إنشاء محتوى جديد وإعادة الترتيب
     */
    public function createContent(Lesson $lesson, array $data, $file = null): LessonContent
    {
        return DB::transaction(function () use ($lesson, $data, $file) {
            $maxOrder = $lesson->contents()->max('order') ?? 0;
            $targetOrder = $data['order'] ?? ($maxOrder + 1);

            if ($targetOrder > ($maxOrder + 1)) {
                $targetOrder = $maxOrder + 1;
            }

            // إعادة ترتيب المحتويات الأخرى بداخل الدرس لإخلاء مكان
            $this->reorderContents($lesson->id, null, $targetOrder);

            $storageKey = null;
            // إذا كان المحتوى مرفق PDF، نقوم بحفظه محلياً مؤقتاً
            if ($data['type'] === 'pdf' && $file) {
                $storageKey = $file->store('attachments', 'local'); // سيتغير إلى b2 لاحقاً
            }

            return LessonContent::create([
                'lesson_id'   => $lesson->id,
                'type'        => $data['type'],
                'title'       => $data['title'] ?? null ,
                'text_value'  => $data['type'] === 'text_article' ? $data['text_value'] : null,
                'storage_key' => $storageKey,
                'status'      => 'ready', // المرفقات والنصوص جاهزة فوراً
                'order'       => $targetOrder,
            ]);
        });
    }

    /**
     * تعديل محتوى وإعادة ترتيب ذكية
     */
    public function updateContent(LessonContent $content, array $data, $file = null): LessonContent
    {
        return DB::transaction(function () use ($content, $data, $file) {
            $lesson = $content->lesson;

            if (isset($data['order'])) {
                $maxOrder = $lesson->contents()->max('order') ?? 1;
                $targetOrder = $data['order'];

                if ($targetOrder > $maxOrder) {
                    $targetOrder = $maxOrder;
                }

                $this->reorderContents($lesson->id, $content->id, $targetOrder);
                $content->order = $targetOrder;
            }

            if (isset($data['title'])) {
                $content->title = $data['title'];
            }

            if ($content->type === 'text_article' && isset($data['text_value'])) {
                $content->text_value = $data['text_value'];
            }

            // لو رفع ملف PDF جديد، يحذف القديم ويخزن الجديد
            if ($content->type === 'pdf' && $file) {
                if ($content->storage_key) {
                    Storage::disk('local')->delete($content->storage_key);
                }
                $content->storage_key = $file->store('attachments', 'local');
            }

            $content->save();
            return $content->refresh();
        });
    }

    /**
     * حذف محتوى وسد الفجوات
     */
    public function deleteContent(LessonContent $content): void
    {
        DB::transaction(function () use ($content) {
            $lessonId = $content->lesson_id;

            // حذف الملف الفيزيائي إذا كان PDF
            if ($content->type === 'pdf' && $content->storage_key) {
                Storage::disk('local')->delete($content->storage_key);
            }

            $content->delete();
            $this->reorderContents($lessonId);
        });
    }

    /**
     * خوارزمية الترتيب المتسلسل للمحتويات داخل الدرس الواحد
     */
    private function reorderContents(int $lessonId, ?int $ignoredContentId = null, ?int $targetOrder = null): void
    {
        $contents = LessonContent::where('lesson_id', $lessonId)
            ->where('id', '!=', $ignoredContentId)
            ->orderBy('order', 'asc')
            ->get();

        $newOrder = 1;
        foreach ($contents as $item) {
            if ($newOrder === $targetOrder) {
                $newOrder++;
            }
            if ($item->order !== $newOrder) {
                $item->update(['order' => $newOrder]);
            }
            $newOrder++;
        }
    }
}
