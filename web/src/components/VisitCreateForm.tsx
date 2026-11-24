import { useState, type FormEvent } from 'react'
import type { MasterRow } from '../api/masterData'

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

type Props = {
  patients: MasterRow[]
  schedules: MasterRow[]
  onSubmit: (values: { patient_id: number; schedule_id: number; chief_complaint: string }) => Promise<void>
  onCancel: () => void
}

function scheduleLabel(schedule: MasterRow) {
  const doctor = schedule.doctor as MasterRow | undefined
  const poli = schedule.poli as MasterRow | undefined
  const day = dayNames[Number(schedule.day_of_week)] ?? `Hari ${schedule.day_of_week}`

  return `${day} · ${doctor?.name ?? '-'} · ${poli?.name ?? '-'} · ${String(schedule.start_time).slice(0, 5)}`
}

export function VisitCreateForm({ patients, schedules, onSubmit, onCancel }: Props) {
  const [patientId, setPatientId] = useState('')
  const [scheduleId, setScheduleId] = useState('')
  const [complaint, setComplaint] = useState('')
  const [saving, setSaving] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    try {
      await onSubmit({
        patient_id: Number(patientId),
        schedule_id: Number(scheduleId),
        chief_complaint: complaint,
      })
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Antrean Baru</h2>

      <div className="form-grid">
        <label>
          Pasien
          <select
            onChange={(event) => setPatientId(event.target.value)}
            required
            value={patientId}
          >
            <option value="">Pilih pasien</option>
            {patients.map((patient) => (
              <option key={patient.id} value={patient.id}>
                {String(patient.medical_record_no)} · {String(patient.name)}
              </option>
            ))}
          </select>
        </label>

        <label>
          Jadwal dokter
          <select
            onChange={(event) => setScheduleId(event.target.value)}
            required
            value={scheduleId}
          >
            <option value="">Pilih jadwal</option>
            {schedules.map((schedule) => (
              <option key={schedule.id} value={schedule.id}>
                {scheduleLabel(schedule)}
              </option>
            ))}
          </select>
        </label>

        <label>
          Keluhan utama
          <textarea
            onChange={(event) => setComplaint(event.target.value)}
            placeholder="Contoh: demam dan batuk"
            value={complaint}
          />
        </label>
      </div>

      <div className="actions">
        <button disabled={saving} type="submit">
          {saving ? 'Menyimpan...' : 'Simpan'}
        </button>
        <button className="secondary" onClick={onCancel} type="button">
          Batal
        </button>
      </div>
    </form>
  )
}
