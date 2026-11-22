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
        {hasRole(['ADMIN']) && <NavLink to="/admin">Area Admin</NavLink>}
      </nav>

      <main className="content">
        <Outlet />
      </main>
    </div>
  )
}
