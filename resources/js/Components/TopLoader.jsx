import { router } from '@inertiajs/react'
import { useEffect, useState } from 'react'

export default function TopLoader() {
  const [active, setActive] = useState(false)
  const [progress, setProgress] = useState(0)

  useEffect(() => {
    const onStart = () => {
      setProgress(0)
      setActive(true)
    }
    const onProgress = (e) => {
      const pct = e?.detail?.progress?.percentage ?? 0
      setProgress(Math.round(pct))
    }
    const onFinish = () => {
      setProgress(100)
      setTimeout(() => setActive(false), 350)
    }

    const offStart = router.on('start', onStart)
    const offProgress = router.on('progress', onProgress)
    const offFinish = router.on('finish', onFinish)

    return () => {
      offStart()
      offProgress()
      offFinish()
    }
  }, [])

  if (!active) return null

  return (
    <div className="top-line-loader">
      <div className="top-line-loader-bar" style={{ width: `${progress}%` }}></div>
    </div>
  )
}
