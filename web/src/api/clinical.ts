import { yiiApi } from './client'
import type { MasterRow } from './masterData'

export type Examination = {
  id: number
  visit_id: number
  anamnesis?: string
  vital_signs?: Record<string, unknown>
  physical_exam?: string
  primary_diagnosis: string
  secondary_diagnosis?: string
  examined_at: string
}

export type VisitAction = {
  id: number
  visit_id: number
  medical_action_id: number
  quantity: number
  price: number
  notes?: string
  medical_action?: MasterRow
}

export function getExamination(token: string, visitId: number) {
  return yiiApi.get<Examination>(`/visits/${visitId}/examination`, token)
}

export function saveExamination(
  token: string,
  visitId: number,
  body: {
    anamnesis?: string
    vital_signs?: Record<string, unknown>
    physical_exam?: string
    primary_diagnosis: string
    secondary_diagnosis?: string
  },
) {
  return yiiApi.post<Examination>(`/visits/${visitId}/examination`, body, token)
}

export function listVisitActions(token: string, visitId: number) {
  return yiiApi.get<{ items: VisitAction[] }>(`/visits/${visitId}/actions`, token)
}

export function addVisitAction(
  token: string,
  visitId: number,
  body: { medical_action_id: number; quantity: number; notes?: string },
) {
  return yiiApi.post<VisitAction>(`/visits/${visitId}/actions`, body, token)
}
