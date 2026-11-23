import { nodeApi } from './client'

export type DashboardSummary = {
  visitsToday: number
  totalVisits: number
  revenue: number
  pendingInvoices: number
  medicineDispensed: number
  pendingLabOrders: number
}

export function getDashboardSummary(token: string) {
  return nodeApi.get<DashboardSummary>('/dashboard/summary', token)
}
