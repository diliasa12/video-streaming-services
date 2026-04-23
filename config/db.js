import mysql from "mysql2/promise";
const pool = await mysql.createPool({
  host: "localhost",
  port: 3306,
  user: "root",
  database: "user_main",
});
export default pool;
