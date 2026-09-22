<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportController extends Controller
{
    //
    public function import()
    {
        return view('import');
    }

    public function importUsers(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $validated = $request->validate([
            'import_type' => ['required', Rule::in(['employee', 'doctor'])],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'progress_id' => ['nullable', 'uuid'],
        ]);

        $type = $validated['import_type'];
        $progressId = $validated['progress_id'] ?? null;
        $import = new UsersImport($type, $progressId);

        $import->publishProgress();

        try {
            Excel::import($import, $request->file('file'));
            $summary = $import->summary();
            $import->publishProgress('completed');
        } catch (Throwable $exception) {
            if ($progressId) {
                Cache::store('file')->put(
                    UsersImport::progressKey($progressId),
                    array_merge($import->summary(), [
                        'status' => 'failed',
                        'message' => 'Import failed. Please check the file and try again.',
                    ]),
                    now()->addHour()
                );
            }

            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Import failed. Please check the file and try again.',
                ], 500);
            }

            return back()->withErrors([
                'file' => 'Import failed. Please check the file and try again.',
            ]);
        }

        $message = sprintf(
            '%s import completed: %d inserted, %d updated, %d skipped.',
            ucfirst($type),
            $summary['inserted'],
            $summary['updated'],
            $summary['skipped']
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'summary' => $summary,
            ]);
        }

        return back()
            ->with('success', $message)
            ->with('import_summary', $summary);
    }

    public function progress(string $progressId): JsonResponse
    {
        $progress = Cache::store('file')->get(
            UsersImport::progressKey($progressId)
        );

        return response()->json($progress ?? [
            'status' => 'waiting',
            'processed' => 0,
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
        ]);
    }
}
