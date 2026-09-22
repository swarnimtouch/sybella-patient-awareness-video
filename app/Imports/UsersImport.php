<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    protected array $employeeCache = [];

    protected string $importType;

    protected int $inserted = 0;

    protected int $updated = 0;

    protected int $skipped = 0;

    protected int $currentRow = 1;

    protected array $issues = [];

    public function __construct(string $importType = 'employee')
    {
        $this->importType = $importType;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $this->currentRow++;

                if ($this->importType === 'employee') {
                    $this->upsertEmployee($row);
                } else {
                    $this->upsertDoctor($row);
                }
            }
        });
    }

    public function summary(): array
    {
        return [
            'type' => $this->importType,
            'inserted' => $this->inserted,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'issues' => $this->issues,
        ];
    }

    private function upsertEmployee(array|\ArrayAccess $row): void
    {
        $name = $this->value($row, 'name', 'employee_name');
        $positionCode = $this->value($row, 'position_code');
        $employeeCode = $this->value($row, 'employee_code');

        if (! $name || ! $positionCode || ! $employeeCode) {
            $this->skip('Employee requires name, position_code and employee_code.');

            return;
        }

        $parentId = null;
        $parentCode = $this->value($row, 'parent_employee_code');
        if ($parentCode) {
            $parentId = $this->employeeIdByCode($parentCode);
        }

        $employee = User::query()
            ->where('type', 'employee')
            ->where('position_code', $positionCode)
            ->first();

        $attributes = [
            'name' => $name,
            'employee_code' => $employeeCode,
            'position_code' => $positionCode,
            'designation' => $this->value($row, 'designation'),
            'hq_name' => $this->value($row, 'hq_name'),
            'hq_code' => $this->value($row, 'hq_code'),
            'type' => 'employee',
            'mobile' => $this->value($row, 'mobile'),
            'speciality' => null,
            'speciality_code' => null,
            'hospital_name' => null,
            'address' => null,
            'msl_number' => null,
            'city' => null,
            'parent_id' => $parentId,
        ];

        if ($employee) {
            if ($employee->employee_code !== $employeeCode) {
                $attributes['password'] = Hash::make($employeeCode, ['rounds' => 4]);
            }

            $employee->fill($attributes)->save();
            $this->updated++;
        } else {
            $attributes['password'] = Hash::make($employeeCode, ['rounds' => 4]);
            $employee = User::create($attributes);
            $this->inserted++;
        }

        $this->employeeCache[$employeeCode] = $employee->id;
    }

    private function upsertDoctor(array|\ArrayAccess $row): void
    {
        $name = $this->value($row, 'name', 'doctor_name');
        $mslNumber = $this->value($row, 'msl_number');
        $employeeCode = $this->value($row, 'employee_code');

        if (! $name || ! $mslNumber || ! $employeeCode) {
            $this->skip('Doctor requires name, msl_number and employee_code.');

            return;
        }

        $parentId = $this->employeeIdByCode($employeeCode);
        if (! $parentId) {
            $this->skip("Employee code {$employeeCode} was not found for this doctor.");

            return;
        }

        $doctor = User::query()
            ->where('type', 'doctor')
            ->where('msl_number', $mslNumber)
            ->first();

        $attributes = [
            'name' => $name,
            'employee_code' => null,
            'position_code' => null,
            'designation' => null,
            'hq_name' => null,
            'hq_code' => null,
            'type' => 'doctor',
            'mobile' => $this->value($row, 'doctor_mobile', 'mobile'),
            'speciality' => $this->value($row, 'speciality'),
            'speciality_code' => $this->value($row, 'speciality_code'),
            'hospital_name' => $this->value($row, 'hospital_name', 'hospital'),
            'city' => $this->value($row, 'city'),
            'msl_number' => $mslNumber,
            'parent_id' => $parentId,
        ];

        $address = $this->value($row, 'address', 'hospital_address');
        if ($address) {
            $attributes['address'] = $address;
        }

        if ($doctor) {
            $doctor->fill($attributes)->save();
            $this->updated++;
        } else {
            $attributes['password'] = Hash::make('null', ['rounds' => 4]);
            User::create($attributes);
            $this->inserted++;
        }
    }

    private function employeeIdByCode(string $employeeCode): ?int
    {
        if (! array_key_exists($employeeCode, $this->employeeCache)) {
            $this->employeeCache[$employeeCode] = User::query()
                ->where('type', 'employee')
                ->where('employee_code', $employeeCode)
                ->value('id');
        }

        return $this->employeeCache[$employeeCode];
    }

    private function value(array|\ArrayAccess $row, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;

            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function skip(string $message): void
    {
        $this->skipped++;

        if (count($this->issues) < 20) {
            $this->issues[] = "Row {$this->currentRow}: {$message}";
        }
    }
}
