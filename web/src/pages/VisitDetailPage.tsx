import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import {
  addVisitAction,
  getExamination,
  listVisitActions,
  saveExamination,
  type Examination,
  type VisitAction,
} from '../api/clinical'
import {
  createLabOrder,
  listLabOrders,
  type LabOrder,
} from '../api/lab'
import { listResource, type MasterRow } from '../api/masterData'
import {
  createPrescription,
  listPrescriptions,
  type Prescription,
} from '../api/pharmacy'
import { getVisit, type Visit } from '../api/visits'
import { useAuth } from '../auth/useAuth'
import { ExaminationForm } from '../components/ExaminationForm'
import { LabOrderForm } from '../components/LabOrderForm'
import { LabOrderTable } from '../components/LabOrderTable'
import { PrescriptionForm } from '../components/PrescriptionForm'
import { VisitActionForm } from '../components/VisitActionForm'

export function VisitDetailPage() {
  const { id } = useParams()
  const visitId = Number(id)
  const { token, hasRole } = useAuth()
  const [visit, setVisit] = useState<Visit | null>(null)
  const [examination, setExamination] = useState<Examination | null>(null)
  const [visitActions, setVisitActions] = useState<VisitAction[]>([])
  const [prescriptions, setPrescriptions] = useState<Prescription[]>([])
  const [labOrders, setLabOrders] = useState<LabOrder[]>([])
  const [labError, setLabError] = useState('')
  const [actions, setActions] = useState<MasterRow[]>([])
  const [medicines, setMedicines] = useState<MasterRow[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const canWrite = hasRole(['DOKTER'])

  const loadData = useCallback(async () => {
    if (!token || !visitId) return

    setLoading(true)
    setError('')
    setLabError('')

    try {
      const [visitResult, actionResult, masterActions, masterMedicines, prescriptionResult] = await Promise.all([
        getVisit(token, visitId),
        listVisitActions(token, visitId),
        listResource('/actions', token),
        listResource('/medicines', token),
        listPrescriptions(token, { visit_id: String(visitId) }),
      ])

      setVisit(visitResult)
      setVisitActions(actionResult.items)
      setActions(masterActions.items)
      setMedicines(masterMedicines.items)
      setPrescriptions(prescriptionResult.items)

      try {
        setExamination(await getExamination(token, visitId))
      } catch {
        setExamination(null)
      }

      try {
        setLabOrders((await listLabOrders(token, { visit_id: String(visitId) })).items)
      } catch {
        setLabOrders([])
        setLabError('Service laboratorium tidak tersedia')
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat kunjungan')
    } finally {
      setLoading(false)
    }
  }, [token, visitId])

  useEffect(() => {
    void Promise.resolve().then(loadData)
  }, [loadData])

  async function handleExamination(body: Parameters<typeof saveExamination>[2]) {
    if (!token) return

    try {
      await saveExamination(token, visitId, body)
      await loadData()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menyimpan pemeriksaan')
    }
  }

  async function handleAction(body: { medical_action_id: number; quantity: number; notes: string }) {
    if (!token) return

    try {
      await addVisitAction(token, visitId, body)
      await loadData()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menambah tindakan')
    }
  }

  async function handleLabOrder(body: { test_name: string; price: number }) {
    if (!token) return

    try {
      await createLabOrder(token, visitId, body)
      await loadData()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal mengirim order lab')
    }
  }

  async function handlePrescription(body: Parameters<typeof createPrescription>[2]) {
    if (!token) return

    try {
      await createPrescription(token, visitId, body)
      await loadData()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menyimpan resep')
    }
  }

  if (loading) {
    return <p className="muted">Memuat kunjungan...</p>
  }

  if (!visit) {
    return <p className="error">{error || 'Kunjungan tidak ditemukan'}</p>
  }

  return (
    <section>
      <Link className="muted" to="/visits">← Kembali ke antrean</Link>
      <p className="eyebrow">Kunjungan {visit.visit_number}</p>
      <h1>{String(visit.patient?.name ?? '-')}</h1>
      <p className="muted">
        {String(visit.poli?.name ?? '-')} · {String(visit.doctor?.name ?? '-')} · Antrean {visit.queue_number} · {visit.status}
      </p>

      {error && <p className="error">{error}</p>}

      <div className="card visit-summary">
        <p><strong>Keluhan:</strong> {visit.chief_complaint || '-'}</p>
        <p><strong>Tanggal:</strong> {visit.visit_date}</p>
      </div>

      {canWrite ? (
        <>
          <ExaminationForm initial={examination} onSubmit={handleExamination} />
          <VisitActionForm actions={actions} onSubmit={handleAction} />
          <LabOrderForm onSubmit={handleLabOrder} />
          <PrescriptionForm medicines={medicines} onSubmit={handlePrescription} />
        </>
      ) : examination && (
        <div className="card">
          <h2>Pemeriksaan</h2>
          <p><strong>Anamnesis:</strong> {examination.anamnesis || '-'}</p>
          <p><strong>Diagnosis utama:</strong> {examination.primary_diagnosis}</p>
          <p><strong>Diagnosis sekunder:</strong> {examination.secondary_diagnosis || '-'}</p>
        </div>
      )}

      <h2 className="section-title">Tindakan</h2>
      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Kode</th>
              <th>Tindakan</th>
              <th>Qty</th>
              <th>Tarif Snapshot</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            {visitActions.map((item) => (
              <tr key={item.id}>
                <td>{String(item.medical_action?.code ?? '-')}</td>
                <td>{String(item.medical_action?.name ?? '-')}</td>
                <td>{item.quantity}</td>
                <td>Rp{Number(item.price).toLocaleString('id-ID')}</td>
                <td>{item.notes || '-'}</td>
              </tr>
            ))}
            {visitActions.length === 0 && <tr><td colSpan={5}>Belum ada tindakan.</td></tr>}
          </tbody>
        </table>
      </div>

      <h2 className="section-title">Laboratorium</h2>
      {labError && <p className="error">{labError}</p>}
      <LabOrderTable orders={labOrders} />

      <h2 className="section-title">Resep</h2>
      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Status</th>
              <th>Obat</th>
              <th>Catatan</th>
            </tr>
          </thead>
          <tbody>
            {prescriptions.map((prescription) => (
              <tr key={prescription.id}>
                <td><span className={`status status-${prescription.status.toLowerCase()}`}>{prescription.status}</span></td>
                <td>
                  {prescription.items.map((item) => (
                    <div key={item.id}>
                      {String(item.medicine?.name ?? '-')} x{item.quantity} · {item.dose} · {item.frequency}
                    </div>
                  ))}
                </td>
                <td>{prescription.notes || '-'}</td>
              </tr>
            ))}
            {prescriptions.length === 0 && <tr><td colSpan={3}>Belum ada resep.</td></tr>}
          </tbody>
        </table>
      </div>
    </section>
  )
}
