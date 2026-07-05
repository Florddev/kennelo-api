"use client"

import { useEffect, useRef, useState } from "react"

type CountUpProps = {
  value: number
  durationMs?: number
  decimals?: number
  suffix?: string
}

export function CountUp({
  value,
  durationMs = 900,
  decimals = 0,
  suffix = "",
}: CountUpProps) {
  const [display, setDisplay] = useState(0)
  const frameRef = useRef<number | null>(null)
  const startRef = useRef<number | null>(null)

  useEffect(() => {
    startRef.current = null

    const tick = (timestamp: number) => {
      if (startRef.current === null) {
        startRef.current = timestamp
      }
      const elapsed = timestamp - startRef.current
      const progress = Math.min(elapsed / durationMs, 1)
      const eased = 1 - Math.pow(1 - progress, 3)
      setDisplay(value * eased)

      if (progress < 1) {
        frameRef.current = requestAnimationFrame(tick)
      } else {
        setDisplay(value)
      }
    }

    frameRef.current = requestAnimationFrame(tick)

    return () => {
      if (frameRef.current !== null) {
        cancelAnimationFrame(frameRef.current)
      }
    }
  }, [value, durationMs])

  return (
    <>
      {display.toLocaleString("fr-FR", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      })}
      {suffix}
    </>
  )
}
