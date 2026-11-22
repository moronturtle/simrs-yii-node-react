import { yiiApi } from './client'

export type MasterRow = Record<string, unknown> & { id: number }

export type ListResult = {
  items: MasterRow[]
  pagination: {
    page: number
    page_size: number
    total: number
  }
}

export function listResource(endpoint: string, token: string, search = '') {
  const query = new URLSearchParams({ 'per-page': '100' })

  if (search) {
    query.set('q', search)
  }

  return yiiApi.get<ListResult>(`${endpoint}?${query}`, token)
}

export function createResource(endpoint: string, token: string, body: unknown) {
  return yiiApi.post<MasterRow>(endpoint, body, token)
}

export function updateResource(
  endpoint: string,
  token: string,
  id: number,
  body: unknown,
) {
  return yiiApi.put<MasterRow>(`${endpoint}/${id}`, body, token)
}

export function deleteResource(endpoint: string, token: string, id: number) {
  return yiiApi.delete<{ deleted: boolean }>(`${endpoint}/${id}`, token)
}
