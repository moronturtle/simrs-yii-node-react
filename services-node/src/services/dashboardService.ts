import { pool } from '../db.js'

export type DashboardSummary = {
  visitsToday: number
  totalVisits: number
  revenue: number
  pendingInvoices: number
  medicineDispensed: number
  pendingLabOrders: number
}

export async function getSummary(): Promise<DashboardSummary> {
  const result = await pool.query(`
    SELECT
      (SELECT COUNT(*) FROM visits WHERE visit_date = CURRENT_DATE) AS visits_today,
      (SELECT COUNT(*) FROM visits) AS total_visits,
      (SELECT COALESCE(SUM(amount), 0) FROM payments) AS revenue,
      (SELECT COUNT(*) FROM invoices WHERE status IN ('UNPAID', 'PARTIAL')) AS pending_invoices,
      (SELECT COALESCE(SUM(quantity), 0) FROM prescription_items WHERE dispensed_at IS NOT NULL) AS medicine_dispensed,
      (SELECT COUNT(*) FROM lab_orders WHERE status IN ('REQUESTED', 'IN_PROGRESS')) AS pending_lab_orders
  `)

  const row = result.rows[0] as Record<string, string>

  return {
    visitsToday: Number(row.visits_today),
    totalVisits: Number(row.total_visits),
    revenue: Number(row.revenue),
    pendingInvoices: Number(row.pending_invoices),
    medicineDispensed: Number(row.medicine_dispensed),
    pendingLabOrders: Number(row.pending_lab_orders),
  }
}
