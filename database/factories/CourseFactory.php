<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        // 1. تحديد السيناريو الزمني عشوائياً لضمان وجود الحالات الثلاثة
        $timeScenario = fake()->randomElement(['past', 'present', 'future']);

        if ($timeScenario === 'past') {
            // كورس منتهي (Completed): بدأ وانتهى في الماضي
            $startDate = fake()->dateTimeBetween('-6 months', '-5 months');
            $status = 'completed'; // أو الاسم المعتمد عندك للكورسات المنتهية
        } elseif ($timeScenario === 'present') {
            // كورس نشط حالياً (Active): بدأ قبل فترة ومستمر
            $startDate = fake()->dateTimeBetween('-1 month', '-1 day');
            $status = 'active';
        } else {
            // كورس قادم (Upcoming/Pending): سيبدأ في المستقبل
            $startDate = fake()->dateTimeBetween('+1 week', '+2 months');
            // بما أن حالتك الافتراضية بالجدول هي pending، فالقادم يمكن اعتباره pending أو حشوه بـ upcoming لو كنت تدعمها
            $status = 'upcoming';
        }

        // 2. حساب الـ end_date (إضافة من شهرين لـ 4 أشهر عشوائياً بناءً على الـ start_date)
        $monthsToAdd = fake()->numberBetween(2, 4);
        $endDate = (clone $startDate)->modify("+{$monthsToAdd} months");

        return [
            // العلاقات (سيتم تجاوزها ديناميكياً في الـ Seeder لربطها ببيانات حقيقية)
            'teacher_id' => User::factory(),
            // ملاحظة: تأكد أن جدول الكورس يحتوي على category_id لأنها لم تظهر بالصورة، لو كانت موجودة اترك السطر التالي:
            // 'category_id' => Category::factory(),

            // حشو الحقول المطابقة لملف الـ Migration بالصورة تماماً:
            'title' => fake()->unique()->sentence(fake()->numberBetween(3, 5)),
            'description' => fake()->paragraph(3),

            // الحقول الجديدة من صورتك:
            'course_type' => fake()->randomElement(['quiz_based', 'attendance_only']),
            'certificate_attendance_threshold' => fake()->randomElement([60, 75, 85, 90]), // نسب مئوية عشوائية للحضور
            'price' => fake()->randomElement([0.00, 19.99, 49.99, 99.99,200.00,125.36,80.00]), // تنويع بين المجاني والمدفوع

            // الحالات والتواريخ الذكية التي رتبناها:
            'status' => $status,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'cover_image' => fake()->imageUrl(640,480,'education'), // أو يمكنك وضع رابط لصورة افتراضية إذا أردت
        ];
    }
}
