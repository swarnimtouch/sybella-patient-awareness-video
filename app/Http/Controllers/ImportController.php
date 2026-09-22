<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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
        ]);

        $type = $validated['import_type'];
        $import = new UsersImport($type);

        Excel::import($import, $request->file('file'));

        $summary = $import->summary();

        $message = sprintf(
            '%s import completed: %d inserted, %d updated, %d skipped.',
            ucfirst($type),
            $summary['inserted'],
            $summary['updated'],
            $summary['skipped']
        );

        return back()
            ->with('success', $message)
            ->with('import_summary', $summary);
    }
}
