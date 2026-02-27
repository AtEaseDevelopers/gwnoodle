<?php

namespace App\Http\Controllers;

use App\DataTables\ActivityLogDataTable;
use App\Models\ActivityLog;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends AppBaseController
{
    /**
     * Display a listing of the Activity Logs.
     *
     * @param ActivityLogDataTable $activityLogDataTable
     * @return Response
     */
    public function index(ActivityLogDataTable $activityLogDataTable)
    {
        // Get filter options for the view
        $modules = ActivityLog::distinct()->pluck('module');
        $actions = ActivityLog::distinct()->pluck('action');
        $users = ActivityLog::distinct()
            ->pluck('user_id');
        
        return $activityLogDataTable->render('activity_logs.index', compact('modules', 'actions', 'users'));
    }

    /**
     * Display the specified Activity Log.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $log = ActivityLog::with('user')->find($id);

        if (empty($log)) {
            Flash::error('Activity log not found');
            return redirect(route('activity-logs.index'));
        }

        return view('activity_logs.show')->with('log', $log);
    }

    /**
     * Get log details for AJAX request
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDetails($id)
    {
        $log = ActivityLog::find($id);

        if (empty($log)) {
            return response()->json([
                'success' => false,
                'message' => 'Log not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'old_data' => $log->formatted_old_data,
                'new_data' => $log->formatted_new_data,
                'user' => $log->user,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Clear old logs
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearOld(Request $request)
    {
        try {
            $days = $request->get('days', 30);
            $date = now()->subDays($days);
            
            $count = ActivityLog::where('created_at', '<', $date)->delete();

            // Log this action
            activity('activity_log')
                ->withProperties(['deleted_count' => $count, 'days' => $days])
                ->log('Cleared old activity logs');

            return response()->json([
                'success' => true,
                'message' => "{$count} log(s) older than {$days} days have been cleared successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error clearing logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export logs to CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = ActivityLog::query();

        // Apply filters if any
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->with('user')->orderBy('created_at', 'desc')->get();

        $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $columns = ['Date Time', 'User', 'Action', 'Module'];

        $callback = function() use ($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name ?? 'System',
                    ucfirst($log->action),
                    ucfirst($log->module),
                    $log->description,
                    $log->ip_address,
                    $log->record_id
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get statistics for dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStats()
    {
        $stats = [
            'total_logs' => ActivityLog::count(),
            'logs_today' => ActivityLog::whereDate('created_at', today())->count(),
            'logs_this_week' => ActivityLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'logs_this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
            'top_users' => ActivityLog::select('user_name', DB::raw('count(*) as total'))
                ->whereNotNull('user_name')
                ->groupBy('user_name')
                ->orderBy('total', 'desc')
                ->limit(5)
                ->get(),
            'actions_breakdown' => ActivityLog::select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->get(),
            'modules_breakdown' => ActivityLog::select('module', DB::raw('count(*) as total'))
                ->groupBy('module')
                ->limit(10)
                ->get()
        ];

        return response()->json($stats);
    }
}