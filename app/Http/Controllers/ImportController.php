<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\UsersImport;
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

        $type = $request->input('import_type', 'employee'); // 'employee' ya 'doctor'

        Excel::import(new UsersImport($type), $request->file('file'));

        return back()->with('success', ucfirst($type) . 's imported successfully!');
    }
}
