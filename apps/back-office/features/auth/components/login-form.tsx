"use client"

import { useEffect, useState } from "react"
import { useRouter } from "next/navigation"
import { toast } from "sonner"

import {
  loginUser,
  loginUserSchema,
  authService,
  AuthModel,
} from "@workspace/modules/users"

import { useAuth } from "@/features/auth/hooks/use-auth"
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Field, FieldError, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"

type FieldErrors = {
  email?: string
  password?: string
}

export function LoginForm() {
  const router = useRouter()
  const { isAuthenticated, isLoading, refreshUser } = useAuth()

  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [errors, setErrors] = useState<FieldErrors>({})
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      router.replace("/dashboard")
    }
  }, [isLoading, isAuthenticated, router])

  const onSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setErrors({})

    const parsed = loginUserSchema.safeParse({ email, password })

    if (!parsed.success) {
      const flattened = parsed.error.flatten().fieldErrors
      setErrors({
        email: flattened.email?.[0],
        password: flattened.password?.[0],
      })
      return
    }

    setSubmitting(true)

    try {
      const result = await loginUser(parsed.data)

      if (!(result instanceof AuthModel)) {
        toast.error(
          "L'authentification à deux facteurs n'est pas prise en charge ici."
        )
        return
      }

      if (!result.user.hasRoles(["admin"])) {
        await authService.clearTokens()
        toast.error("Accès réservé aux administrateurs.")
        return
      }

      await refreshUser()
      router.replace("/dashboard")
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : "Échec de la connexion."
      )
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="flex min-h-svh items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-sm">
        <CardHeader className="flex flex-col items-center text-center">
          <Badge variant="outline" className="mb-2 w-fit">
            Back-office
          </Badge>
          <CardTitle className="text-2xl">Kennelo Admin</CardTitle>
          <CardDescription>
            Connectez-vous avec votre compte administrateur.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={onSubmit} className="flex flex-col gap-6" noValidate>
            <Field data-invalid={errors.email ? true : undefined}>
              <FieldLabel htmlFor="email">Adresse e-mail</FieldLabel>
              <Input
                id="email"
                name="email"
                type="email"
                autoComplete="email"
                placeholder="nom@exemple.com"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                aria-invalid={errors.email ? true : undefined}
                disabled={submitting}
              />
              <FieldError>{errors.email}</FieldError>
            </Field>

            <Field data-invalid={errors.password ? true : undefined}>
              <FieldLabel htmlFor="password">Mot de passe</FieldLabel>
              <Input
                id="password"
                name="password"
                type="password"
                autoComplete="current-password"
                placeholder="••••••••"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                aria-invalid={errors.password ? true : undefined}
                disabled={submitting}
              />
              <FieldError>{errors.password}</FieldError>
            </Field>

            <Button
              render={<button type="submit" />}
              loading={submitting}
              className="w-full"
            >
              Se connecter
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
