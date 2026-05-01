<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class EmployeeExport implements FromCollection, WithHeadings
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        return User::where('type', 'employee')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('employee_code', 'like', '%' . $this->search . '%');
                });
            })
            ->get()
            ->map(function ($item) {
                return [
                    $item->name ?? '',
                    $item->employee_code ?? '',
                    $item->position_code ?? '',
                    $item->designation ?? '',
                    $item->hq_name ?? '',
                    $item->hq_code ?? '',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee Code',
            'Position Code',
            'Designation',
            'HQ Name',
            'HQ Code',
        ];
    }
}
