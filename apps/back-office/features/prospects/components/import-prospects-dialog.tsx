"use client"

import { useState } from "react"
import { toast } from "sonner"

import { importProspects } from "@workspace/modules/prospects"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogClose,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogPanel,
  DialogPopup,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import { Field, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { useImportProgress } from "@/features/prospects/hooks/use-import-progress"
import { useProspectAction } from "@/features/prospects/hooks/use-prospect-action"

export function ImportProspectsDialog() {
  const { busy, run } = useProspectAction()
  const { track } = useImportProgress()
  const [open, setOpen] = useState(false)
  const [location, setLocation] = useState("")
  const [maxResults, setMaxResults] = useState("20")

  const onSubmit = () => {
    if (!location.trim()) {
      toast.error("La localisation est requise.")
      return
    }

    run(
      async () => {
        const result = await importProspects({
          location: location.trim(),
          maxResults: Number(maxResults) || 20,
        })
        if (result) {
          track(result.id)
        }
      },
      "Prospection lancée en arrière-plan. Vous serez notifié à la fin.",
      async () => {
        setOpen(false)
        setLocation("")
      }
    )
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger render={<Button size="sm" />}>Importer</DialogTrigger>
      <DialogPopup className="max-w-md">
        <DialogHeader>
          <DialogTitle>Importer des prospects</DialogTitle>
          <DialogDescription>
            Recherche les pensions animalières sur Google Maps via Apify pour la
            zone indiquée. L&apos;import se fait en arrière-plan, vous pouvez
            continuer à naviguer.
          </DialogDescription>
        </DialogHeader>
        <DialogPanel className="flex flex-col gap-4">
          <Field>
            <FieldLabel htmlFor="import-location">Localisation</FieldLabel>
            <Input
              id="import-location"
              value={location}
              onChange={(event) => setLocation(event.target.value)}
              placeholder="Lyon, France"
            />
          </Field>
          <Field>
            <FieldLabel htmlFor="import-max">
              Nombre max de résultats
            </FieldLabel>
            <Input
              id="import-max"
              type="number"
              min={1}
              max={500}
              value={maxResults}
              onChange={(event) => setMaxResults(event.target.value)}
            />
          </Field>
        </DialogPanel>
        <DialogFooter>
          <DialogClose render={<Button variant="outline" />}>
            Annuler
          </DialogClose>
          <Button onClick={onSubmit} loading={busy}>
            Lancer l&apos;import
          </Button>
        </DialogFooter>
      </DialogPopup>
    </Dialog>
  )
}
