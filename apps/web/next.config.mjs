/* global process */
import createNextIntlPlugin from 'next-intl/plugin';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const isMobileBuild = process.env.NEXT_PUBLIC_PLATFORM === 'mobile';
const isDockerBuild = process.env.NEXT_PUBLIC_PLATFORM === 'docker';

/** @type {import('next').NextConfig} */
const nextConfig = {
    transpilePackages: ["@workspace/ui"],
    turbopack: {
        root: join(__dirname, '../../'),
    },
    webpack(config) {
        config.module.rules.push({
            test: /\.svg$/,
            use: ['@svgr/webpack'],
        })
        return config
    },
    images: {
        unoptimized: true,
    },

    ...(isMobileBuild && {
        output: 'export',
    }),
    ...(isDockerBuild && {
        output: 'standalone',
        outputFileTracingRoot: join(__dirname, '../../'),
    }),
}

const withNextIntl = createNextIntlPlugin('./lib/i18n/request.ts');
export default withNextIntl(nextConfig);