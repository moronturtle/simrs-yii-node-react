import type { Role } from '../auth/context'

export type ResourceKey =
  | 'patients'
  | 'doctors'
  | 'polis'
  | 'schedules'
  | 'actions'
  | 'medicines'

export type FieldConfig = {
  name: string
  label: string
  type?: 'text' | 'number' | 'date' | 'time' | 'select' | 'textarea' | 'checkbox'
  required?: boolean
  options?: { value: string; label: string }[]
  source?: 'doctors' | 'polis'
}

export type ResourceConfig = {
  key: ResourceKey
  title: string
  endpoint: string
  writeRoles: Role[]
  columns: { name: string; label: string; type?: 'boolean' | 'number' }[]
  fields: FieldConfig[]
}

const activeField: FieldConfig = {
  name: 'is_active',
  label: 'Aktif',
  type: 'checkbox',
}

export const resources: ResourceConfig[] = [
  {
    key: 'patients',
    title: 'Pasien',
    endpoint: '/patients',
    writeRoles: ['ADMIN', 'PENDAFTARAN'],
    columns: [
      { name: 'medical_record_no', label: 'No. RM' },
      { name: 'name', label: 'Nama' },
      { name: 'nik', label: 'NIK' },
      { name: 'phone', label: 'Telepon' },
    ],
    fields: [
      { name: 'medical_record_no', label: 'No. RM', required: true },
      { name: 'nik', label: 'NIK' },
      { name: 'name', label: 'Nama', required: true },
      { name: 'date_of_birth', label: 'Tanggal lahir', type: 'date' },
      {
        name: 'gender',
        label: 'Jenis kelamin',
        type: 'select',
        options: [
          { value: 'L', label: 'Laki-laki' },
          { value: 'P', label: 'Perempuan' },
        ],
      },
      { name: 'phone', label: 'Telepon' },
      { name: 'address', label: 'Alamat', type: 'textarea' },
    ],
  },
  {
    key: 'doctors',
    title: 'Dokter',
    endpoint: '/doctors',
    writeRoles: ['ADMIN'],
    columns: [
      { name: 'name', label: 'Nama' },
      { name: 'specialization', label: 'Spesialis' },
      { name: 'phone', label: 'Telepon' },
      { name: 'is_active', label: 'Status', type: 'boolean' },
    ],
    fields: [
      { name: 'name', label: 'Nama', required: true },
      { name: 'specialization', label: 'Spesialis' },
      { name: 'phone', label: 'Telepon' },
      activeField,
    ],
  },
  {
    key: 'polis',
    title: 'Poli',
    endpoint: '/polis',
    writeRoles: ['ADMIN'],
    columns: [
      { name: 'code', label: 'Kode' },
      { name: 'name', label: 'Nama' },
      { name: 'is_active', label: 'Status', type: 'boolean' },
    ],
    fields: [
      { name: 'code', label: 'Kode', required: true },
      { name: 'name', label: 'Nama', required: true },
      activeField,
    ],
  },
  {
    key: 'schedules',
    title: 'Jadwal Dokter',
    endpoint: '/schedules',
    writeRoles: ['ADMIN'],
    columns: [
      { name: 'doctor.name', label: 'Dokter' },
      { name: 'poli.name', label: 'Poli' },
      { name: 'day_of_week', label: 'Hari' },
      { name: 'start_time', label: 'Mulai' },
      { name: 'end_time', label: 'Selesai' },
    ],
    fields: [
      { name: 'doctor_id', label: 'Dokter', type: 'select', source: 'doctors', required: true },
      { name: 'poli_id', label: 'Poli', type: 'select', source: 'polis', required: true },
      {
        name: 'day_of_week',
        label: 'Hari',
        type: 'select',
        required: true,
        options: [
          { value: '0', label: 'Minggu' },
          { value: '1', label: 'Senin' },
          { value: '2', label: 'Selasa' },
          { value: '3', label: 'Rabu' },
          { value: '4', label: 'Kamis' },
          { value: '5', label: 'Jumat' },
          { value: '6', label: 'Sabtu' },
        ],
      },
      { name: 'start_time', label: 'Jam mulai', type: 'time', required: true },
      { name: 'end_time', label: 'Jam selesai', type: 'time', required: true },
      activeField,
    ],
  },
  {
    key: 'actions',
    title: 'Tindakan',
    endpoint: '/actions',
    writeRoles: ['ADMIN'],
    columns: [
      { name: 'code', label: 'Kode' },
      { name: 'name', label: 'Nama' },
      { name: 'price', label: 'Tarif', type: 'number' },
      { name: 'is_active', label: 'Status', type: 'boolean' },
    ],
    fields: [
      { name: 'code', label: 'Kode', required: true },
      { name: 'name', label: 'Nama', required: true },
      { name: 'price', label: 'Tarif', type: 'number', required: true },
      activeField,
    ],
  },
  {
    key: 'medicines',
    title: 'Obat',
    endpoint: '/medicines',
    writeRoles: ['ADMIN'],
    columns: [
      { name: 'code', label: 'Kode' },
      { name: 'name', label: 'Nama' },
      { name: 'unit', label: 'Satuan' },
      { name: 'price', label: 'Harga', type: 'number' },
      { name: 'stock', label: 'Stok', type: 'number' },
    ],
    fields: [
      { name: 'code', label: 'Kode', required: true },
      { name: 'name', label: 'Nama', required: true },
      { name: 'unit', label: 'Satuan', required: true },
      { name: 'price', label: 'Harga', type: 'number', required: true },
      { name: 'stock', label: 'Stok', type: 'number', required: true },
      activeField,
    ],
  },
]
