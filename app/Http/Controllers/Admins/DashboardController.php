<?php

namespace App\Http\Controllers\Admins;

use App\Helpers\ApiResource;
use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardStatistics;

class DashboardController extends Controller
{

    private DashboardStatistics $dashboardStatistics;

    public function __construct(DashboardStatistics $dashboardStatistics)
    {
        $this->dashboardStatistics = $dashboardStatistics;
    }
    public function getDashboardStatistics()
    {

        $data = [
            'totalCourses' => $this->dashboardStatistics->getTotalCourses(),
            'totalRevenue' => $this->dashboardStatistics->getTotalRevenue(),
            'totalUsers' => $this->dashboardStatistics->getTotalUsers(),
            'newUserThisMonth' => $this->dashboardStatistics->getNewUserInThisMonth(),
            'popularCategory' => $this->dashboardStatistics->popularCategory(),
            'monthlySubscriptionsCourses' => $this->dashboardStatistics->getMonthlySubscriptionsCourses(),
            'monthlyNewUsers' => $this->dashboardStatistics->getMonthlyNewUsers(),
            'categoryDistribution' => $this->dashboardStatistics->getCategoryDistribution(),
            'recentCourses' => $this->dashboardStatistics->getRecentCourses(),
            'moderationQueue' => $this->dashboardStatistics->getModerationQueueCourse(),
            'publishedCourses' => $this->dashboardStatistics->getPublishedCourses(),
        ];


        return ApiResource::sendResponse('Dashboard loaded success', $data, 200);
    }
}
