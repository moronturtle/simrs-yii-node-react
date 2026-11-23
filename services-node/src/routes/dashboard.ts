import { Router } from 'express'
import { asyncRoute, success } from '../http.js'
import { authRequired } from '../middleware/auth.js'
import { getSummary } from '../services/dashboardService.js'

export const dashboardRouter = Router()

dashboardRouter.use(authRequired)

dashboardRouter.get('/summary', asyncRoute(async (_req, res) => {
  return success(res, await getSummary())
}))
