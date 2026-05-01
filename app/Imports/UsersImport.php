<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{


    public function model(array $row)
    {
        $parent = null;
        if (!empty($row['parent_employee_code'])) {
            $parent = User::where('employee_code', $row['parent_employee_code'])->first();
        }

        if ($row['type'] == 'employee') {
            $password = Hash::make($row['employee_code']);
        } else {
            $password = 'null';
        }

        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'password' => $password,
            'employee_code' => $row['employee_code'],
            'type' => $row['type'],
            'mobile' => $row['doctor_mobile'] ?? null,
            'speciality' => $row['speciality'] ?? null,
            'hospital_name' => $row['hospital_name'] ?? null,
            'address' => $row['hospital_address'] ?? null,
            'msl_number' => $row['msl_number'] ?? null,
            'parent_id' => $parent ? $parent->id : null,
        ]);
    }
}
