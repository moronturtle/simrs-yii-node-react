import { nodeApi, yiiApi } from './client'
import type { Visit } from './visits'

export type LabOrder = {
  id: number
  visit_id: number
  order_number: string
  test_name: string
  status: 'REQUESTED' | 'IN_PROGRESS' | 'COMPLETED' | 'FAILED'
  price: number | string
  requested_at: string
  visit?: Visit
  requested_by_user?: { id: number; name: string }
  result?: {
    result_text: string
    result_values?: Record<string, unknown>
    resulted_at: string
    resulted_by_name?: string
  } | null
}

export function listLabOrders(token: string, params: Record<string, string> = {}) {
  const query = new URLSearchParams(params)
  return nodeApi.get<{ items: LabOrder[] }>(`/lab/orders?${query}`, token)
}

export function createLabOrder(
  token: string,
  visitId: number,
  body: { test_name: string; price: number },
) {
  return yiiApi.post<LabOrder>(`/visits/${visitId}/lab-orders`, body, token)
}

export function startLabOrder(token: string, id: number) {
  return nodeApi.patch<LabOrder>(`/lab/orders/${id}/start`, {}, token)
}

export function saveLabResult(
  token: string,
  id: number,
  body: { result_text: string; result_values?: Record<string, unknown> },
) {
  return nodeApi.put<LabOrder>(`/lab/orders/${id}/result`, body, token)
}
