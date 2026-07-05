import { DM_Sans, Figtree, Inter } from "next/font/google"

import "./globals.css"
import { ThemeProvider } from "@/components/providers/theme-provider"
import { AuthProvider } from "@/features/auth/hooks/use-auth"
import { NotificationsProvider } from "@/features/notifications/hooks/use-notifications"
import { ImportProgressProvider } from "@/features/prospects/hooks/use-import-progress"
import { Toaster } from "@/components/ui/sonner"
import { cn } from "@/lib/utils"

const interHeading = Figtree({ subsets: ["latin"], variable: "--font-heading" })

const inter = DM_Sans({ subsets: ["latin"], variable: "--font-sans" })

const geistMono = Inter({ subsets: ["latin"], variable: "--font-mono" })

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode
}>) {
  return (
    <html
      lang="fr"
      suppressHydrationWarning
      className={cn(
        "antialiased",
        inter.variable,
        interHeading.variable,
        geistMono.variable
      )}
    >
      <body>
        <ThemeProvider>
          <AuthProvider>
            <NotificationsProvider>
              <ImportProgressProvider>{children}</ImportProgressProvider>
            </NotificationsProvider>
          </AuthProvider>
          <Toaster />
        </ThemeProvider>
      </body>
    </html>
  )
}
