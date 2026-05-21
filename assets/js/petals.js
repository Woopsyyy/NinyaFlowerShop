/**
 * Cinematic Floating Organic Rose Petal Canvas Particle System
 * Designed specifically for Ninya Flower Shop's romantic hero experience
 */

class PetalSystem {
    constructor() {
        this.canvas = document.getElementById('petals-canvas');
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.petals = [];
        this.petalCount = 35; // Calming density, not distracting
        
        this.resize();
        window.addEventListener('resize', () => this.resize());
        
        this.init();
        this.animate();
    }
    
    resize() {
        this.canvas.width = this.canvas.offsetWidth;
        this.canvas.height = this.canvas.offsetHeight;
    }
    
    init() {
        for (let i = 0; i < this.petalCount; i++) {
            this.petals.push(this.createPetal(true));
        }
    }
    
    createPetal(randomY = false) {
        return {
            x: Math.random() * this.canvas.width,
            y: randomY ? Math.random() * this.canvas.height : -20,
            size: 6 + Math.random() * 12,
            speedY: 0.6 + Math.random() * 1.4,
            speedX: -0.5 + Math.random() * 1.0,
            angle: Math.random() * Math.PI * 2,
            spinSpeed: -0.01 + Math.random() * 0.02,
            swayRange: 20 + Math.random() * 40,
            swaySpeed: 0.01 + Math.random() * 0.02,
            swayOffset: Math.random() * 100,
            opacity: 0.2 + Math.random() * 0.55
        };
    }
    
    drawPetal(p) {
        this.ctx.save();
        this.ctx.translate(p.x, p.y);
        this.ctx.rotate(p.angle);
        this.ctx.beginPath();
        
        // Draw an elegant organic curved leaf/petal shape
        this.ctx.moveTo(0, 0);
        this.ctx.bezierCurveTo(p.size / 2, -p.size / 2, p.size, p.size / 3, p.size / 2, p.size);
        this.ctx.bezierCurveTo(0, p.size / 2, -p.size / 2, p.size / 3, 0, 0);
        
        // Create an elegant, ultra-soft blush rose pink gradient
        const gradient = this.ctx.createLinearGradient(0, 0, p.size, p.size);
        gradient.addColorStop(0, `rgba(253, 246, 246, ${p.opacity})`); // Blush Soft Cream
        gradient.addColorStop(0.5, `rgba(246, 214, 214, ${p.opacity})`); // Soft Pink Rose
        gradient.addColorStop(1, `rgba(225, 178, 178, ${p.opacity * 0.8})`); // Shadow edge pink
        
        this.ctx.fillStyle = gradient;
        this.ctx.fill();
        
        // Soft border highlight
        this.ctx.strokeStyle = `rgba(246, 214, 214, ${p.opacity * 0.3})`;
        this.ctx.lineWidth = 0.5;
        this.ctx.stroke();
        
        this.ctx.restore();
    }
    
    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        for (let i = 0; i < this.petals.length; i++) {
            const p = this.petals[i];
            
            // Apply physics: vertical fall + horizontal drift & sinusoidal sway
            p.y += p.speedY;
            p.swayOffset += p.swaySpeed;
            p.x += p.speedX + Math.sin(p.swayOffset) * 0.4;
            p.angle += p.spinSpeed;
            
            // Draw
            this.drawPetal(p);
            
            // Recycle petals that drift off screen
            if (p.y > this.canvas.height + 20 || p.x < -20 || p.x > this.canvas.width + 20) {
                this.petals[i] = this.createPetal(false);
            }
        }
        
        requestAnimationFrame(() => this.animate());
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PetalSystem();
});
