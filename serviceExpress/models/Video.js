// models/Video.js
import mongoose from "mongoose";

const videoSchema = new mongoose.Schema(
  {
    title: { type: String, required: true, trim: true },
    description: { type: String, default: "" },
    instructor_id: {
      type: mongoose.Schema.Types.ObjectId,
      ref: "User",
      required: true,
    },
    filename: { type: String, required: true },
    file_path: { type: String, required: true },
    mime_type: { type: String, default: "video/mp4" },
    size_bytes: { type: Number, default: 0 },
    duration_sec: { type: Number, default: 0 },
    resolution: { type: String, default: "1080p" },
    thumbnail_url: { type: String },
    tags: [{ type: String }],
    view_count: { type: Number, default: 0 },
    status: {
      type: String,
      enum: ["processing", "ready", "failed"],
      default: "processing",
      index: true,
    },
    is_public: { type: Boolean, default: false },
  },
  { timestamps: true },
);

module.exports = mongoose.model("Video", videoSchema);
