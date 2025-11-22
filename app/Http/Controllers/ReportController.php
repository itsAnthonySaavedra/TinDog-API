<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Report;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['reportedUser', 'reportedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reported_user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:255',
        ]);

        // Assuming the logged-in admin is reporting
        // In a real app, we might want to track which admin reported it
        // For now, we'll use the reported_by_user_id as the admin's ID if available, or a fallback
        // Since this is an admin action, we might need to adjust the validation or logic
        // But for now, let's assume the frontend sends the reporter ID (the admin)
        
        $report = Report::create([
            'reported_user_id' => $request->reported_user_id,
            'reported_by_user_id' => $request->user()->id, // The authenticated user (admin)
            'reason' => $request->reason,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User reported successfully',
            'data' => $report
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Report not found'], 404);
        }

        $request->validate([
            'status' => 'required|in:open,resolved,dismissed,banned,suspended'
        ]);

        $report->status = $request->status;
        $report->save();

        // If status is banned or suspended, update the user status
        if (in_array($request->status, ['banned', 'suspended'])) {
            $user = $report->reportedUser;
            if ($user) {
                $user->status = $request->status;
                $user->save();
            }
        } 
        // If status is resolved, set user back to active (if not banned by another report?)
        // For simplicity, we'll set them to active. In a complex system, we'd check other active reports.
        elseif ($request->status === 'resolved') {
            $user = $report->reportedUser;
            if ($user) {
                $user->status = 'active';
                $user->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => $report
        ]);
    }
}
