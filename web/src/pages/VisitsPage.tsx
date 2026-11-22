import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { Link } from 'react-router-dom'
import { listResource, type MasterRow } from '../api/masterData'
import {
  createVisit,
  listVisits,
  updateVisitStatus,
  visitStatuses,
  type Visit,
  type VisitStatus,
} from '../api/visits'
import { useAuth } from '../auth/useAuth'
import { VisitCreateForm } from '../components/VisitCreateForm'

function nameOf(row: MasterRow | undefined, field = 'name') {
  return String(row?.[field] ?? '-')
}

export function VisitsPage() {
  const { token, hasRole } = useAuth()
  const [visits, setVisits] = useState<Visit[]>([])
  const [patients, setPatients] = useState<MasterRow[]>([])
  const [schedules, setSchedules] = useState<MasterRow[]>([])
  const [search, setSearch] = useState('')
  const [query, setQuery] = useState('')
  const [status, setStatus] = useState('')
  const [visitDate, setVisitDate] = useState('')
  const [formOpen, setFormOpen] = useState(false)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const canCreate = hasRole(['ADMIN', 'PENDAFTARAN'])
  const canUpdateStatus = hasRole(['ADMIN', 'PENDAFTARAN', 'DOKTER'])

  const loadVisits = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      const params: Record<string, string> = {}
      if (query) params.q = query
      if (status) params.status = status
      if (visitDate) params.visit_date = visitDate

      const result = await listVisits(token, params)
      setVisits(result.items)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat antrean')
    } finally {
      setLoading(false)
    }
  }, [query, status, token, visitDate])

  useEffect(() => {
    void Promise.resolve().then(loadVisits)
  }, [loadVisits])

  useEffect(() => {
    if (!token) return

    Promise.all([
      listResource('/patients', token),
      listResource('/schedules', token),
    ]).then(([patientResult, scheduleResult]) => {
      setPatients(patientResult.items)
      setSchedules(scheduleResult.items)
    }).catch(() => undefined)
  }, [token])

  function handleSearch(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setQuery(search)
  }

  async function handleCreate(values: {
    patient_id: number
    schedule_id: number
    chief_complaint: string
  }) {
    if (!token) return

    try {
      await createVisit(token, values)
      setFormOpen(false)
      await loadVisits()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal membuat kunjungan')
    }
  }

  async function handleStatusChange(row: Visit, nextStatus: VisitStatus) {
    if (!token) return

    setError('')
    try {
      await updateVisitStatus(token, row.id, nextStatus)
      await loadVisits()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal mengubah status')
    }
  }

  return (
    <section>
      <p className="eyebrow">Pendaftaran</p>
      <h1>Antrean Rawat Jalan</h1>
      <p className="muted">Daftarkan pasien ke jadwal dokter dan pantau status kunjungan.</p>

      <div className="toolbar">
        <form onSubmit={handleSearch}>
          <input
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Cari no. visit / pasien..."
            value={search}
          />
          <select onChange={(event) => setStatus(event.target.value)} value={status}>
            <option value="">Semua status</option>
            {visitStatuses.map((item) => <option key={item} value={item}>{item}</option>)}
          </select>
          <input
            onChange={(event) => setVisitDate(event.target.value)}
            type="date"
            value={visitDate}
          />
          <button className="secondary" type="submit">Filter</button>
        </form>

        {canCreate && (
          <button onClick={() => setFormOpen(true)} type="button">Daftar Kunjungan</button>
        )}
      </div>

      {error && <p className="error">{error}</p>}
      {loading && <p className="muted">Memuat antrean...</p>}

      {formOpen && canCreate && (
        <VisitCreateForm
          onCancel={() => setFormOpen(false)}
          onSubmit={handleCreate}
          patients={patients}
          schedules={schedules}
        />
      )}

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Visit</th>
              <th>Antrean</th>
              <th>Pasien</th>
              <th>Poli</th>
              <th>Dokter</th>
              <th>Tanggal</th>
              <th>Status</th>
              {canUpdateStatus && <th>Aksi</th>}
            </tr>
          </thead>
          <tbody>
            {visits.map((visit) => (
              <tr key={visit.id}>
                <td><Link to={`/visits/${visit.id}`}>{visit.visit_number}</Link></td>
                <td><strong>{visit.queue_number}</strong></td>
                <td>
                  {nameOf(visit.patient)}
                  <div className="muted">{String(visit.patient?.medical_record_no ?? '')}</div>
                </td>
                <td>{nameOf(visit.poli)}</td>
                <td>{nameOf(visit.doctor)}</td>
                <td>{visit.visit_date}</td>
                <td><span className={`status status-${visit.status.toLowerCase()}`}>{visit.status}</span></td>
                {canUpdateStatus && (
                  <td>
                    <select
                      disabled={['PAID', 'CANCELLED'].includes(visit.status)}
                      onChange={(event) => void handleStatusChange(visit, event.target.value as VisitStatus)}
                      value={visit.status}
                    >
                      {visitStatuses.map((item) => <option key={item} value={item}>{item}</option>)}
                    </select>
                  </td>
                )}
              </tr>
            ))}
            {!loading && visits.length === 0 && (
              <tr><td colSpan={canUpdateStatus ? 8 : 7}>Belum ada kunjungan.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  )
}
