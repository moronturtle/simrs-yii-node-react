import { useState, type FormEvent } from 'react'
import type { Examination } from '../api/clinical'

type Props = {
  initial: Examination | null
  onSubmit: (body: {
    anamnesis?: string
    vital_signs?: Record<string, unknown>
    physical_exam?: string
    primary_diagnosis: string
    secondary_diagnosis?: string
  }) => Promise<void>
}

function vitalValue(initial: Examination | null, key: string) {
  return String(initial?.vital_signs?.[key] ?? '')
}

function numberOrString(value: string) {
  const number = Number(value)
  return Number.isNaN(number) ? value : number
}

export function ExaminationForm({ initial, onSubmit }: Props) {
  const [anamnesis, setAnamnesis] = useState(initial?.anamnesis ?? '')
  const [temperature, setTemperature] = useState(vitalValue(initial, 'temperature'))
  const [bloodPressure, setBloodPressure] = useState(vitalValue(initial, 'blood_pressure'))
  const [pulse, setPulse] = useState(vitalValue(initial, 'pulse'))
  const [respiration, setRespiration] = useState(vitalValue(initial, 'respiration'))
  const [physicalExam, setPhysicalExam] = useState(initial?.physical_exam ?? '')
  const [primary, setPrimary] = useState(initial?.primary_diagnosis ?? '')
  const [secondary, setSecondary] = useState(initial?.secondary_diagnosis ?? '')
  const [saving, setSaving] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setSaving(true)

    const vitalSigns = Object.fromEntries(
      Object.entries({
        temperature,
        blood_pressure: bloodPressure,
        pulse,
        respiration,
      })
        .filter(([, value]) => value !== '')
        .map(([key, value]) => [key, numberOrString(value)]),
    )

    try {
      await onSubmit({
        anamnesis,
        vital_signs: vitalSigns,
        physical_exam: physicalExam,
        primary_diagnosis: primary,
        secondary_diagnosis: secondary,
      })
    } finally {
      setSaving(false)
    }
  }

  return (
    <form className="master-form" onSubmit={handleSubmit}>
      <h2>Pemeriksaan</h2>

      <div className="form-grid">
        <label>
          Keluhan
          <textarea onChange={(e) => setAnamnesis(e.target.value)} value={anamnesis} />
        </label>
        <label>
          Suhu (°C)
          <input onChange={(e) => setTemperature(e.target.value)} type="number" value={temperature} />
        </label>
        <label>
          Tekanan darah
          <input onChange={(e) => setBloodPressure(e.target.value)} placeholder="120/80" value={bloodPressure} />
        </label>
        <label>
          Nadi
          <input onChange={(e) => setPulse(e.target.value)} type="number" value={pulse} />
        </label>
        <label>
          Pernapasan
          <input onChange={(e) => setRespiration(e.target.value)} type="number" value={respiration} />
        </label>
        <label>
          Pemeriksaan fisik
          <textarea onChange={(e) => setPhysicalExam(e.target.value)} value={physicalExam} />
        </label>
        <label>
          Diagnosis utama
          <input onChange={(e) => setPrimary(e.target.value)} required value={primary} />
        </label>
        <label>
          Diagnosis sekunder
          <textarea onChange={(e) => setSecondary(e.target.value)} value={secondary} />
        </label>
      </div>

      <div className="actions">
        <button disabled={saving} type="submit">
          {saving ? 'Menyimpan...' : 'Simpan Pemeriksaan'}
        </button>
      </div>
    </form>
  )
}
