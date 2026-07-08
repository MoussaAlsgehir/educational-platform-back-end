<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable; // 1. تأكد من استدعائها هنا
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function otps()
    {
        return $this->hasMany(Otp::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
    public function reviews()
    {
        return $this->hasMany(CourseReview::class, 'student_id');
    }

    /**
     * الفحص إذا كان المستخدم يملك دوراً معيناً
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    /**
     * الفحص إذا كان المستخدم يملك أي دور من قائمة أدوار معينة
     * @param array $roles قائمة الأدوار للتحقق منها
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * دالة مختصرة للفحص إذا كان أدمن
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * جلب الاسم الكامل للمستخدم تلقائياً
     */
    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function studentAttempts()
    {
        return $this->hasMany(StudentAttempt::class, 'student_id');
    }
}
