import { Router } from 'express'
import { pool } from '../services/db.js'

export const healthRouter = Router()

healthRouter.get('/health', async (_req, res, next) => {
  try {
    await pool.query('select 1')

    res.json({
      success: true,
      data: { service: 'node', status: 'ok' },
      error: null,
    })
  } catch (error) {
    next(error)
  }
})
