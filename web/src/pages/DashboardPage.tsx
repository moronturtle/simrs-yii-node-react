import { useAuth } from '../auth/useAuth'

const modules = [
  'Pendaftaran dan antrean',
  'Pemeriksaan dokter',
  'Farmasi dan resep',
  'Laboratorium',
  'Billing dan pembayaran',
]

export function DashboardPage() {
  const { user } = useAuth()

  return (
    <section>
      <p className="eyebrow">Dashboard</p>
      <h1>Selamat datang, {user?.name}</h1>
      <p className="muted">
        Role aktif: <strong>{user?.role}</strong>
      </p>

      <div className="grid">
        {modules.map((module) => (
          <article className="card" key={module}>
            <h2>{module}</h2>
          </article>
        ))}
      </div>
    </section>
  )
}
