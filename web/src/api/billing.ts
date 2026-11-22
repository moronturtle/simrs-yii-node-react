import { yiiApi } from './client'
import type { ListResult, MasterRow } from './masterData'
import type { Visit } from './visits'

export type InvoiceItem = {
  id: number
  item_type: 'REGISTRATION' | 'ACTION' | 'LAB' | 'MEDICINE' | 'ADJUSTMENT'
  description: string
  quantity: number
  unit_price: string
  total_price: string
}

export type Payment = {
  id: number
  invoice_id: number
  payment_number: string
  amount: string
  method: 'CASH' | 'NON_CASH'
  idempotency_key: string
  paid_at: string
  paid_by_user?: MasterRow
}

export type Invoice = {
  id: number
  visit_id: number
  invoice_number: string
  status: 'UNPAID' | 'PARTIAL' | 'PAID' | 'CANCELLED'
  total_amount: string
  paid_amount: string
  issued_at: string
  visit?: Visit
  items: InvoiceItem[]
  payments: Payment[]
}

export type Receipt = {
  payment: Payment
  invoice?: Omit<Invoice, 'payments'> | null
  visit?: Visit | null
}

type InvoiceListResult = Omit<ListResult, 'items'> & {
  items: Invoice[]
}

export function listInvoices(token: string, params: Record<string, string> = {}) {
  const query = new URLSearchParams({ 'per-page': '100', ...params })
  return yiiApi.get<InvoiceListResult>(`/invoices?${query}`, token)
}

export function getInvoice(token: string, id: number) {
  return yiiApi.get<Invoice>(`/invoices/${id}`, token)
}

export function payInvoice(
  token: string,
  id: number,
  body: { amount: number; method: 'CASH' | 'NON_CASH'; idempotency_key: string },
) {
  return yiiApi.post<Payment>(`/invoices/${id}/payments`, body, token)
}

export function getReceipt(token: string, paymentId: number) {
  return yiiApi.get<Receipt>(`/payments/${paymentId}/receipt`, token)
}
