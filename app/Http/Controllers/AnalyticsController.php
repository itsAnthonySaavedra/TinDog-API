<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function overview()
    {
        $totalUsers = User::where('role', 'user')->count();
        $openReports = Report::where('status', 'open')->count();
        
        // Revenue Calculation
        // Labrador: 49, Mastiff: 99
        $labradorCount = User::where('plan', 'labrador')->count();
        $mastiffCount = User::where('plan', 'mastiff')->count();
        $monthlyRevenue = ($labradorCount * 49) + ($mastiffCount * 99);

        // System Status Check
        try {
            DB::connection()->getPdo();
            $systemStatus = 'Online';
        } catch (\Exception $e) {
            $systemStatus = 'Database Error';
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => $totalUsers,
                'monthly_revenue' => $monthlyRevenue,
                'open_reports' => $openReports,
                'system_status' => $systemStatus
            ]
        ]);
    }

    public function recentActivity()
    {
        // 1. Get latest 5 users (Created) - Include Soft Deleted ones so we don't lose the "Joined" log
        $newUsers = User::withTrashed()
            ->where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user',
                    'message' => "New User: {$user->first_name} {$user->last_name} registered.",
                    'time' => $user->created_at->diffForHumans(),
                    'timestamp' => $user->created_at
                ];
            });

        // 2. Get latest 5 deleted users
        $deletedUsers = User::onlyTrashed()
            ->where('role', 'user')
            ->orderBy('deleted_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'delete',
                    'message' => "User Deleted: {$user->first_name} {$user->last_name} was removed.",
                    'time' => $user->deleted_at->diffForHumans(),
                    'timestamp' => $user->deleted_at
                ];
            });

        // 3. Get latest 5 reports
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
                    'timestamp' => $report->created_at
                ];
            });

        // Merge all and sort by timestamp descending
        $activities = $newUsers->concat($deletedUsers)->concat($newReports)
            ->sortByDesc('timestamp')
            ->take(10) // Take top 10 mixed events
            ->values();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function userGrowth()
    {
        // Get users created in the last 30 days, grouped by date
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(29);

        $users = User::where('role', 'user')
            ->whereBetween('created_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');

        // Fill in missing dates with 0
        $data = [];
        $labels = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i)->format('Y-m-d');
            $labels[] = $startDate->copy()->addDays($i)->format('M d');
            $data[] = $users[$date] ?? 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'values' => $data
            ]
        ]);
    }

    public function demographics()
    {
        $dogSizes = User::where('role', 'user')
            ->whereNotNull('dog_size')
            ->select('dog_size', DB::raw('count(*) as count'))
            ->groupBy('dog_size')
            ->pluck('count', 'dog_size');

        // Normalize keys to lowercase just in case
        $normalizedSizes = [
            'small' => $dogSizes['small'] ?? $dogSizes['Small'] ?? 0,
            'medium' => $dogSizes['medium'] ?? $dogSizes['Medium'] ?? 0,
            'large' => $dogSizes['large'] ?? $dogSizes['Large'] ?? 0,
        ];

        // For location, we might just return the raw list for now or top 5
        // Since the frontend uses a static image for the map, we won't send location data yet
        // unless we want to list top cities.

        return response()->json([
            'success' => true,
            'data' => [
                'dog_sizes' => $normalizedSizes
            ]
        ]);
    }

    public function engagement()
    {
        // DAU: Users active in the last 24 hours
        // We need a 'last_seen' column. If it doesn't exist or isn't populated, this will be 0.
        // Assuming 'last_seen' is a timestamp.
        
        // For the chart (last 30 days DAU), we can't really reconstruct this history 
        // without a separate 'activity_logs' table.
        // So for the chart, we might have to stick to a placeholder or 
        // just show the *current* DAU as a single number if we can't chart it.
        // However, the user asked to avoid mock data.
        // Strategy: We will return the CURRENT DAU count.
        // For the chart, we will return an empty array or a single point if we can't derive history.
        // OR, we can just return the daily signups as a proxy for "New Active Users" if that's useful?
        // Let's stick to returning current stats.
        
        $currentDAU = User::where('role', 'user')
            ->where('last_seen', '>=', Carbon::now()->subDay())
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'current_dau' => $currentDAU,
                // We can't give a 30-day trend of DAU without historical data.
                // We'll send null for the chart data to indicate "No Data" or handle it on frontend.
                'dau_history' => [] 
            ]
        ]);
    }

    public function revenue()
    {
        $labradorCount = User::where('plan', 'labrador')->count();
        $mastiffCount = User::where('plan', 'mastiff')->count();

        // MRR Growth (Derived)
        // We'll calculate what the MRR *was* at the end of each of the last 6 months.
        $mrrHistory = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i)->endOfMonth();
            
            // Count users who existed by this date
            $lCount = User::where('plan', 'labrador')->where('created_at', '<=', $date)->count();
            $mCount = User::where('plan', 'mastiff')->where('created_at', '<=', $date)->count();
            
            $revenue = ($lCount * 49) + ($mCount * 99);
            
            $labels[] = $date->format('M Y');
            $mrrHistory[] = $revenue;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'breakdown' => [
                    'labrador' => $labradorCount * 49,
                    'mastiff' => $mastiffCount * 99
                ],
                'history' => [
                    'labels' => $labels,
                    'values' => $mrrHistory
                ]
            ]
        ]);
    }
}
