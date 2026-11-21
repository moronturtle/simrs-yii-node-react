import { Router } from 'express'
import { authRequired } from '../middleware/auth.js'

export const dashboardRouter = Router()

dashboardRouter.use(authRequired)

dashboardRouter.get('/summary', (_req, res) => {
  res.json({
    success: true,
    data: {
      visits: 0,
      revenue: 0,
      pendingPayments: 0,
      medicineUsage: 0,
    },
    error: null,
  })
})
