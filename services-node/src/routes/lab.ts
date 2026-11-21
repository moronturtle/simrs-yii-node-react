import { Router } from 'express'
import { authRequired } from '../middleware/auth.js'

export const labRouter = Router()

labRouter.use(authRequired)

labRouter.get('/orders', (_req, res) => {
  res.json({
    success: true,
    data: [],
    error: null,
  })
})
