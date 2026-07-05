"use client"

import { formatDistanceToNow } from "date-fns"
import { fr } from "date-fns/locale"
import { HugeiconsIcon } from "@hugeicons/react"
import {
  Alert02Icon,
  Notification01Icon,
  CheckmarkCircle02Icon,
  Delete02Icon,
  InformationCircleIcon,
} from "@hugeicons/core-free-icons"

import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { cn } from "@/lib/utils"
import {
  useNotifications,
  type AppNotification,
  type NotificationType,
} from "@/features/notifications/hooks/use-notifications"

const TYPE_ICON: Record<NotificationType, typeof Notification01Icon> = {
  success: CheckmarkCircle02Icon,
  error: Alert02Icon,
  info: InformationCircleIcon,
}

const TYPE_COLOR: Record<NotificationType, string> = {
  success: "text-green-500",
  error: "text-destructive",
  info: "text-blue-500",
}

function NotificationItem({
  notification,
  onRead,
  onRemove,
}: {
  notification: AppNotification
  onRead: (id: string) => void
  onRemove: (id: string) => void
}) {
  return (
    <div
      className={cn(
        "flex gap-3 px-3 py-2.5 text-sm transition-colors hover:bg-muted/50",
        !notification.read && "bg-primary/5"
      )}
      onMouseEnter={() => {
        if (!notification.read) onRead(notification.id)
      }}
    >
      <HugeiconsIcon
        icon={TYPE_ICON[notification.type]}
        strokeWidth={2}
        className={cn("mt-0.5 size-4 shrink-0", TYPE_COLOR[notification.type])}
      />
      <div className="grid flex-1 gap-0.5 leading-tight">
        <span className="font-medium">{notification.title}</span>
        {notification.body ? (
          <span className="text-xs text-muted-foreground">
            {notification.body}
          </span>
        ) : null}
        <span className="text-[11px] text-muted-foreground">
          {formatDistanceToNow(notification.createdAt, {
            addSuffix: true,
            locale: fr,
          })}
        </span>
      </div>
      <Button
        variant="ghost"
        size="icon-sm"
        className="shrink-0 opacity-60 hover:opacity-100"
        onClick={() => onRemove(notification.id)}
        aria-label="Supprimer"
      >
        <HugeiconsIcon
          icon={Delete02Icon}
          strokeWidth={2}
          className="size-3.5"
        />
      </Button>
    </div>
  )
}

export function NotificationBell() {
  const {
    notifications,
    unreadCount,
    markAsRead,
    markAllRead,
    remove,
    clearAll,
  } = useNotifications()

  return (
    <DropdownMenu>
      <DropdownMenuTrigger
        render={<Button variant="ghost" size="icon" className="relative" />}
      >
        <HugeiconsIcon icon={Notification01Icon} strokeWidth={2} />
        {unreadCount > 0 ? (
          <span className="absolute -end-0.5 -top-0.5 flex min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] leading-4 font-semibold text-white">
            {unreadCount > 9 ? "9+" : unreadCount}
          </span>
        ) : null}
        <span className="sr-only">Notifications</span>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between border-b px-3 py-2">
          <span className="text-sm font-semibold">Notifications</span>
          {notifications.length > 0 ? (
            <div className="flex items-center gap-1">
              {unreadCount > 0 ? (
                <Button
                  variant="ghost"
                  size="sm"
                  className="h-7 text-xs"
                  onClick={markAllRead}
                >
                  Tout lire
                </Button>
              ) : null}
              <Button
                variant="ghost"
                size="sm"
                className="h-7 text-xs"
                onClick={clearAll}
              >
                Effacer
              </Button>
            </div>
          ) : null}
        </div>
        <div className="max-h-96 overflow-y-auto">
          {notifications.length === 0 ? (
            <div className="px-3 py-8 text-center text-sm text-muted-foreground">
              Aucune notification
            </div>
          ) : (
            <div className="divide-y">
              {notifications.map((notification) => (
                <NotificationItem
                  key={notification.id}
                  notification={notification}
                  onRead={markAsRead}
                  onRemove={remove}
                />
              ))}
            </div>
          )}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}
