import './bootstrap';
import { createRoot } from 'react-dom/client'
import Grainient from './components/Grainient';
import Alpine from 'alpinejs';

const el = document.getElementById('iridescence-bg')
if (el && document.body.classList.contains('theme-colorland')) {
    const color = JSON.parse(el.dataset.color || '[0.749, 0.737, 0.447]')
    const mouseReact = el.dataset.mouseReact !== 'false'
    createRoot(el).render(
        <Grainient
            color1="#ffe000"
            color2="#ff0000"
            color3="#0008ff"
            timeSpeed={0.65}
            colorBalance={0.04}
            warpStrength={1.35}
            warpFrequency={5.6}
            warpSpeed={2}
            warpAmplitude={50}
            blendAngle={35}
            blendSoftness={0}
            rotationAmount={650}
            noiseScale={1.4}
            grainAmount={0.1}
            grainScale={2}
            grainAnimated={false}
            contrast={1.5}
            gamma={1}
            saturation={1}
            centerX={0}
            centerY={0}
            zoom={1}
        />
    )
}

window.Alpine = Alpine;
Alpine.start();