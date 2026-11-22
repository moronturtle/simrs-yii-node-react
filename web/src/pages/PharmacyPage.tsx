import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { dispensePrescription, listPrescriptions, type Prescription } from '../api/pharmacy'
import { useAuth } from '../auth/useAuth'

const statuses = ['', 'PRESCRIBED', 'DISPENSED', 'CANCELLED']

function itemSummary(prescription: Prescription) {
  return prescription.items.map((item) => (
    `${item.medicine?.name ?? 'Obat'} x${item.quantity}`
  )).join(', ')
}

export function PharmacyPage() {
  const { token, hasRole } = useAuth()
  const [prescriptions, setPrescriptions] = useState<Prescription[]>([])
  const [status, setStatus] = useState('PRESCRIBED')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const canDispense = hasRole(['FARMASI'])

  const loadPrescriptions = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      const params: Record<string, string> = {}
      if (status) params.status = status

      const result = await listPrescriptions(token, params)
      setPrescriptions(result.items)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat resep')
    } finally {
      setLoading(false)
    }
  }, [status, token])

  useEffect(() => {
    void Promise.resolve().then(loadPrescriptions)
  }, [loadPrescriptions])

  function handleFilter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    void loadPrescriptions()
  }

  async function handleDispense(prescription: Prescription) {
    if (!token || !window.confirm('Serahkan obat dan kurangi stok?')) return

    setError('')
    try {
      await dispensePrescription(token, prescription.id)
      await loadPrescriptions()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menyerahkan obat')
    }
  }

  return (
    <section>
      <p className="eyebrow">Farmasi</p>
      <h1>Daftar Resep</h1>
      <p className="muted">Proses resep dokter dan serahkan obat ke pasien.</p>

      <form className="toolbar" onSubmit={handleFilter}>
        <select onChange={(event) => setStatus(event.target.value)} value={status}>
          {statuses.map((item) => (
            <option key={item || 'all'} value={item}>{item || 'Semua status'}</option>
          ))}
        </select>
        <button className="secondary" type="submit">Filter</button>
      </form>

      {error && <p className="error">{error}</p>}
      {loading && <p className="muted">Memuat resep...</p>}

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Visit</th>
              <th>Pasien</th>
              <th>Dokter</th>
              <th>Obat</th>
              <th>Status</th>
              {canDispense && <th>Aksi</th>}
            </tr>
          </thead>
          <tbody>
            {prescriptions.map((prescription) => (
              <tr key={prescription.id}>
                <td>{prescription.visit?.visit_number ?? '-'}</td>
                <td>{String(prescription.visit?.patient?.name ?? '-')}</td>
                <td>{String(prescription.doctor?.name ?? '-')}</td>
                <td>{itemSummary(prescription)}</td>
                <td><span className={`status status-${prescription.status.toLowerCase()}`}>{prescription.status}</span></td>
                {canDispense && (
                  <td>
                    <button
                      disabled={prescription.status !== 'PRESCRIBED'}
                      onClick={() => void handleDispense(prescription)}
                      type="button"
                    >
                      Dispense
                    </button>
                  </td>
                )}
              </tr>
            ))}
            {!loading && prescriptions.length === 0 && (
              <tr><td colSpan={canDispense ? 6 : 5}>Tidak ada resep.</td></tr>
            )}
          </tbody>
        </table>
      </div>
    </section>
  )
}
