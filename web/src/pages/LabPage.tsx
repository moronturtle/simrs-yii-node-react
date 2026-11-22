import { useCallback, useEffect, useState, type FormEvent } from 'react'
import {
  listLabOrders,
  saveLabResult,
  startLabOrder,
  type LabOrder,
} from '../api/lab'
import { useAuth } from '../auth/useAuth'

const statuses = ['', 'REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'FAILED']

export function LabPage() {
  const { token, hasRole } = useAuth()
  const [orders, setOrders] = useState<LabOrder[]>([])
  const [status, setStatus] = useState('')
  const [selected, setSelected] = useState<LabOrder | null>(null)
  const [resultText, setResultText] = useState('')
  const [resultValues, setResultValues] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const canWork = hasRole(['LAB'])

  const loadOrders = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      const params: Record<string, string> = {}
      if (status) params.status = status
      setOrders((await listLabOrders(token, params)).items)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Service laboratorium tidak tersedia')
    } finally {
      setLoading(false)
    }
  }, [status, token])

  useEffect(() => {
    void Promise.resolve().then(loadOrders)
  }, [loadOrders])

  async function handleStart(order: LabOrder) {
    if (!token) return

    try {
      await startLabOrder(token, order.id)
      await loadOrders()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memproses order')
    }
  }

  function selectOrder(order: LabOrder) {
    setSelected(order)
    setResultText(order.result?.result_text ?? '')
    setResultValues(order.result?.result_values ? JSON.stringify(order.result.result_values, null, 2) : '')
  }

  async function handleResult(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!token || !selected) return

    setError('')
    let values: Record<string, unknown> | undefined

    try {
      values = resultValues.trim() ? JSON.parse(resultValues) as Record<string, unknown> : undefined
    } catch {
      setError('Result values harus berupa JSON object valid')
      return
    }

    try {
      await saveLabResult(token, selected.id, {
        result_text: resultText,
        result_values: values,
      })
      setSelected(null)
      setResultText('')
      setResultValues('')
      await loadOrders()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal menyimpan hasil')
    }
  }

  return (
    <section>
      <p className="eyebrow">Laboratorium</p>
      <h1>Order Lab</h1>
      <p className="muted">Proses permintaan lab dan input hasil pemeriksaan.</p>

      <form className="toolbar" onSubmit={(event) => { event.preventDefault(); void loadOrders() }}>
        <select onChange={(event) => setStatus(event.target.value)} value={status}>
          {statuses.map((item) => (
            <option key={item || 'all'} value={item}>{item || 'Semua status'}</option>
          ))}
        </select>
        <button className="secondary" type="submit">Filter</button>
      </form>

      {error && <p className="error">{error} <button className="link-button" onClick={() => void loadOrders()} type="button">Coba lagi</button></p>}
      {loading && <p className="muted">Memuat order...</p>}

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Order</th>
              <th>Pasien</th>
              <th>Pemeriksaan</th>
              <th>Status</th>
              <th>Hasil</th>
              {canWork && <th>Aksi</th>}
            </tr>
          </thead>
          <tbody>
            {orders.map((order) => (
              <tr key={order.id}>
                <td>{order.order_number}</td>
                <td>{String(order.visit?.patient?.name ?? '-')}</td>
                <td>{order.test_name}</td>
                <td><span className={`status status-${order.status.toLowerCase()}`}>{order.status}</span></td>
                <td>{order.result?.result_text ?? '-'}</td>
                {canWork && (
                  <td className="row-actions">
                    {order.status === 'REQUESTED' && (
                      <button onClick={() => void handleStart(order)} type="button">Proses</button>
                    )}
                    {['IN_PROGRESS', 'COMPLETED'].includes(order.status) && (
                      <button className="secondary" onClick={() => selectOrder(order)} type="button">
                        {order.result ? 'Update Hasil' : 'Input Hasil'}
                      </button>
                    )}
                  </td>
                )}
              </tr>
            ))}
            {!loading && orders.length === 0 && <tr><td colSpan={canWork ? 6 : 5}>Tidak ada order.</td></tr>}
          </tbody>
        </table>
      </div>

      {selected && canWork && (
        <form className="master-form" onSubmit={handleResult}>
          <h2>Hasil {selected.test_name}</h2>
          <label>
            Hasil teks
            <textarea onChange={(event) => setResultText(event.target.value)} required value={resultText} />
          </label>
          <label>
            Nilai detail (JSON opsional)
            <textarea
              onChange={(event) => setResultValues(event.target.value)}
              placeholder='{"hemoglobin":"13.5"}'
              value={resultValues}
            />
          </label>
          <div className="actions">
            <button className="secondary" onClick={() => setSelected(null)} type="button">Batal</button>
            <button type="submit">Simpan Hasil</button>
          </div>
        </form>
      )}
    </section>
  )
}
