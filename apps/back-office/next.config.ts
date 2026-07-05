import type { NextConfig } from "next"

const nextConfig: NextConfig = {
  transpilePackages: [
    "@workspace/common",
    "@workspace/modules",
    "@workspace/ui",
  ],
}

export default nextConfig
