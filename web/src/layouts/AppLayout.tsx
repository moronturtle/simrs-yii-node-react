import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'

export function AppLayout() {
  const { user, hasRole, logout } = useAuth()

  return (
    <div className="shell">
      <header className="topbar">
        <div>
          <strong>SIMRS Rawat Jalan</strong>
          <span className="muted"> · {user?.name} ({user?.role})</span>
        </div>
        <button className="link-button" type="button" onClick={logout}>
          Keluar
        </button>
      </header>

      <nav className="nav">
        <NavLink to="/">Dashboard</NavLink>
        {hasRole(['ADMIN', 'PENDAFTARAN']) && <NavLink to="/master">Master Data</NavLink>}
        {hasRole(['ADMIN', 'PENDAFTARAN', 'DOKTER']) && <NavLink to="/visits">Antrean</NavLink>}
        {hasRole(['ADMIN', 'FARMASI']) && <NavLink to="/pharmacy">Farmasi</NavLink>}
        {hasRole(['ADMIN', 'LAB']) && <NavLink to="/lab">Laboratorium</NavLink>}
        {hasRole(['ADMIN', 'KASIR']) && <NavLink to="/billing">Billing</NavLink>}
        {hasRole(['ADMIN']) && <NavLink to="/admin">Area Admin</NavLink>}
      </nav>

      <main className="content">
        <Outlet />
      </main>
    </div>
  )
}
