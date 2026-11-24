import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'

function navClass({ isActive }: { isActive: boolean }) {
  return `whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold ${
    isActive ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'
  }`
}

export function AppLayout() {
  const { user, hasRole, logout } = useAuth()

  return (
    <div className="min-h-screen bg-slate-100">
      <header className="sticky top-0 z-20 border-b border-slate-200 bg-white">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
          <div className="flex items-center gap-3">
            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-teal-600 font-bold text-white">
              S
            </div>
            <div>
              <p className="font-bold text-slate-900">SIMRS</p>
              <p className="text-xs text-slate-500">Rawat Jalan</p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <div className="hidden text-right sm:block">
              <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
              <p className="text-xs text-slate-500">{user?.role}</p>
            </div>
            <button
              className="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
              onClick={logout}
              type="button"
            >
              Keluar
            </button>
          </div>
        </div>
      </header>

      <nav className="border-b border-slate-200 bg-white">
        <div className="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 py-2 sm:px-6">
          <NavLink className={navClass} to="/">Dashboard</NavLink>
          {hasRole(['ADMIN', 'PENDAFTARAN']) && <NavLink className={navClass} to="/master">Master Data</NavLink>}
          {hasRole(['ADMIN', 'PENDAFTARAN', 'DOKTER']) && <NavLink className={navClass} to="/visits">Antrean</NavLink>}
          {hasRole(['ADMIN', 'FARMASI']) && <NavLink className={navClass} to="/pharmacy">Farmasi</NavLink>}
          {hasRole(['ADMIN', 'LAB']) && <NavLink className={navClass} to="/lab">Laboratorium</NavLink>}
          {hasRole(['ADMIN', 'KASIR']) && <NavLink className={navClass} to="/billing">Billing</NavLink>}
          {hasRole(['ADMIN']) && <NavLink className={navClass} to="/admin">Area Admin</NavLink>}
        </div>
      </nav>

      <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <Outlet />
      </main>
    </div>
  )
}
