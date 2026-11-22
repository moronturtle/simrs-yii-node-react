import 'dotenv/config'

export const config = {
  port: Number(process.env.PORT ?? 3000),
  corsOrigin: process.env.CORS_ORIGIN ?? 'http://localhost:5173',
  jwtSecret: process.env.JWT_SECRET ?? 'local-dev-jwt-secret-0123456789abcdef',
  databaseUrl: process.env.NODE_DATABASE_URL ?? '',
}
