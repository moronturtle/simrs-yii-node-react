export function AdminPage() {
  return (
    <section>
      <p className="eyebrow">Area Admin</p>
      <h1>Administrasi sistem</h1>
      <p className="muted">
        Halaman ini hanya bisa dibuka role ADMIN. Dipakai sebagai bukti route guard.
      </p>
    </section>
  )
}
