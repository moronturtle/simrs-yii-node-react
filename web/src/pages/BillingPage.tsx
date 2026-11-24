import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { listInvoices, type Invoice } from '../api/billing'
import { useAuth } from '../auth/useAuth'
import { InvoiceDetail } from '../components/InvoiceDetail'

const statuses = ['', 'UNPAID', 'PARTIAL', 'PAID', 'CANCELLED']

function money(value: string | number) {
  return `Rp${Number(value).toLocaleString('id-ID')}`
}

export function BillingPage() {
  const { token } = useAuth()
  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [selectedId, setSelectedId] = useState<number | null>(null)
  const [status, setStatus] = useState('')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const loadInvoices = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      const params: Record<string, string> = {}
      if (status) params.status = status
      const result = await listInvoices(token, params)
      setInvoices(result.items)
      setSelectedId((current) => current ?? result.items[0]?.id ?? null)
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Gagal memuat invoice')
    } finally {
      setLoading(false)
    }
  }, [status, token])

  useEffect(() => {
    void Promise.resolve().then(loadInvoices)
  }, [loadInvoices])

  function handleFilter(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    void loadInvoices()
  }

  return (
    <section>
      <p className="eyebrow">Kasir</p>
      <h1>Billing</h1>
      <p className="muted">Daftar tagihan kunjungan dan pembayaran pasien.</p>

      <form className="toolbar" onSubmit={handleFilter}>
        <select onChange={(event) => setStatus(event.target.value)} value={status}>
          {statuses.map((item) => (
            <option key={item || 'all'} value={item}>{item || 'Semua status'}</option>
          ))}
        </select>
        <button className="secondary" type="submit">Filter</button>
      </form>

      {error && <p className="error">{error}</p>}
      {loading && <p className="muted">Memuat invoice...</p>}

      <div className="table-wrap">
        <table>
          <thead>
            <tr>
              <th>No. Invoice</th>
              <th>Pasien</th>
              <th>Total</th>
              <th>Terbayar</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            {invoices.map((invoice) => (
              <tr
                className={selectedId === invoice.id ? 'selected-row' : ''}
                key={invoice.id}
              >
                <td>{invoice.invoice_number}</td>
                <td>{String(invoice.visit?.patient?.name ?? '-')}</td>
                <td>{money(invoice.total_amount)}</td>
                <td>{money(invoice.paid_amount)}</td>
                <td><span className={`status status-${invoice.status.toLowerCase()}`}>{invoice.status}</span></td>
                <td>
                  <button className="secondary" onClick={() => setSelectedId(invoice.id)} type="button">
                    Lihat
                  </button>
                </td>
              </tr>
            ))}
            {!loading && invoices.length === 0 && <tr><td colSpan={6}>Tidak ada invoice.</td></tr>}
          </tbody>
        </table>
      </div>

      {selectedId && (
        <div
          className="fixed inset-0 z-30 flex items-start justify-center overflow-y-auto bg-slate-900/40 p-4"
          onClick={(event) => {
            if (event.target === event.currentTarget) setSelectedId(null)
          }}
        >
          <div className="my-8 w-full max-w-3xl rounded-xl bg-white p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <h2 className="text-lg font-bold text-slate-900">Detail Tagihan</h2>
              <button className="secondary" onClick={() => setSelectedId(null)} type="button">
                Tutup
              </button>
            </div>
            <InvoiceDetail key={selectedId} invoiceId={selectedId} onChanged={loadInvoices} />
          </div>
        </div>
      )}
    </section>
  )
}
