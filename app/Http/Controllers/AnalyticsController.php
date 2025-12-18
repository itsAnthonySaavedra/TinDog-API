<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Get file cache store to avoid hitting remote database for caching
     * This stores cache in local filesystem instead of remote Supabase
     */
    private function fileCache()
    {
        return Cache::store('file');
    }

    public function overview()
    {
        // Cache for 5 minutes (300 seconds) using LOCAL file cache
        $data = $this->fileCache()->remember('analytics_overview', 300, function () {
            $totalUsers = User::where('role', 'user')->count();
            $openReports = Report::where('status', 'open')->count();
            
            $labradorCount = User::where('plan', 'labrador')->count();
            $mastiffCount = User::where('plan', 'mastiff')->count();
            $monthlyRevenue = ($labradorCount * 49) + ($mastiffCount * 99);

            return [
                'total_users' => $totalUsers,
                'monthly_revenue' => $monthlyRevenue,
                'open_reports' => $openReports,
                'system_status' => 'Online'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function recentActivity()
    {
        // Cache for 2 minutes using LOCAL file cache
        $activities = $this->fileCache()->remember('analytics_recent_activity', 120, function () {
            $newUsers = User::where('role', 'user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user',
                        'message' => "New User: {$user->first_name} {$user->last_name} registered.",
                        'time' => $user->created_at->diffForHumans(),
                        'timestamp' => $user->created_at->toISOString()
                    ];
                });

            $newReports = Report::with('reportedUser')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($report) {
                    $reportedName = $report->reportedUser ? $report->reportedUser->display_name : 'Unknown User';
                    return [
                        'type' => 'report',
                        'message' => "New Report: Profile \"{$reportedName}\" was reported.",
                        'time' => $report->created_at->diffForHumans(),
                        'timestamp' => $report->created_at->toISOString()
                    ];
                });

            return $newUsers->concat($newReports)
                ->sortByDesc('timestamp')
                ->take(10)
                ->values()
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function userGrowth()
    {
        // Cache for 10 minutes using LOCAL file cache
        $data = $this->fileCache()->remember('analytics_user_growth', 600, function () {
            $endDate = Carbon::now();
            $startDate = Carbon::now()->subDays(29);

            $users = User::where('role', 'user')
                ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');

            $data = [];
            $labels = [];
            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i)->format('Y-m-d');
                $labels[] = $startDate->copy()->addDays($i)->format('M d');
                $data[] = $users[$date] ?? 0;
            }

            return [
                'labels' => $labels,
                'values' => $data
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function demographics()
    {
        // Cache for 10 minutes using LOCAL file cache
        $data = $this->fileCache()->remember('analytics_demographics', 600, function () {
            $dogSizes = User::where('role', 'user')
                ->whereNotNull('dog_size')
                ->select('dog_size', DB::raw('count(*) as count'))
                ->groupBy('dog_size')
                ->pluck('count', 'dog_size');

            return [
                'dog_sizes' => [
                    'small' => $dogSizes['small'] ?? $dogSizes['Small'] ?? 0,
                    'medium' => $dogSizes['medium'] ?? $dogSizes['Medium'] ?? 0,
                    'large' => $dogSizes['large'] ?? $dogSizes['Large'] ?? 0,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function engagement()
    {
        // Cache for 5 minutes using LOCAL file cache
        $data = $this->fileCache()->remember('analytics_engagement', 300, function () {
            $currentDAU = User::where('role', 'user')
                ->where('last_seen', '>=', Carbon::now()->subDay())
                ->count();

            return [
                'current_dau' => $currentDAU,
                'dau_history' => []
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function revenue()
    {
        // Cache for 10 minutes using LOCAL file cache (this was the slowest endpoint)
        $data = $this->fileCache()->remember('analytics_revenue', 600, function () {
            $labradorCount = User::where('plan', 'labrador')->count();
            $mastiffCount = User::where('plan', 'mastiff')->count();

            $labels = [];
            $mrrHistory = [];
            
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i)->endOfMonth();
                $labels[] = $date->format('M Y');
                
                $lCount = User::where('plan', 'labrador')->where('created_at', '<=', $date)->count();
                $mCount = User::where('plan', 'mastiff')->where('created_at', '<=', $date)->count();
                $mrrHistory[] = ($lCount * 49) + ($mCount * 99);
            }

            return [
                'breakdown' => [
                    'labrador' => $labradorCount * 49,
                    'mastiff' => $mastiffCount * 99
                ],
                'history' => [
                    'labels' => $labels,
                    'values' => $mrrHistory
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
