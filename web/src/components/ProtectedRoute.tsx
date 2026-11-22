import { Navigate, Outlet, useLocation } from 'react-router-dom'
import type { Role } from '../auth/context'
import { useAuth } from '../auth/useAuth'

export function ProtectedRoute({ roles }: { roles?: Role[] }) {
  const { token, user, hasRole } = useAuth()
  const location = useLocation()

  if (!token || !user) {
    return <Navigate to="/login" replace state={{ from: location }} />
  }

  if (roles && !hasRole(roles)) {
    return <Navigate to="/" replace />
  }

  return <Outlet />
}
