type ApiError = {
  code: string
  message: string
}

type ApiEnvelope<T> = {
  success: boolean
  data: T
  error: ApiError | null
}

async function request<T>(
  base: string,
  path: string,
  options: RequestInit = {},
  token?: string,
): Promise<T> {
  const headers = new Headers(options.headers)

  if (options.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${base}${path}`, { ...options, headers })
  const payload = (await response.json().catch(() => null)) as ApiEnvelope<T> | null

  if (!response.ok || !payload?.success) {
    throw new Error(payload?.error?.message ?? 'Request gagal')
  }

  return payload.data
}

export const yiiApi = {
  get: <T,>(path: string, token?: string) =>
    request<T>('/api-yii', path, {}, token),
  post: <T,>(path: string, body: unknown, token?: string) =>
    request<T>('/api-yii', path, {
      method: 'POST',
      body: JSON.stringify(body),
    }, token),
}

export const nodeApi = {
  get: <T,>(path: string, token?: string) =>
    request<T>('/api-node', path, {}, token),
}
