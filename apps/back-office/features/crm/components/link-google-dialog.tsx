"use client"

import { useState } from "react"
import { toast } from "sonner"
import { HugeiconsIcon } from "@hugeicons/react"
import { Search01Icon, StarIcon } from "@hugeicons/core-free-icons"

import {
  linkActivityGoogle,
  searchActivityGoogle,
  type GoogleCandidate,
  type ProfessionalModel,
} from "@workspace/modules/professionals"

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
} from "@/components/ui/dialog"
import { Field, FieldLabel } from "@/components/ui/field"
import { Input } from "@/components/ui/input"
import { Spinner } from "@/components/ui/spinner"
import { useCrmAction } from "@/features/crm/hooks/use-crm-action"

type LinkGoogleDialogProps = {
  activity: ProfessionalModel
  open: boolean
  onOpenChange: (open: boolean) => void
  onDone: () => Promise<void>
}

export function LinkGoogleDialog({
  activity,
  open,
  onOpenChange,
  onDone,
}: LinkGoogleDialogProps) {
  const { busy, run } = useCrmAction()
  const [searching, setSearching] = useState(false)
  const [searched, setSearched] = useState(false)
  const [candidate, setCandidate] = useState<GoogleCandidate | null>(null)
  const [manualPlaceId, setManualPlaceId] = useState("")

  const onSearch = async () => {
    setSearching(true)
    setSearched(false)
    setCandidate(null)
    try {
      const result = await searchActivityGoogle(activity.id)
      setCandidate(result)
      setSearched(true)
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Recherche échouée.")
    } finally {
      setSearching(false)
    }
  }

  const linkFromCandidate = () => {
    if (!candidate?.google_place_id) return
    run(
      () =>
        linkActivityGoogle(activity.id, {
          placeId: candidate.google_place_id!,
          rating: candidate.google_rating,
          reviewsCount: candidate.google_reviews_count,
          mapsUrl: candidate.google_maps_url,
        }),
      "Fiche Google liée.",
      async () => {
        await onDone()
        onOpenChange(false)
      }
    )
  }

  const linkFromManual = () => {
    if (!manualPlaceId.trim()) {
      toast.error("Saisissez un place_id.")
      return
    }
    run(
      () => linkActivityGoogle(activity.id, { placeId: manualPlaceId.trim() }),
      "Fiche Google liée.",
      async () => {
        await onDone()
        onOpenChange(false)
        setManualPlaceId("")
      }
    )
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogPopup className="max-w-md">
        <DialogHeader>
          <DialogTitle>Lier à Google Maps</DialogTitle>
          <DialogDescription>{activity.name}</DialogDescription>
        </DialogHeader>
        <DialogPanel className="flex flex-col gap-4">
          <Button
            variant="outline"
            onClick={onSearch}
            loading={searching}
            className="w-full"
          >
            <HugeiconsIcon icon={Search01Icon} strokeWidth={2} />
            Chercher sur Google Maps
          </Button>

          {searching ? (
            <div className="flex justify-center py-4">
              <Spinner className="size-5 text-muted-foreground" />
            </div>
          ) : null}

          {searched && !candidate ? (
            <p className="text-center text-sm text-muted-foreground">
              Aucune correspondance trouvée. Saisissez un place_id manuellement.
            </p>
          ) : null}

          {candidate ? (
            <div className="flex flex-col gap-2 rounded-lg border p-3">
              <span className="font-medium">{candidate.name}</span>
              {candidate.address ? (
                <span className="text-xs text-muted-foreground">
                  {candidate.address}
                </span>
              ) : null}
              {candidate.google_rating !== null ? (
                <span className="inline-flex items-center gap-1 text-sm">
                  <HugeiconsIcon
                    icon={StarIcon}
                    strokeWidth={2}
                    className="size-3.5 text-amber-500"
                  />
                  {candidate.google_rating}
                  <span className="text-xs text-muted-foreground">
                    ({candidate.google_reviews_count ?? 0} avis)
                  </span>
                </span>
              ) : null}
              <Button
                size="sm"
                onClick={linkFromCandidate}
                loading={busy}
                className="mt-1 w-fit"
              >
                Lier cette fiche
              </Button>
            </div>
          ) : null}

          <div className="border-t pt-4">
            <Field>
              <FieldLabel htmlFor={`manual-place-${activity.id}`}>
                Ou saisir un place_id manuellement
              </FieldLabel>
              <Input
                id={`manual-place-${activity.id}`}
                value={manualPlaceId}
                onChange={(event) => setManualPlaceId(event.target.value)}
                placeholder="ChIJ..."
              />
            </Field>
          </div>
        </DialogPanel>
        <DialogFooter>
          <DialogClose render={<Button variant="outline" />}>
            Fermer
          </DialogClose>
          <Button
            onClick={linkFromManual}
            loading={busy}
            disabled={!manualPlaceId.trim()}
          >
            Lier ce place_id
          </Button>
        </DialogFooter>
      </DialogPopup>
    </Dialog>
  )
}
