<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(): Response
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(50);

        return Inertia::render('admin/ActivityLog/Index', [
            'logs' => $logs,
        ]);
    }
}
