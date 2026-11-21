<?php

use yii\db\Migration;

class m251121_000005_seed_initial_data extends Migration
{
    public function safeUp(): void
    {
        $users = [
            ['admin', 'Administrator', 'ADMIN'],
            ['pendaftaran', 'Petugas Pendaftaran', 'PENDAFTARAN'],
            ['dokter', 'Dokter Rawat Jalan', 'DOKTER'],
            ['farmasi', 'Petugas Farmasi', 'FARMASI'],
            ['lab', 'Petugas Laboratorium', 'LAB'],
            ['kasir', 'Petugas Kasir', 'KASIR'],
        ];

        foreach ($users as [$username, $name, $role]) {
            $this->insert('users', [
                'username' => $username,
                'password_hash' => Yii::$app->security->generatePasswordHash('password123'),
                'name' => $name,
                'role' => $role,
            ]);
        }

        $this->batchInsert('patients', [
            'medical_record_no', 'nik', 'name', 'date_of_birth', 'gender', 'phone', 'address',
        ], [
            ['RM-000001', '3201000101900001', 'Budi Santoso', '1990-01-01', 'L', '081234567801', 'Jl. Melati No. 1'],
            ['RM-000002', '3201000202920002', 'Siti Rahma', '1992-02-02', 'P', '081234567802', 'Jl. Kenanga No. 2'],
        ]);

        $this->batchInsert('doctors', [
            'name', 'specialization', 'phone',
        ], [
            ['dr. Andi Pratama', 'Umum', '081200000001'],
            ['dr. Maya Sari', 'Anak', '081200000002'],
        ]);

        $this->batchInsert('polis', [
            'code', 'name',
        ], [
            ['UMUM', 'Poli Umum'],
            ['ANAK', 'Poli Anak'],
            ['GIGI', 'Poli Gigi'],
        ]);

        $this->batchInsert('doctor_schedules', [
            'doctor_id', 'poli_id', 'day_of_week', 'start_time', 'end_time',
        ], [
            [1, 1, 1, '08:00', '12:00'],
            [2, 2, 1, '13:00', '17:00'],
        ]);

        $this->batchInsert('medical_actions', [
            'code', 'name', 'price',
        ], [
            ['KONSUL', 'Konsultasi dokter', 100000],
            ['INJEKSI', 'Injeksi', 50000],
            ['NEBULIZER', 'Nebulizer', 75000],
        ]);

        $this->batchInsert('medicines', [
            'code', 'name', 'unit', 'price', 'stock',
        ], [
            ['PARA500', 'Paracetamol 500mg', 'tablet', 5000, 100],
            ['AMOX500', 'Amoxicillin 500mg', 'kapsul', 12000, 50],
            ['ORS200', 'Oralit 200ml', 'sachet', 8000, 75],
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('medicines');
        $this->delete('medical_actions');
        $this->delete('doctor_schedules');
        $this->delete('polis');
        $this->delete('doctors');
        $this->delete('patients');
        $this->delete('users');
    }
}
