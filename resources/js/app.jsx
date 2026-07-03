import './bootstrap';
import { createRoot } from 'react-dom/client'
import Grainient from './components/Grainient';
import SponsorDecayBackground from './components/SponsorDecayBackground';
import Alpine from 'alpinejs';

const el = document.getElementById('iridescence-bg');

if (el) {
    if (document.body.classList.contains('theme-colorland')) {
        createRoot(el).render(
            <Grainient
                color1="#ffe000"
                color2="#ff0000"
                color3="#0008ff"
                timeSpeed={0.65}
                colorBalance={0.04}
                warpStrength={1.35}
                wrapFrequency={5.6}
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
        );
    } else if (document.body.classList.contains('theme-sponsor')) {
        createRoot(el).render(
            <SponsorDecayBackground />
        );
    }
}

window.Alpine = Alpine;
Alpine.start();