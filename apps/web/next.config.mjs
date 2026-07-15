/* global process */
import createNextIntlPlugin from 'next-intl/plugin';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const isMobileBuild = process.env.NEXT_PUBLIC_PLATFORM === 'mobile';
const isDockerBuild = process.env.NEXT_PUBLIC_PLATFORM === 'docker';

const apiOrigin = process.env.NEXT_PUBLIC_API_URL
    ? new URL(process.env.NEXT_PUBLIC_API_URL).origin
    : '';
const reverbHostPort = process.env.NEXT_PUBLIC_REVERB_HOST
    ? `${process.env.NEXT_PUBLIC_REVERB_HOST}:${process.env.NEXT_PUBLIC_REVERB_PORT ?? ''}`
    : '';
const reverbOrigins = reverbHostPort ? `ws://${reverbHostPort} wss://${reverbHostPort}` : '';
const googleIdentityOrigin = 'https://accounts.google.com';
const mapTilesOrigins = 'https://basemaps.cartocdn.com https://*.basemaps.cartocdn.com';
const stripeScriptOrigins = 'https://connect-js.stripe.com https://js.stripe.com https://*.js.stripe.com';
const stripeFrameOrigins =
    'https://connect-js.stripe.com https://js.stripe.com https://*.js.stripe.com https://hooks.stripe.com';
const stripeConnectOrigins =
    'https://api.stripe.com https://merchant-ui-api.stripe.com https://r.stripe.com https://errors.stripe.com';
const devThemePreviewOrigin = process.env.NODE_ENV === 'development' ? 'https://tweakcn.com' : '';

const contentSecurityPolicy = [
    "default-src 'self'",
    "base-uri 'self'",
    "frame-ancestors 'none'",
    "object-src 'none'",
    "img-src 'self' data: blob: https:",
    "font-src 'self' data:",
    "style-src 'self' 'unsafe-inline'",
    "worker-src 'self' blob:",
    `script-src 'self' 'unsafe-inline' 'unsafe-eval' ${googleIdentityOrigin} ${stripeScriptOrigins} ${devThemePreviewOrigin}`,
    `frame-src 'self' ${googleIdentityOrigin} ${stripeFrameOrigins}`,
    `connect-src 'self' ${apiOrigin} ${reverbOrigins} ${googleIdentityOrigin} ${mapTilesOrigins} ${stripeConnectOrigins}`,
]
    .map((directive) => directive.replace(/\s+/g, ' ').trim())
    .join('; ');

const securityHeaders = [
    { key: 'Content-Security-Policy', value: contentSecurityPolicy },
    { key: 'X-Frame-Options', value: 'DENY' },
    { key: 'X-Content-Type-Options', value: 'nosniff' },
    { key: 'Referrer-Policy', value: 'strict-origin-when-cross-origin' },
    { key: 'Strict-Transport-Security', value: 'max-age=63072000; includeSubDomains; preload' },
];

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

    ...(isMobileBuild
        ? {
              output: 'export',
          }
        : {
              async headers() {
                  return [
                      {
                          source: '/:path*',
                          headers: securityHeaders,
                      },
                  ];
              },
              async rewrites() {
                  return [
                      {
                          source: '/ingest/static/:path*',
                          destination: 'https://eu-assets.i.posthog.com/static/:path*',
                      },
                      {
                          source: '/ingest/array/:path*',
                          destination: 'https://eu-assets.i.posthog.com/array/:path*',
                      },
                      {
                          source: '/ingest/:path*',
                          destination: 'https://eu.i.posthog.com/:path*',
                      },
                  ];
              },
          }),
    skipTrailingSlashRedirect: true,
    ...(isDockerBuild && {
        output: 'standalone',
        outputFileTracingRoot: join(__dirname, '../../'),
    }),
}

const withNextIntl = createNextIntlPlugin('./lib/i18n/request.ts');
export default withNextIntl(nextConfig);