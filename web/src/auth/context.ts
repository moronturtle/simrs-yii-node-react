import { createContext } from 'react'

export type Role = 'ADMIN' | 'PENDAFTARAN' | 'DOKTER' | 'FARMASI' | 'LAB' | 'KASIR'

export type AuthUser = {
  id: number
  username: string
  name: string
  role: Role
}

export type LoginResponse = {
  token: string
  user: AuthUser
}

export type AuthContextValue = {
  token: string | null
  user: AuthUser | null
  login: (username: string, password: string) => Promise<void>
  logout: () => void
  hasRole: (roles: Role[]) => boolean
}

export const AuthContext = createContext<AuthContextValue | undefined>(undefined)
