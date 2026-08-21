import { useEffect, useRef } from 'react'

export default function ParticlesBg() {
    const canvasRef = useRef(null)

    useEffect(() => {
        const canvas = canvasRef.current
        if (!canvas) return
        const ctx = canvas.getContext('2d')
        if (!ctx) return

        let animationFrameId
        let width = canvas.width = window.innerWidth
        let height = canvas.height = window.innerHeight

        const handleResize = () => {
            if (!canvas) return
            width = canvas.width = window.innerWidth
            height = canvas.height = window.innerHeight
        }
        window.addEventListener('resize', handleResize)

        let mouse = { x: null, y: null }

        const handleMouseMove = (e) => {
            mouse.x = e.clientX
            mouse.y = e.clientY
        }

        const handleMouseLeave = () => {
            mouse.x = null
            mouse.y = null
        }

        window.addEventListener('mousemove', handleMouseMove)
        document.addEventListener('mouseleave', handleMouseLeave)

        // Particle class
        class Particle {
            constructor() {
                this.x = Math.random() * width
                this.y = Math.random() * height
                this.vx = (Math.random() - 0.5) * 0.4
                this.vy = (Math.random() - 0.5) * 0.4
                this.radius = Math.random() * 2 + 1
                this.alpha = Math.random() * 0.5 + 0.2
            }

            update() {
                this.x += this.vx
                this.y += this.vy

                if (this.x < 0 || this.x > width) this.vx = -this.vx
                if (this.y < 0 || this.y > height) this.vy = -this.vy
            }

            draw() {
                ctx.beginPath()
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2)
                ctx.fillStyle = `rgba(129, 140, 248, ${this.alpha})` // primary-light style color
                ctx.fill()
            }
        }

        const particleCount = Math.min(Math.floor((width * height) / 12000), 100)
        const particles = Array.from({ length: particleCount }, () => new Particle())

        const animate = () => {
            ctx.clearRect(0, 0, width, height)

            // Draw connecting lines
            for (let i = 0; i < particles.length; i++) {
                const p1 = particles[i]
                p1.update()
                p1.draw()

                for (let j = i + 1; j < particles.length; j++) {
                    const p2 = particles[j]
                    const dx = p1.x - p2.x
                    const dy = p1.y - p2.y
                    const dist = Math.sqrt(dx * dx + dy * dy)

                    if (dist < 120) {
                        ctx.beginPath()
                        ctx.moveTo(p1.x, p1.y)
                        ctx.lineTo(p2.x, p2.y)
                        const alpha = (1 - dist / 120) * 0.12
                        ctx.strokeStyle = `rgba(129, 140, 248, ${alpha})`
                        ctx.lineWidth = 0.8
                        ctx.stroke()
                    }
                }
            }

            // Draw mouse connection lines
            if (mouse.x !== null && mouse.y !== null) {
                particles.forEach(p => {
                    const dx = p.x - mouse.x
                    const dy = p.y - mouse.y
                    const dist = Math.sqrt(dx * dx + dy * dy)

                    if (dist < 150) {
                        ctx.beginPath()
                        ctx.moveTo(p.x, p.y)
                        ctx.lineTo(mouse.x, mouse.y)
                        const alpha = (1 - dist / 150) * 0.18
                        ctx.strokeStyle = `rgba(99, 102, 241, ${alpha})`
                        ctx.lineWidth = 1
                        ctx.stroke()
                    }
                })
            }

            animationFrameId = requestAnimationFrame(animate)
        }

        animate()

        return () => {
            window.removeEventListener('resize', handleResize)
            window.removeEventListener('mousemove', handleMouseMove)
            document.removeEventListener('mouseleave', handleMouseLeave)
            cancelAnimationFrame(animationFrameId)
        }
    }, [])

    return (
        <canvas
            ref={canvasRef}
            style={{
                position: 'absolute',
                top: 0,
                left: 0,
                width: '100%',
                height: '100%',
                pointerEvents: 'none',
                zIndex: 0
            }}
        />
    )
}
