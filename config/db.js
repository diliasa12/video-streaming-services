import mysql from "mysql2/promise";
const pool = await mysql.createPool({
  host: process.env.DB_HOST,
  port: process.env.DB_PORT,
  user: process.env.DB_USERNAME,
  database: process.env.DB_DATABASE,
});
export default pool;
