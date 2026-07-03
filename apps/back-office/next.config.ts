import type { NextConfig } from "next"

const nextConfig: NextConfig = {
  transpilePackages: ["@workspace/common", "@workspace/modules"],
}

export default nextConfig
