<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DoctorExport implements FromCollection, WithHeadings
{
    protected $search;

    public function __construct($search = null)
    {
        $this->search = $search;
    }

    public function collection()
    {
        return User::where('type', 'doctor')
            ->whereHas('userFile')
            ->with(['userFile', 'employee'])

            ->when($this->search, function ($q) {
                $search = $this->search;

                $q->where(function ($query) use ($search) {

                    // 🔍 Doctor fields
                    $query->where('name', 'like', "%$search%")
                        ->orWhere('mobile', 'like', "%$search%")
                        ->orWhere('speciality', 'like', "%$search%")
                        ->orWhere('hospital_name', 'like', "%$search%")
                        ->orWhere('address', 'like', "%$search%")

                        // 🔍 Employee search
                        ->orWhereHas('employee', function ($q2) use ($search) {
                            $q2->where('name', 'like', "%$search%")
                                ->orWhere('employee_code', 'like', "%$search%");
                        });
                });
            })

            ->get()

            ->map(function ($doctor) {

                $file = $doctor->userFile;
                $employee = $doctor->employee;

                return [
                    $doctor->name ?? '',
                    $doctor->msl_number ?? '',
                    $doctor->mobile ?? '',
                    $doctor->speciality ?? '',
                    $doctor->hospital_name ?? '',
                    $doctor->address ?? '',

                    // ✅ Employee safe
                    optional($employee)->name ?? '',
                    optional($employee)->employee_code ?? '',
                    optional($employee)->position_code ?? '',

                    // Media
                    $file && $file->photo ? asset('storage/'.$file->photo) : '',
                    $file && $file->banner_path ? asset('storage/'.$file->banner_path) : '',
                    $file && $file->video ? asset('storage/'.$file->video) : '',

                    $file->language ?? '',

                    optional($doctor->created_at)
                        ->timezone('Asia/Kolkata')
                        ->format('d-m-Y h:i A'),
                ];

            });
    }

    public function headings(): array
    {
        return [
            'Doctor Name',
            'MSL Number',
            'Mobile',
            'Speciality',
            'Hospital',
            'Address',
            'Employee Name',
            'Employee Code',
            'Position Code',
            'Photo URL',
            'Banner URL',
            'Video URL',
            'Language',
            'Created At',
        ];
    }
}
