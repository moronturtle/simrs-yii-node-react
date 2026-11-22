import { Router } from 'express'
import { z } from 'zod'
import { asyncRoute, failure, HttpError, success } from '../http.js'
import { authRequired, roleRequired } from '../middleware/auth.js'
import {
  getOrder,
  listOrders,
  saveResult,
  serializeOrder,
  startOrder,
} from '../services/labService.js'

const statuses = ['REQUESTED', 'IN_PROGRESS', 'COMPLETED', 'FAILED']

const resultSchema = z.object({
  result_text: z.string().trim().min(1, 'Hasil lab wajib diisi'),
  result_values: z.record(z.string(), z.unknown()).optional(),
})

export const labRouter = Router()

labRouter.use(authRequired)

labRouter.get('/orders', asyncRoute(async (req, res) => {
  const status = String(req.query.status ?? '')
  const visitId = Number(req.query.visit_id ?? 0)

  if (status && !statuses.includes(status)) {
    throw new HttpError(422, 'VALIDATION_ERROR', 'Status lab tidak dikenal')
  }

  const orders = await listOrders({
    status: status || undefined,
    visitId: visitId || undefined,
  })

  return success(res, {
    items: orders.map(serializeOrder),
  })
}))

labRouter.get('/orders/:id', asyncRoute(async (req, res) => {
  const order = await getOrder(Number(req.params.id))
  if (!order) throw new HttpError(404, 'NOT_FOUND', 'Order lab tidak ditemukan')

  return success(res, serializeOrder(order))
}))

labRouter.patch('/orders/:id/start', roleRequired(['LAB']), asyncRoute(async (req, res) => {
  const order = await startOrder(Number(req.params.id))
  return success(res, serializeOrder(order!))
}))

labRouter.put('/orders/:id/result', roleRequired(['LAB']), asyncRoute(async (req, res) => {
  const parsed = resultSchema.safeParse(req.body)
  if (!parsed.success) {
    return failure(res, 422, 'VALIDATION_ERROR', 'Data hasil lab tidak valid')
  }

  const order = await saveResult(
    Number(req.params.id),
    parsed.data,
    Number(req.user?.sub),
  )

  return success(res, serializeOrder(order!))
}))
