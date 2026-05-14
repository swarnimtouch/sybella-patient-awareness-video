<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected array $parentCache = [];
    protected string $importType;

    public function __construct(string $importType = 'employee')
    {
        $this->importType = $importType;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows)
    {
        $toInsert = [];

        foreach ($rows as $row) {
            if ($this->importType === 'employee') {
                $toInsert[] = $this->prepareEmployee($row);
            } else {
                $toInsert[] = $this->prepareDoctor($row);
            }
        }

        $toInsert = array_filter($toInsert);

        if (!empty($toInsert)) {
            User::insert($toInsert);
        }
    }

    private function prepareEmployee(array|\ArrayAccess $row): ?array
    {
        if (empty($row['name'])) return null;

        $parentId = null;
        if (!empty($row['parent_employee_code'])) {
            $code = $row['parent_employee_code'];
            if (!array_key_exists($code, $this->parentCache)) {
                $this->parentCache[$code] = User::where('employee_code', $code)->value('id');
            }
            $parentId = $this->parentCache[$code];
        }

        return [
            'name'          => $row['name'],
            'password'      => Hash::make($row['employee_code'], ['rounds' => 4]),
            'employee_code' => $row['employee_code'] ?? null,
            'position_code' => $row['position_code'] ?? null,
            'designation'   => $row['designation'] ?? null,
            'hq_name'       => $row['hq_name'] ?? null,
            'hq_code'       => $row['hq_code'] ?? null,
            'type'          => 'employee',
            'mobile'        => $row['mobile'] ?? null,
            'speciality'    => null,
            'hospital_name' => null,
            'address'       => null,
            'msl_number'    => null,
            'parent_id'     => $parentId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }

    private function prepareDoctor(array|\ArrayAccess $row): ?array
    {
        if (empty($row['name'])) return null;

        $parentId = null;
        if (!empty($row['employee_code'])) {
            $code = $row['employee_code'];
            if (!array_key_exists($code, $this->parentCache)) {
                $this->parentCache[$code] = User::where('employee_code', $code)->value('id');
            }
            $parentId = $this->parentCache[$code];
        }

        return [
            'name'          => $row['name'],
            'password'      => 'null',
            'employee_code' => null,
            'position_code' => null,
            'designation'   => null,
            'hq_name'       => null,
            'hq_code'       => null,
            'type'          => 'doctor',
            'mobile'        => $row['doctor_mobile'] ?? null,
            'speciality'    => $row['speciality'] ?? null,
            'speciality_code'    => $row['speciality_code'] ?? null,
            'hospital_name' => $row['hospital_name'] ?? null,
            'city'       => $row['city'] ?? null,
            'msl_number'    => $row['msl_number'] ?? null,
            'parent_id'     => $parentId,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];
    }
}
