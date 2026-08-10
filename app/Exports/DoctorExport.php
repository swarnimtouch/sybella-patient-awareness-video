<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DoctorExport implements FromCollection, WithHeadings
{
    protected $search;

    // S3 presigned URLs ki AWS-imposed max validity 7 din hai — isse zyada
    // nahi ho sakta chahe jo bhi try karo.
    private const URL_EXPIRY_DAYS = 7;

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

                    // Media — S3 signed URLs (max 7 din valid, AWS limit)
                    $this->s3Url($file->photo ?? null),
                    $this->s3Url($file->banner_path ?? null),
                    $this->s3Url($file->video ?? null),

                    $file->language ?? '',

                    optional($doctor->created_at)
                        ->timezone('Asia/Kolkata')
                        ->format('d-m-Y h:i A'),
                ];

            });
    }

    private function s3Url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addDays(self::URL_EXPIRY_DAYS)
        );
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
