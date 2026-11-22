import { yiiApi } from './client'
import type { ListResult, MasterRow } from './masterData'

export const visitStatuses = [
  'REGISTERED',
  'IN_EXAM',
  'PHARMACY',
  'LAB',
  'BILLING',
  'PAID',
  'CANCELLED',
] as const

export type VisitStatus = (typeof visitStatuses)[number]

export type Visit = {
  id: number
  visit_number: string
  patient_id: number
  poli_id: number
  doctor_id: number
  schedule_id: number
  visit_date: string
  queue_number: number
  status: VisitStatus
  chief_complaint?: string
  patient?: MasterRow
  poli?: MasterRow
  doctor?: MasterRow
  schedule?: MasterRow
}

type VisitListResult = Omit<ListResult, 'items'> & {
  items: Visit[]
}

export function listVisits(
  token: string,
  params: Record<string, string> = {},
) {
  const query = new URLSearchParams({ 'per-page': '100', ...params })
  return yiiApi.get<VisitListResult>(`/visits?${query}`, token)
}

export function getVisit(token: string, id: number) {
  return yiiApi.get<Visit>(`/visits/${id}`, token)
}

export function createVisit(
  token: string,
  body: { patient_id: number; schedule_id: number; chief_complaint?: string },
) {
  return yiiApi.post<Visit>('/visits', body, token)
}

export function updateVisitStatus(token: string, id: number, status: VisitStatus) {
  return yiiApi.patch<Visit>(`/visits/${id}/status`, { status }, token)
}
