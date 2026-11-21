import cors from 'cors'
import express from 'express'
import helmet from 'helmet'
import { config } from './config.js'
import { errorHandler, notFound } from './middleware/error.js'
import { dashboardRouter } from './routes/dashboard.js'
import { healthRouter } from './routes/health.js'
import { labRouter } from './routes/lab.js'

const app = express()

app.use(helmet())
app.use(cors({ origin: config.corsOrigin }))
app.use(express.json())

app.use('/api', healthRouter)
app.use('/api/lab', labRouter)
app.use('/api/dashboard', dashboardRouter)

app.use(notFound)
app.use(errorHandler)

app.listen(config.port, () => {
  console.log(`Node service berjalan di port ${config.port}`)
})
