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
    <section className="space-y-6">
      <div className="rounded-2xl bg-slate-900 p-6 text-white sm:p-8">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p className="text-sm font-semibold text-teal-300">Dashboard</p>
            <h1 className="mt-2 text-3xl font-bold">
              Selamat datang, {user?.name}
            </h1>
            <p className="mt-2 text-sm text-slate-300">
              Ringkasan operasional rawat jalan.
            </p>
          </div>
          <span className="w-fit rounded-full bg-white/10 px-3 py-1 text-sm font-semibold">
            {user?.role}
          </span>
        </div>
      </div>

      {error && (
        <div className="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <span>{error}</span>
          <button
            className="rounded-lg bg-red-600 px-3 py-2 font-semibold text-white hover:bg-red-700"
            onClick={() => void loadSummary()}
            type="button"
          >
            Coba lagi
          </button>
        </div>
      )}

      {loading && <p className="text-sm text-slate-500">Memuat ringkasan...</p>}

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {metrics.map((item) => (
          <article className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" key={item.label}>
            <p className="text-sm text-slate-500">{item.label}</p>
            <strong className="mt-3 block text-3xl font-bold text-slate-900">
              {item.value}
            </strong>
          </article>
        ))}
      </div>
    </section>
  )
}
