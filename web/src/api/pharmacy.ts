import { yiiApi } from './client'
import type { ListResult, MasterRow } from './masterData'
import type { Visit } from './visits'

export type PrescriptionItem = {
  id: number
  prescription_id: number
  medicine_id: number
  dose: string
  frequency: string
  quantity: number
  price: number | string
  dispensed_at?: string | null
  dispensed_by?: number | null
  medicine?: MasterRow
}

export type Prescription = {
  id: number
  visit_id: number
  doctor_id: number
  status: 'PRESCRIBED' | 'DISPENSED' | 'CANCELLED'
  notes?: string
  created_at: string
  visit?: Visit
  doctor?: MasterRow
  items: PrescriptionItem[]
}

type PrescriptionListResult = Omit<ListResult, 'items'> & {
  items: Prescription[]
}

export function listPrescriptions(
  token: string,
  params: Record<string, string> = {},
) {
  const query = new URLSearchParams({ 'per-page': '100', ...params })
  return yiiApi.get<PrescriptionListResult>(`/prescriptions?${query}`, token)
}

export function createPrescription(
  token: string,
  visitId: number,
  body: {
    notes?: string
    items: {
      medicine_id: number
      dose: string
      frequency: string
      quantity: number
    }[]
  },
) {
  return yiiApi.post<Prescription>(`/visits/${visitId}/prescriptions`, body, token)
}

export function dispensePrescription(token: string, id: number) {
  return yiiApi.post<Prescription>(`/prescriptions/${id}/dispense`, {}, token)
}
