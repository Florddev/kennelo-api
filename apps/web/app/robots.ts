import { MetadataRoute } from "next";
import { routing } from "@/lib/i18n/routing";

export default function robots(): MetadataRoute.Robots {
    const sitemapUrls =
        routing.domains?.map((domain) => `https://${domain.domain}/sitemap.xml`) || [];

    return {
        rules: [
            {
                userAgent: "*",
                allow: "/",
                disallow: ["/admin", "/auth"],
            },
        ],
        sitemap: sitemapUrls,
    };
}
