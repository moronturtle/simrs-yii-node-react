import { useCallback, useEffect, useState } from 'react'
import { getDashboardSummary, type DashboardSummary } from '../api/dashboard'
import { useAuth } from '../auth/useAuth'

function metric(label: string, value: number | string) {
  return { label, value }
}

export function DashboardPage() {
  const { token, user } = useAuth()
  const [summary, setSummary] = useState<DashboardSummary | null>(null)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  const loadSummary = useCallback(async () => {
    if (!token) return

    setLoading(true)
    setError('')

    try {
      setSummary(await getDashboardSummary(token))
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Dashboard service tidak tersedia')
    } finally {
      setLoading(false)
    }
  }, [token])

  useEffect(() => {
    void Promise.resolve().then(loadSummary)
  }, [loadSummary])

  const metrics = summary ? [
    metric('Kunjungan hari ini', summary.visitsToday),
    metric('Total kunjungan', summary.totalVisits),
    metric('Pendapatan', `Rp${summary.revenue.toLocaleString('id-ID')}`),
    metric('Invoice belum lunas', summary.pendingInvoices),
    metric('Obat diserahkan', summary.medicineDispensed),
    metric('Order lab pending', summary.pendingLabOrders),
  ] : []

  return (
    <section>
      <p className="eyebrow">Dashboard</p>
      <h1>Selamat datang, {user?.name}</h1>
      <p className="muted">
        Role aktif: <strong>{user?.role}</strong>
      </p>

      {error && (
        <p className="error">
          {error}{' '}
          <button className="link-button" onClick={() => void loadSummary()} type="button">
            Coba lagi
          </button>
        </p>
      )}
      {loading && <p className="muted">Memuat ringkasan...</p>}

      <div className="grid metric-grid">
        {metrics.map((item) => (
          <article className="card metric" key={item.label}>
            <span>{item.label}</span>
            <strong>{item.value}</strong>
          </article>
        ))}
      </div>
    </section>
  )
}
