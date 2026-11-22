import {
  useCallback,
  useMemo,
  useState,
  type PropsWithChildren,
} from 'react'
import { yiiApi } from '../api/client'
import {
  AuthContext,
  type AuthUser,
  type LoginResponse,
  type Role,
} from './context'

const TOKEN_KEY = 'simrs_token'
const USER_KEY = 'simrs_user'

function readStoredUser() {
  const raw = localStorage.getItem(USER_KEY)

  try {
    return raw ? (JSON.parse(raw) as AuthUser) : null
  } catch {
    localStorage.removeItem(USER_KEY)
    return null
  }
}

export function AuthProvider({ children }: PropsWithChildren) {
  const [token, setToken] = useState(() => localStorage.getItem(TOKEN_KEY))
  const [user, setUser] = useState<AuthUser | null>(readStoredUser)

  const login = useCallback(async (username: string, password: string) => {
    const result = await yiiApi.post<LoginResponse>('/auth/login', {
      username,
      password,
    })

    localStorage.setItem(TOKEN_KEY, result.token)
    localStorage.setItem(USER_KEY, JSON.stringify(result.user))
    setToken(result.token)
    setUser(result.user)
  }, [])

  const logout = useCallback(() => {
    localStorage.removeItem(TOKEN_KEY)
    localStorage.removeItem(USER_KEY)
    setToken(null)
    setUser(null)
  }, [])

  const hasRole = useCallback(
    (roles: Role[]) => Boolean(user && roles.includes(user.role)),
    [user],
  )

  const value = useMemo(
    () => ({ token, user, login, logout, hasRole }),
    [token, user, login, logout, hasRole],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}
