import { useState, type FormEvent } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'

type LocationState = {
  from?: {
    pathname?: string
  }
}

export function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [username, setUsername] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setLoading(true)

    try {
      await login(username, password)
      const state = location.state as LocationState | null
      navigate(state?.from?.pathname ?? '/', { replace: true })
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Login gagal')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-100 px-4">
      <section className="grid w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-xl md:grid-cols-2">
        <aside className="hidden bg-slate-900 p-10 text-white md:flex md:flex-col md:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-600 text-lg font-bold">
              S
            </div>
            <div>
              <p className="font-bold">SIMRS</p>
              <p className="text-sm text-slate-400">Rawat Jalan</p>
            </div>
          </div>

          <div>
            <h1 className="text-3xl font-bold leading-tight">
              Layanan rawat jalan terpadu.
            </h1>
            <p className="mt-4 text-sm leading-6 text-slate-300">
              Kelola pendaftaran, antrean, pemeriksaan, laboratorium, farmasi,
              billing, dan pembayaran dari satu aplikasi.
            </p>
          </div>

          <div className="space-y-3 text-sm text-slate-200">
            <div className="rounded-lg bg-white/10 px-4 py-3">Pendaftaran</div>
            <div className="rounded-lg bg-white/10 px-4 py-3">Farmasi</div>
            <div className="rounded-lg bg-white/10 px-4 py-3">Billing</div>
          </div>
        </aside>

        <form className="p-8 sm:p-10" onSubmit={handleSubmit}>
          <div className="mb-8 flex items-center gap-3 md:hidden">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-600 font-bold text-white">
              S
            </div>
            <div>
              <p className="font-bold text-slate-900">SIMRS</p>
              <p className="text-xs text-slate-500">Rawat Jalan</p>
            </div>
          </div>

          <p className="text-sm font-semibold text-teal-700">SIMRS</p>
          <h2 className="mt-2 text-2xl font-bold text-slate-900">Masuk</h2>

          <div className="mt-8 space-y-5">
            <label className="block text-sm font-semibold text-slate-700">
              Username
              <input
                autoComplete="username"
                className="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                onChange={(event) => setUsername(event.target.value)}
                required
                value={username}
              />
            </label>

            <label className="block text-sm font-semibold text-slate-700">
              Password
              <input
                autoComplete="current-password"
                className="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 outline-none focus:border-teal-600 focus:ring-2 focus:ring-teal-100"
                onChange={(event) => setPassword(event.target.value)}
                required
                type="password"
                value={password}
              />
            </label>
          </div>

          {error && (
            <p className="mt-5 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
              {error}
            </p>
          )}

          <button
            className="mt-6 w-full rounded-lg bg-teal-700 px-4 py-2.5 font-semibold text-white transition hover:bg-teal-800 disabled:opacity-60"
            disabled={loading}
            type="submit"
          >
            {loading ? 'Memproses...' : 'Masuk'}
          </button>
        </form>
      </section>
    </main>
  )
}
