<?php

declare(strict_types=1);

class PatientService
{
    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->config = require __DIR__ . '/../config/app.php';

    }

    /*
    |--------------------------------------------------------------------------
    | Create Patient
    |--------------------------------------------------------------------------
    */

    public function createPatient(array $patient, int $registeredBy): array
    {
        $errors = $this->validate($patient);

        if (!empty($errors)) {

            return [

                'success' => false,

                'patient_id' => null,

                'hospital_number' => null,

                'errors' => $errors

            ];

        }

        try {

            $this->pdo->beginTransaction();

            $sql = "

                INSERT INTO patients (

                    hospital_number,

                    first_name,

                    last_name,

                    gender,

                    date_of_birth,

                    phone,

                    email,

                    address,

                    blood_group,

                    genotype,

                    allergies,

                    next_of_kin,

                    next_of_kin_phone,

                    registered_by

                )

                VALUES (

                    NULL,

                    :first_name,

                    :last_name,

                    :gender,

                    :date_of_birth,

                    :phone,

                    :email,

                    :address,

                    :blood_group,

                    :genotype,

                    :allergies,

                    :next_of_kin,

                    :next_of_kin_phone,

                    :registered_by

                )

            ";

            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([

                ':first_name'        => $patient['first_name'],
                ':last_name'         => $patient['last_name'],
                ':gender'            => $patient['gender'],
                ':date_of_birth'     => $patient['date_of_birth'] ?: null,
                ':phone'             => $patient['phone'],
                ':email'             => $patient['email'],
                ':address'           => $patient['address'],
                ':blood_group'       => $patient['blood_group'],
                ':genotype'          => $patient['genotype'],
                ':allergies'         => $patient['allergies'],
                ':next_of_kin'       => $patient['next_of_kin'],
                ':next_of_kin_phone' => $patient['next_of_kin_phone'],
                ':registered_by'     => $registeredBy

            ]);

            $patientId = (int)$this->pdo->lastInsertId();

            $hospitalNumber = $this->generateHospitalNumber($patientId);

            $update = $this->pdo->prepare(

                "

                UPDATE patients

                SET hospital_number = :hospital_number

                WHERE id = :id

                "

            );

            $update->execute([

                ':hospital_number' => $hospitalNumber,

                ':id' => $patientId

            ]);

            $this->pdo->commit();

            return [

                'success' => true,

                'patient_id' => $patientId,

                'hospital_number' => $hospitalNumber,

                'errors' => []

            ];

        } catch (Throwable $e) {

    if ($this->pdo->inTransaction()) {

        $this->pdo->rollBack();

    }

    return [

        'success' => false,

        'patient_id' => null,

        'hospital_number' => null,

        'errors' => [

            $e->getMessage()

        ]

    ];

}
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    private function validate(array $patient): array
    {
        $errors = [];

        if (empty($patient['first_name'])) {

            $errors[] = 'First name is required.';

        }

        if (empty($patient['last_name'])) {

            $errors[] = 'Last name is required.';

        }

        if (empty($patient['gender'])) {

            $errors[] = 'Gender is required.';

        }

        if (!empty($patient['email'])) {

            if (!filter_var($patient['email'], FILTER_VALIDATE_EMAIL)) {

                $errors[] = 'Invalid email address.';

            }

        }

        return $errors;
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Hospital Number
    |--------------------------------------------------------------------------
    */

    private function generateHospitalNumber(int $patientId): string
    {
        return sprintf(

            '%s-%s-%06d',

            $this->config['hospital']['code'],

            date('Y'),

            $patientId

        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Patient
    |--------------------------------------------------------------------------
    */

    public function getPatientById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(

            "SELECT * FROM patients WHERE id = ?"

        );

        $stmt->execute([$id]);

        $patient = $stmt->fetch(PDO::FETCH_ASSOC);

        return $patient ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Search Patients
    |--------------------------------------------------------------------------
    */

    public function searchPatients(array $filters): array
{
    $sql = "SELECT * FROM patients";

    $conditions = [];

    $params = [];

    if (!empty($filters['hospital_number'])) {

        $conditions[] = "hospital_number LIKE :hospital_number";

        $params['hospital_number'] =
            '%' . trim($filters['hospital_number']) . '%';

    }

    if (!empty($filters['first_name'])) {

        $conditions[] = "first_name LIKE :first_name";

        $params['first_name'] =
            '%' . trim($filters['first_name']) . '%';

    }

    if (!empty($filters['last_name'])) {

        $conditions[] = "last_name LIKE :last_name";

        $params['last_name'] =
            '%' . trim($filters['last_name']) . '%';

    }

    if (!empty($filters['phone'])) {

        $conditions[] = "phone LIKE :phone";

        $params['phone'] =
            '%' . trim($filters['phone']) . '%';

    }

    if (!empty($filters['date_of_birth'])) {

        $conditions[] = "date_of_birth = :date_of_birth";

        $params['date_of_birth'] =
            $filters['date_of_birth'];

    }

    if (!empty($filters['gender'])) {

        $conditions[] = "gender = :gender";

        $params['gender'] =
            $filters['gender'];

    }

    if (!empty($conditions)) {

        $sql .= " WHERE " . implode(" AND ", $conditions);

    }

    $sql .= " ORDER BY last_name, first_name";

    $stmt = $this->pdo->prepare($sql);

    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    

    /*
|--------------------------------------------------------------------------
| Update Patient
|--------------------------------------------------------------------------
*/

public function updatePatient(
    int $id,
    array $patient
): array
{
    /*
    |--------------------------------------------------------------------------
    | Validate Patient Data
    |--------------------------------------------------------------------------
    */

    $errors = $this->validate($patient);

    if (!empty($errors)) {

        return [

            'success' => false,

            'errors' => $errors

        ];

    }

    try {

        $sql = "

            UPDATE patients

            SET

                first_name = :first_name,

                last_name = :last_name,

                gender = :gender,

                date_of_birth = :date_of_birth,

                phone = :phone,

                email = :email,

                address = :address,

                blood_group = :blood_group,

                genotype = :genotype,

                allergies=:allergies,

                next_of_kin = :next_of_kin,

                next_of_kin_phone = :next_of_kin_phone

            WHERE id = :id

        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([

            ':first_name'        => $patient['first_name'],

            ':last_name'         => $patient['last_name'],

            ':gender'            => $patient['gender'],

            ':date_of_birth'     => $patient['date_of_birth'] ?: null,

            ':phone'             => $patient['phone'],

            ':email'             => $patient['email'],

            ':address'           => $patient['address'],

            ':blood_group'       => $patient['blood_group'],

            ':genotype'          => $patient['genotype'],

            ':allergies'         => $patient['allergies'],

            ':next_of_kin'       => $patient['next_of_kin'],

            ':next_of_kin_phone' => $patient['next_of_kin_phone'],

            ':id'                => $id

        ]);

        return [

            'success' => true,

            'errors' => []

        ];

    } catch (Throwable $e) {

        return [

            'success' => false,

            'errors' => [

                $e->getMessage()

            ]

        ];

    }
}
}