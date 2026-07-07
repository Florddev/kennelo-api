export function formatRateAsPercent(rate: string | number): string {
  const value = typeof rate === "number" ? rate : Number.parseFloat(rate)

  if (Number.isNaN(value)) {
    return "—"
  }

  return `${roundPercent(value * 100)} %`
}

export function rateToPercentInput(rate: string | number): string {
  const value = typeof rate === "number" ? rate : Number.parseFloat(rate)

  if (Number.isNaN(value)) {
    return ""
  }

  return String(roundPercent(value * 100))
}

export function percentInputToRate(percent: string): string {
  const value = Number.parseFloat(percent)

  if (Number.isNaN(value)) {
    return ""
  }

  return String(value / 100)
}

function roundPercent(percent: number): number {
  return Math.round(percent * 100) / 100
}
