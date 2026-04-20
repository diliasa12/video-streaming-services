// models/User.js (mirror minimal dari Gateway)
import mongoose from "mongoose";
const userSchema = new mongoose.Schema(
  {
    name: { type: String, required: true },
    email: { type: String, required: true, unique: true },
    password: {
      type: String,
      required: true,
      min: 8,
    },
    avatar_url: { type: String },
    role: { type: String, enum: ["student", "instructor"], default: "student" },
  },
  { timestamps: true },
);

module.exports = mongoose.model("User", userSchema);
