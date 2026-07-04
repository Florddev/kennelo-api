"use client"

import { useTheme } from "next-themes"

import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Button } from "@/components/ui/button"
import { ButtonGroup } from "@/components/ui/button-group"
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from "@/components/ui/sidebar"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  ComputerIcon,
  Logout01Icon,
  Moon02Icon,
  MoreVerticalCircle01Icon,
  Sun03Icon,
} from "@hugeicons/core-free-icons"

import { useAuth } from "@/features/auth/hooks/use-auth"
import { cn } from "@/lib/utils"

const THEME_OPTIONS = [
  { value: "light", label: "Clair", icon: Sun03Icon },
  { value: "dark", label: "Sombre", icon: Moon02Icon },
  { value: "system", label: "Système", icon: ComputerIcon },
]

export function NavUser() {
  const { isMobile } = useSidebar()
  const { user, logout } = useAuth()
  const { theme, setTheme } = useTheme()

  if (!user) {
    return null
  }

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger
            render={
              <SidebarMenuButton size="lg" className="aria-expanded:bg-muted" />
            }
          >
            <Avatar className="size-8 rounded-lg">
              <AvatarImage
                src={user.avatarUrl ?? undefined}
                alt={user.getFullName()}
              />
              <AvatarFallback className="rounded-lg">
                {user.getInitials()}
              </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-start text-sm leading-tight">
              <span className="truncate font-medium">{user.getFullName()}</span>
              <span className="truncate text-xs text-foreground/70">
                {user.email}
              </span>
            </div>
            <HugeiconsIcon
              icon={MoreVerticalCircle01Icon}
              strokeWidth={2}
              className="ms-auto size-4"
            />
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="min-w-56"
            side={isMobile ? "bottom" : "right"}
            align="end"
            sideOffset={4}
          >
            <DropdownMenuGroup>
              <DropdownMenuLabel className="p-0 font-normal">
                <div className="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                  <Avatar className="size-8 rounded-lg">
                    <AvatarImage
                      src={user.avatarUrl ?? undefined}
                      alt={user.getFullName()}
                    />
                    <AvatarFallback className="rounded-lg">
                      {user.getInitials()}
                    </AvatarFallback>
                  </Avatar>
                  <div className="grid flex-1 text-start text-sm leading-tight">
                    <span className="truncate font-medium">
                      {user.getFullName()}
                    </span>
                    <span className="truncate text-xs text-muted-foreground">
                      {user.email}
                    </span>
                  </div>
                </div>
              </DropdownMenuLabel>
            </DropdownMenuGroup>
            <DropdownMenuSeparator />
            <div className="flex items-center justify-between gap-2 px-2 py-1.5">
              <span className="text-sm font-medium">Thème</span>
              <ButtonGroup>
                {THEME_OPTIONS.map((option) => {
                  const active = (theme ?? "system") === option.value
                  return (
                    <Button
                      key={option.value}
                      variant="secondary"
                      size="icon-sm"
                      aria-label={option.label}
                      aria-pressed={active}
                      onClick={() => setTheme(option.value)}
                      className={cn(active && "bg-background")}
                    >
                      <HugeiconsIcon icon={option.icon} strokeWidth={2} />
                    </Button>
                  )
                })}
              </ButtonGroup>
            </div>
            <DropdownMenuSeparator />
            <DropdownMenuItem variant="destructive" onClick={() => logout()}>
              <HugeiconsIcon icon={Logout01Icon} strokeWidth={2} />
              Se déconnecter
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  )
}
