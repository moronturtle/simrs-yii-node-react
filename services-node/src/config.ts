import 'dotenv/config'

export const config = {
  port: Number(process.env.PORT ?? 3000),
  corsOrigin: process.env.CORS_ORIGIN ?? 'http://localhost:5173',
  jwtSecret: process.env.JWT_SECRET ?? 'dev-only-secret',
  databaseUrl: process.env.NODE_DATABASE_URL ?? '',
}
