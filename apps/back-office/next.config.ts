import type { NextConfig } from "next"

const nextConfig: NextConfig = {
  output: "standalone",
  transpilePackages: [
    "@workspace/common",
    "@workspace/modules",
    "@workspace/ui",
  ],
}

export default nextConfig
