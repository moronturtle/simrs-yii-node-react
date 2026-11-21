import './App.css'

const modules = [
  'Pendaftaran dan antrean',
  'Pemeriksaan dokter',
  'Farmasi dan resep',
  'Laboratorium',
  'Billing dan pembayaran',
]

function App() {
  return (
    <main className="page">
      <section className="hero">
        <p className="eyebrow">SIMRS Rawat Jalan</p>
        <h1>Sistem pelayanan pasien terintegrasi</h1>
        <p className="subtitle">
          Alur utama dari pendaftaran sampai pembayaran.
        </p>
      </section>

      <section className="grid">
        {modules.map((module) => (
          <article className="card" key={module}>
            <h2>{module}</h2>
          </article>
        ))}
      </section>
    </main>
  )
}

export default App
